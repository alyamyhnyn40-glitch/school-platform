<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\{DB, Env, HttpException, Migrator, Request, Response, Validator, View};
use App\Services\{Crypto, PasswordHasher, Phone, SeedService};

/** معالج التثبيت: يقفل نفسه بعد النجاح (storage/installed.lock). */
final class InstallController
{
    private function guard(): void
    {
        if (is_file(base_path('storage/installed.lock'))) { throw new HttpException(403, 'تم تثبيت المنصة مسبقًا'); }
    }

    /** هل القاعدة المضبوطة في .env تحوي تثبيتًا سابقًا (مشغّل منصة)؟ إن نعم يُنشأ ملف القفل ويستمر العمل. */
    public static function healLock(): bool
    {
        if (!is_file(base_path('.env'))) { return false; }
        try { if (!DB::value("SELECT COUNT(*) FROM platform_admins")) { return false; } }
        catch (\Throwable) { return false; }
        @mkdir(storage_path(), 0775, true); return @file_put_contents(base_path('storage/installed.lock'), date('c') . " (restored)") !== false;
    }

    public static function requirements(): array
    {
        $exts = ['pdo_mysql', 'mbstring', 'sodium', 'json', 'curl', 'gd', 'zip', 'intl'];
        $r = [['PHP ≥ 8.2 (الحالي ' . PHP_VERSION . ')', version_compare(PHP_VERSION, '8.2.0', '>='), true]];
        foreach ($exts as $e) { $r[] = ["امتداد $e", extension_loaded($e), in_array($e, ['pdo_mysql', 'mbstring', 'sodium', 'json'], true)]; }
        foreach (['storage', 'storage/logs', 'storage/uploads', 'storage/cache', 'storage/backups'] as $d) {
            if (!is_dir(base_path($d))) { @mkdir(base_path($d), 0775, true); }
            $r[] = ["كتابة في $d", is_writable(base_path($d)), true];
        }
        $r[] = ['كتابة ملف .env', is_writable(base_path()) || is_writable(base_path('.env')), true];
        return $r;
    }

    public function show(Request $r): Response
    {
        $this->guard();
        $reqs = self::requirements();
        $ok = !array_filter($reqs, fn($x) => $x[2] && !$x[1]);
        return Response::html(View::render('install/index', ['title' => 'تثبيت المنصة', 'reqs' => $reqs, 'ok' => $ok, 'scripts' => []], 'bare'));
    }

