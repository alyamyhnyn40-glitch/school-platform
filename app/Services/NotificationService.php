<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\{Auth, DB, Tenant};

/** إشعارات داخل المنصة + طابور SMS. */
final class NotificationService
{
    /** يرسل إشعارًا داخليًا لمجموعة مستخدمين (من المدرسة الحالية فقط). */
    public static function notify(array $userIds, string $title, ?string $body = null, ?string $url = null): int
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $userIds)))); if (!$ids) { return 0; }
        $sid = Tenant::require(); $self = Auth::id();
        $valid = array_column(DB::all('SELECT id FROM users WHERE school_id = ? AND status = "active" AND deleted_at IS NULL AND id IN (' . implode(',', $ids) . ')', [$sid]), 'id');
        $n = 0;
        foreach ($valid as $uid) { if ((int)$uid === $self) { continue; } DB::query('INSERT INTO notifications (school_id, user_id, title, body, url) VALUES (?,?,?,?,?)', [$sid, $uid, mb_substr($title, 0, 200), $body !== null ? mb_substr($body, 0, 500) : null, $url]); $n++; }
        return $n;
    }
    public static function sms(array $phones, string $body): int { return self::queue('sms', $phones, $body); }
    /** يضيف رسائل إلى طابور الإرسال لقناة (sms | whatsapp). */
    public static function queue(string $channel, array $recipients, string $body): int
    {
        if (!in_array($channel, ['sms', 'whatsapp'], true)) { throw new \InvalidArgumentException('قناة غير صالحة'); }
        $sid = Tenant::require(); $n = 0;
        foreach (array_unique(array_filter($recipients)) as $p) { DB::query('INSERT INTO notification_queue (school_id, channel, recipient, body) VALUES (?, ?, ?, ?)', [$sid, $channel, $p, mb_substr($body, 0, 480)]); $n++; }
        return $n;
    }
    /** هل قناة واتساب مفعّلة بمزود حقيقي من مشغّل المنصة؟ (لإظهار خيارها في الواجهة) */
    public static function whatsappEnabled(): bool { return \App\Services\Sms\SmsConfig::enabled('whatsapp'); }

    public static function unread(): int { return (int)DB::value('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND read_at IS NULL', [Auth::id()]); }
    public static function latest(int $limit = 15): array { return DB::all('SELECT id, title, body, url, read_at, created_at FROM notifications WHERE user_id = ? ORDER BY id DESC LIMIT ' . (int)$limit, [Auth::id()]); }
    public static function markRead(?int $id): void
    {
        DB::query('UPDATE notifications SET read_at = NOW() WHERE user_id = ? AND read_at IS NULL' . ($id ? ' AND id = ' . (int)$id : ''), [Auth::id()]);
    }

    // ---------- حل المستلمين ----------
    /** مستخدمو الطلاب (وأولياء أمورهم الذين يحق لهم الاطلاع) في شعبة. */
    public static function sectionUsers(int $sectionId, bool $students = true, bool $guardians = true): array
    {
        $sid = Tenant::require(); $ids = [];
        if ($students) { foreach (DB::all("SELECT s.user_id FROM student_enrollments e JOIN students s ON s.id = e.student_id WHERE e.section_id = ? AND e.school_id = ? AND e.status = 'active' AND s.user_id IS NOT NULL", [$sectionId, $sid]) as $r) { $ids[] = (int)$r['user_id']; } }
        if ($guardians) { foreach (DB::all("SELECT g.user_id FROM student_enrollments e JOIN student_guardians sg ON sg.student_id = e.student_id AND sg.school_id = e.school_id AND sg.can_view = 1 JOIN guardians g ON g.id = sg.guardian_id WHERE e.section_id = ? AND e.school_id = ? AND e.status = 'active' AND g.user_id IS NOT NULL", [$sectionId, $sid]) as $r) { $ids[] = (int)$r['user_id']; } }
        return array_values(array_unique($ids));
    }
    public static function sectionGuardianPhones(int $sectionId, bool $wa = false): array
    {
        return array_column(DB::all("SELECT DISTINCT " . ($wa ? "COALESCE(NULLIF(g.whatsapp, ''), g.phone)" : 'g.phone') . " AS phone FROM student_enrollments e JOIN student_guardians sg ON sg.student_id = e.student_id AND sg.school_id = e.school_id AND sg.can_view = 1 JOIN guardians g ON g.id = sg.guardian_id WHERE e.section_id = ? AND e.school_id = ? AND e.status = 'active' AND g.phone IS NOT NULL", [$sectionId, Tenant::require()]), 'phone');
    }
}
