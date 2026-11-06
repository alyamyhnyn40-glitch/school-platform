<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\{Auth, DB, Env, Updater};

/** فحص أمني دوري لإعدادات التشغيل (للمشغّل): يكشف الإعدادات الخطرة ويقترح الإصلاح. لا يغيّر شيئًا. */
final class SecurityCheckService
{
    /** @return list<array{level:string, title:string, detail:string, fix:string}> level: ok|warn|bad */
    public static function run(): array
    {
        Auth::authorize('platform.settings'); $r = [];
        $add = function (string $lvl, string $t, string $d, string $f = '') use (&$r) { $r[] = ['level' => $lvl, 'title' => $t, 'detail' => $d, 'fix' => $f]; };
        $prod = Env::isProduction();
        $add($prod ? 'ok' : 'warn', 'بيئة التشغيل', 'APP_ENV=' . Env::get('APP_ENV', 'production'), $prod ? '' : 'اجعل APP_ENV=production على الخادم الحقيقي (يعطّل وضع الدفع التجريبي ويشدّد حماية الروابط).');
        $dbg = Env::get('APP_DEBUG') === '1';
        $add($dbg ? 'bad' : 'ok', 'عرض الأخطاء التفصيلية', $dbg ? 'APP_DEBUG=1' : 'معطّل', $dbg ? 'اجعل APP_DEBUG=0 حتى لا تظهر تفاصيل الأخطاء للزوار.' : '');
        $https = str_starts_with((string)Env::get('APP_URL', ''), 'https://');
        $add($https ? 'ok' : ($prod ? 'bad' : 'warn'), 'الاتصال المشفّر HTTPS', (string)Env::get('APP_URL', '—'), $https ? '' : 'فعّل شهادة SSL (مجانية عبر Let\'s Encrypt) واجعل APP_URL يبدأ بـ https://.');
        $key = (string)Env::get('APP_ENC_KEY', ''); $raw = base64_decode($key, true);
        if ($raw === false || strlen($raw) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES) { $add('bad', 'مفتاح التشفير', 'غير صالح أو مفقود', 'أعد التثبيت أو ضع مفتاحًا صالحًا في APP_ENC_KEY.'); }
        else {
            $demo = is_file(base_path('database/ready/.env.ready')) && preg_match('/^APP_ENC_KEY=(.+)$/m', (string)file_get_contents(base_path('database/ready/.env.ready')), $m) && trim($m[1]) === $key;
            $add($demo ? ($prod ? 'bad' : 'warn') : 'ok', 'مفتاح التشفير', $demo ? 'يُستخدم مفتاح القاعدة التجريبية المعروف علنًا' : 'مفتاح خاص صالح', $demo ? 'للإنتاج: ثبّت من /install ليُولَّد مفتاح خاص، ولا تستخدم القاعدة الجاهزة.' : '');
        }
        $envPerm = @fileperms(base_path('.env'));
        if ($envPerm !== false && DIRECTORY_SEPARATOR === '/') { $world = ($envPerm & 0004) !== 0; $add($world ? 'warn' : 'ok', 'صلاحيات ملف .env', sprintf('%o', $envPerm & 0777), $world ? 'اجعلها 640 أو 600: chmod 640 .env' : ''); }
        $add(is_file(base_path('.htaccess')) || is_file(base_path('public/.htaccess')) ? 'ok' : 'warn', 'حماية المجلدات الداخلية', 'ملف .htaccess ' . (is_file(base_path('.htaccess')) ? 'موجود' : 'غير موجود في الجذر'), 'اجعل جذر الموقع مجلد public، أو أبقِ .htaccess الجذر الذي يحجب app وstorage و.env.');
        $weak = [];
        foreach (['admin@example.com' => 'Admin12345', 'director@demo.test' => 'Demo12345'] as $em => $pw) { $h = DB::value('SELECT password_hash FROM users WHERE email = ? AND status = ? AND deleted_at IS NULL', [$em, 'active']); if ($h && password_verify($pw, (string)$h)) { $weak[] = $em; } }
        $demoUsers = (int)DB::value("SELECT COUNT(*) FROM users WHERE email LIKE '%.demo@school.test' AND status = 'active'");
        $add($weak ? 'bad' : 'ok', 'كلمات مرور افتراضية', $weak ? 'حسابات بكلمات مرور معروفة: ' . implode('، ', $weak) : 'لا توجد', $weak ? 'غيّر كلمات المرور فورًا أو عطّل الحسابات التجريبية.' : '');
        if ($demoUsers) { $add($prod ? 'warn' : 'ok', 'حسابات تجريبية', "$demoUsers حساب تجريبي فعّال", 'احذف المدرسة التجريبية أو عطّل حساباتها قبل الاستخدام الفعلي.'); }
        $add(version_compare(PHP_VERSION, '8.2.0', '>=') ? 'ok' : 'bad', 'إصدار PHP', PHP_VERSION, 'حدّث PHP إلى 8.2 أو أحدث (إصدار مدعوم أمنيًا).');
        $last = 0; foreach (glob(storage_path('backups/backup-*.zip')) ?: [] as $f) { $last = max($last, (int)filemtime($f)); }
        $schoolBk = (int)DB::value('SELECT UNIX_TIMESTAMP(MAX(created_at)) FROM school_backups'); $last = max($last, $schoolBk);
        $days = $last ? (int)floor((time() - $last) / 86400) : null;
        $add($days === null ? 'bad' : ($days > 7 ? 'warn' : 'ok'), 'النسخ الاحتياطي', $days === null ? 'لا توجد أي نسخة احتياطية' : "آخر نسخة قبل $days يوم", 'جدول php bin/backup.php يوميًا، وفعّل النسخ التلقائي للمدارس، واحتفظ بنسخة خارج الخادم مع ملف .env.');
        $fails = (int)DB::value("SELECT COUNT(*) FROM security_events WHERE type IN ('login_failed','account_locked') AND created_at > NOW() - INTERVAL 1 DAY");
        $add($fails > 50 ? 'warn' : 'ok', 'محاولات دخول فاشلة (24 ساعة)', (string)$fails, $fails > 50 ? 'راجع «الأمان» لمعرفة المصادر؛ قد تكون محاولة تخمين كلمات مرور.' : '');
        $add(Updater::pending() ? 'warn' : 'ok', 'ترقية قاعدة البيانات', Updater::pending() ? 'توجد ترقية لم تُطبَّق' : 'محدّثة (الإصدار ' . app_version() . ')', 'افتح أي صفحة لتطبيقها تلقائيًا أو نفّذ php bin/migrate.php.');
        $mail = EmailService::enabled(); $sms = \App\Services\Sms\SmsConfig::enabled('sms');
        $add($mail || $sms ? 'ok' : 'warn', 'قنوات استعادة الحساب', ($mail ? 'البريد مفعّل' : 'البريد غير مفعّل') . '، ' . ($sms ? 'SMS مفعّل' : 'SMS غير مفعّل'), 'اضبط البريد أو SMS حتى يستطيع المستخدمون استعادة كلمات المرور.');
        $admins = (int)DB::value('SELECT COUNT(*) FROM platform_admins');
        $add($admins > 3 ? 'warn' : 'ok', 'حسابات مشغّل المنصة', "$admins حساب", $admins > 3 ? 'قلّل حسابات المشغّل إلى الضروري فقط.' : '');
        return $r;
    }
    public static function summary(array $checks): array
    {
        $c = ['ok' => 0, 'warn' => 0, 'bad' => 0]; foreach ($checks as $x) { $c[$x['level']]++; }
        $last = PlatformSettings::get('security.last_review');
        return $c + ['score' => count($checks) ? (int)round(($c['ok'] + $c['warn'] * 0.5) / count($checks) * 100) : 0, 'last_review' => $last, 'review_due' => !$last || strtotime($last) < strtotime('-30 days')];
    }
    public static function markReviewed(): void
    {
        Auth::authorize('platform.settings'); PlatformSettings::set('security.last_review', date('Y-m-d H:i:s'));
        AuditService::log('platform.security_review', 'platform', null, null, self::summary(self::run()));
    }
}