    public function run(Request $r): Response
    {
        $this->guard();
        if (array_filter(self::requirements(), fn($x) => $x[2] && !$x[1])) { throw new HttpException(422, 'متطلبات الخادم غير مستوفاة'); }
        $v = Validator::make($r->all(), [
            'app_url' => 'required|max:200', 'db_host' => 'required', 'db_port' => 'required|numeric', 'db_name' => 'required', 'db_user' => 'required',
            'admin_name' => 'required|max:200', 'admin_email' => 'required|email', 'admin_phone' => 'max:30', 'admin_password' => 'required|min:8',
        ]);
        if ($v->fails()) { throw new HttpException(422, 'تحقق من الحقول المطلوبة', $v->firstErrors()); }
        if ($r->input('admin_password') !== $r->input('admin_password_confirm')) { throw new HttpException(422, 'كلمتا المرور غير متطابقتين', ['admin_password_confirm' => 'غير مطابق']); }
        if ($err = PasswordHasher::validate((string)$r->input('admin_password'))) { throw new HttpException(422, $err, ['admin_password' => $err]); }

        // إنشاء القاعدة تلقائيًا إن لم تكن موجودة (يلزم صلاحية CREATE للمستخدم)
        if (!empty($r->input('create_db')) && $r->input('create_db') !== '0') {
            $dbn = (string)$r->input('db_name'); if (!preg_match('/^[A-Za-z0-9_]{1,64}$/', $dbn)) { throw new HttpException(422, 'اسم القاعدة: حروف إنجليزية وأرقام و_ فقط', ['db_name' => 'غير صالح']); }
            try { $p0 = new \PDO(sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $r->input('db_host'), $r->input('db_port')), (string)$r->input('db_user'), (string)$r->input('db_pass', ''), [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]); $p0->exec("CREATE DATABASE IF NOT EXISTS `$dbn` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"); }
            catch (\PDOException $e) { throw new HttpException(422, 'تعذّر إنشاء القاعدة: أنشئها يدويًا من phpMyAdmin أو امنح المستخدم صلاحية CREATE', ['db_name' => 'فشل الإنشاء']); }
        }
        // اختبار الاتصال قبل كتابة أي شيء
        try {
            new \PDO(sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $r->input('db_host'), $r->input('db_port'), $r->input('db_name')),
                (string)$r->input('db_user'), (string)$r->input('db_pass', ''), [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
        } catch (\PDOException $e) {
            throw new HttpException(422, 'تعذر الاتصال بقاعدة البيانات: تحقق من البيانات', ['db_host' => 'فشل الاتصال']);
        }

        // المسار الفرعي (WAMP: /school-platform/public) يُستنتج تلقائيًا إن لم يُدخَل
        $base = trim((string)$r->input('base_path', '')); if ($base === '') { $d = rtrim(str_replace('\\', '/', dirname((string)($_SERVER['SCRIPT_NAME'] ?? ''))), '/'); $base = $d === '.' ? '' : $d; }
        $appUrl = rtrim((string)$r->input('app_url'), '/'); if ($base !== '' && str_ends_with($appUrl, $base)) { $appUrl = substr($appUrl, 0, -strlen($base)); }
        $encKey = trim((string)$r->input('enc_key', '')); $keyReused = false;
        if ($encKey !== '') { $raw = base64_decode($encKey, true); if ($raw === false || strlen($raw) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES) { throw new HttpException(422, 'مفتاح التشفير غير صالح (يجب أن يكون المفتاح القديم كما هو في .env)', ['enc_key' => 'غير صالح']); } $keyReused = true; } else { $encKey = Crypto::generateKey(); }
        $env = [
            'APP_ENV' => 'production', 'APP_DEBUG' => '0', 'APP_URL' => $appUrl,
            'APP_BASE_PATH' => $base, 'APP_TIMEZONE' => 'Asia/Aden', 'APP_ENC_KEY' => $encKey,
            'SESSION_IDLE_MINUTES' => '30', 'DB_HOST' => (string)$r->input('db_host'), 'DB_PORT' => (string)$r->input('db_port'),
            'DB_NAME' => (string)$r->input('db_name'), 'DB_USER' => (string)$r->input('db_user'), 'DB_PASS' => (string)$r->input('db_pass', ''),
            'SMS_DRIVER' => 'log',
        ];
        $content = '';
        foreach ($env as $k => $val) { $content .= $k . '=' . str_replace(["\n", "\r"], '', $val) . "\n"; }
        if (@file_put_contents(base_path('.env'), $content, LOCK_EX) === false) { throw new HttpException(500, 'تعذر كتابة ملف .env'); }
        @chmod(base_path('.env'), 0640);
        foreach ($env as $k => $val) { Env::set($k, $val); }
        DB::reset();

        DB::pdo();
        Migrator::run();
        $existing = (int)DB::value('SELECT COUNT(*) FROM platform_admins') > 0;   // قاعدة فيها تثبيت سابق: نستعيد الإعداد دون المساس بالبيانات
        $msg = 'تم التثبيت بنجاح';
        if ($existing) {
            \App\Services\SeedService::syncAdminRoles();
            $msg = 'وُجد تثبيت سابق في هذه القاعدة فاستُعيد الإعداد دون المساس بالبيانات. سجّل الدخول بحساب المشغّل القديم.' . ($keyReused ? '' : ' تنبيه: أُنشئ مفتاح تشفير جديد، فالحقول المشفّرة القديمة (الهويات والملاحظات الحساسة) لن تُقرأ؛ أعد إدخال المفتاح القديم في .env إن وُجد.');
        } else {
            SeedService::seedBase();
            $email = mb_strtolower(trim((string)$r->input('admin_email')));
            $phone = trim((string)$r->input('admin_phone')) !== '' ? Phone::normalize((string)$r->input('admin_phone')) : null;
            DB::query("INSERT INTO users (school_id, user_type, full_name, email, phone, password_hash, must_change_password) VALUES (NULL, 'platform', ?, ?, ?, ?, 0)",
                [trim((string)$r->input('admin_name')), $email, $phone, PasswordHasher::hash((string)$r->input('admin_password'))]);
            DB::query('INSERT INTO platform_admins (user_id, is_owner) VALUES (?, 1)', [DB::lastId()]);
            if (!empty($r->input('load_demo')) && $r->input('load_demo') !== '0') {
                try { \App\Services\DemoService::load($email, (string)$r->input('admin_password')); $msg .= '، وحُمّلت البيانات التجريبية (كلمة مرور حساباتها Demo12345)'; }
                catch (\Throwable $e) { \App\Core\Logger::error('فشل تحميل البيانات التجريبية: ' . $e->getMessage()); $msg .= '. تعذّر تحميل البيانات التجريبية (راجع storage/logs).'; }
            }
        }
        @mkdir(storage_path('cache'), 0775, true); @file_put_contents(storage_path('cache/applied.version'), app_version());
        file_put_contents(base_path('storage/installed.lock'), date('c'));
        return Response::ok(['message' => $msg, 'redirect' => url('/login')]);
    }
}
