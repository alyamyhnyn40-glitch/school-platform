<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\{DB, Env, Logger};
use App\Services\Sms\SmsFactory;

/** معالجة طابور الرسائل الصادرة (sms / whatsapp / email) مع إعادة المحاولة (5 مرات بفواصل متزايدة). تستدعيها bin/cron-notifications.php. */
final class NotificationQueueService
{
    public static function process(int $limit = 100): array
    {
        $rows = DB::all("SELECT * FROM notification_queue WHERE status = 'pending' AND run_after <= NOW() AND attempts < 5 ORDER BY id LIMIT " . (int)$limit);
        $sent = $failed = 0;
        foreach ($rows as $r) {
            $ok = false; $err = null;
            try {
                if (in_array($r['channel'], ['sms', 'whatsapp'], true)) { $ok = SmsFactory::makeFor($r['channel'])->send($r['recipient'], $r['body']); if (!$ok) { $err = 'رفض المزود الرسالة'; } }
                elseif ($r['channel'] === 'email' && EmailService::enabled()) { $ok = EmailService::send($r['recipient'], 'إشعار من ' . Brand::name(), $r['body']); if (!$ok) { $err = EmailService::$last['error'] ?? 'فشل الإرسال'; } }
                else { $err = 'لا يوجد مزود مضبوط للقناة ' . $r['channel']; }
            } catch (\Throwable $e) { $err = $e->getMessage(); Logger::error('notification failed', ['id' => $r['id'], 'e' => $err]); }
            if ($ok) { DB::query("UPDATE notification_queue SET status='sent', sent_at=NOW(), attempts=attempts+1 WHERE id=?", [$r['id']]); $sent++; }
            else {
                $att = (int)$r['attempts'] + 1;
                DB::query('UPDATE notification_queue SET attempts=?, last_error=?, status=?, run_after = NOW() + INTERVAL ? MINUTE WHERE id=?', [$att, $err ?? 'فشل الإرسال', $att >= 5 ? 'failed' : 'pending', $att * 5, $r['id']]);
                $failed++;
            }
        }
        return ['sent' => $sent, 'failed' => $failed];
    }
}
