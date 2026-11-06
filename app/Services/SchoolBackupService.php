<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\{Auth, DB, Env, HttpException, Tenant};

/**
 * نسخ احتياطي لبيانات مدرسة واحدة: ملف ZIP فيه data.sql (بيانات المدرسة فقط) + ملفاتها المرفوعة + manifest.json.
 * محلي (تنزيل) أو سحابي (متوافق مع S3). كل مدرسة ترى نسخها فقط. الاستعادة: على قاعدة جديدة بعد التثبيت (انظر README داخل الأرشيف).
 */
final class SchoolBackupService
{
    /** جداول لا تُنسخ: عامة أو مؤقتة أو كتالوج. */
    private const SKIP = ['migrations', 'permissions', 'currencies', 'subscription_plans', 'platform_admins', 'email_codes', 'otp_codes', 'password_resets', 'login_attempts', 'rate_limits', 'assistant_usage', 'school_backups', 'security_events', 'notification_queue'];
    public const KEEP_DEFAULT = 7;
    private static array $scopeCache = [];

    // ---------- النطاق: شرط WHERE لكل جدول ----------
    private static function cols(string $t): array { return array_column(DB::all('SELECT column_name AS c FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ?', [$t]), 'c'); }
    /** @return ?string شرط SQL بمعامل واحد (?) هو رقم المدرسة */
    public static function scope(string $t, int $depth = 0): ?string
    {
        if (isset(self::$scopeCache[$t])) { return self::$scopeCache[$t] ?: null; }
        $c = self::cols($t); $r = null;
        if ($t === 'schools') { $r = 'id = ?'; }
        elseif ($t === 'student_identities') { $r = 'id IN (SELECT identity_id FROM students WHERE school_id = ? AND identity_id IS NOT NULL)'; }
        elseif (in_array('school_id', $c, true)) { $r = 'school_id = ?'; }
        elseif ($depth < 4) {
            foreach (DB::all('SELECT column_name AS col, referenced_table_name AS pt, referenced_column_name AS pc FROM information_schema.key_column_usage WHERE table_schema = DATABASE() AND table_name = ? AND referenced_table_name IS NOT NULL', [$t]) as $fk) {
                if ($fk['pt'] === $t) { continue; } $ps = self::scope($fk['pt'], $depth + 1);
                if ($ps) { $r = "`{$fk['col']}` IN (SELECT `{$fk['pc']}` FROM `{$fk['pt']}` WHERE $ps)"; break; }
            }
        }
        self::$scopeCache[$t] = $r ?? ''; return $r;
    }
    /** @return list<string> */
    public static function tables(): array
    {
        $out = []; foreach (DB::all("SELECT table_name AS t FROM information_schema.tables WHERE table_schema = DATABASE() AND table_type = 'BASE TABLE' ORDER BY table_name") as $x) { if (!in_array($x['t'], self::SKIP, true) && self::scope($x['t'])) { $out[] = $x['t']; } }
        return $out;
    }
    /** يكتب data.sql لمدرسة. @return array{rows:int, tables:int} */
    public static function dumpSql(int $sid, string $path): array
    {
        $pdo = DB::pdo(); $fh = fopen($path, 'wb'); $rows = 0; $n = 0;
        fwrite($fh, "-- نسخة احتياطية لمدرسة #$sid — " . date('c') . " — إصدار المنصة " . app_version() . "\nSET NAMES utf8mb4; SET FOREIGN_KEY_CHECKS=0; SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n");
        foreach (self::tables() as $t) {
            $where = self::scope($t); $extra = '';
            if ($t === 'settings') { $extra = " AND skey NOT LIKE 'backup.%'"; }   // مفاتيح التخزين السحابي لا تُصدَّر
            $st = $pdo->prepare("SELECT * FROM `$t` WHERE $where$extra"); $st->execute([$sid]); $batch = []; $cols = null; $cnt = 0;
            while ($row = $st->fetch(\PDO::FETCH_ASSOC)) {
                $cols ??= '`' . implode('`,`', array_keys($row)) . '`';
                $batch[] = '(' . implode(',', array_map(fn($v) => $v === null ? 'NULL' : $pdo->quote((string)$v), $row)) . ')'; $cnt++;
                if (count($batch) >= 200) { fwrite($fh, "INSERT INTO `$t` ($cols) VALUES " . implode(",\n", $batch) . ";\n"); $batch = []; }
            }
            if ($batch) { fwrite($fh, "INSERT INTO `$t` ($cols) VALUES " . implode(",\n", $batch) . ";\n"); }
            if ($cnt) { $rows += $cnt; $n++; }
        }
        fwrite($fh, "SET FOREIGN_KEY_CHECKS=1;\n"); fclose($fh); return ['rows' => $rows, 'tables' => $n];
    }

    // ---------- الإنشاء ----------
    private static function dir(int $sid): string { $d = storage_path("backups/school-$sid"); if (!is_dir($d) && !@mkdir($d, 0770, true) && !is_dir($d)) { throw new HttpException(500, 'تعذّر إنشاء مجلد النسخ'); } return $d; }
    /** ينشئ نسخة للمدرسة $sid (للاستخدام اليدوي والمجدول). @return array صف school_backups */
    public static function create(int $sid, string $kind = 'manual', ?string $password = null, bool $withFiles = true, ?int $userId = null): array
    {
        if ($password !== null && $password !== '' && mb_strlen($password) < 8) { throw new HttpException(422, 'كلمة مرور الأرشيف 8 أحرف على الأقل', ['password' => 'قصيرة']); }
        @set_time_limit(0); $dir = self::dir($sid); $stamp = date('Ymd-His'); $sql = "$dir/data-$stamp.sql"; $name = "school-$sid-$stamp.zip"; $zipPath = "$dir/$name";
        $d = self::dumpSql($sid, $sql); $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) { @unlink($sql); throw new HttpException(500, 'تعذّر إنشاء الأرشيف'); }
        $enc = $password !== null && $password !== '';
        $zip->addFile($sql, 'data.sql'); $files = 0;
        if ($withFiles) {
            foreach ([storage_path("uploads/files/$sid"), storage_path('uploads/school-logos')] as $root) {
                if (!is_dir($root)) { continue; }
                $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
                foreach ($it as $f) {
                    if (!$f->isFile()) { continue; } $rel = substr($f->getPathname(), strlen(storage_path('uploads')) + 1);
                    if (str_contains($root, 'school-logos') && !preg_match('#school-logos/' . $sid . '\.#', $f->getPathname())) { continue; }
                    $zip->addFile($f->getPathname(), 'uploads/' . $rel); $files++;
                }
            }
        }
        $zip->addFromString('manifest.json', json_encode(['school_id' => $sid, 'created_at' => date('c'), 'app_version' => app_version(), 'rows' => $d['rows'], 'tables' => $d['tables'], 'files' => $files, 'encrypted' => $enc, 'note' => 'الحقول الحساسة مشفّرة بمفتاح APP_ENC_KEY؛ احتفظ بنسخة من .env'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        $zip->addFromString('README.txt', "استعادة نسخة مدرسة:\n1) ثبّت المنصة على خادم/قاعدة جديدة (أو نظّف بيانات المدرسة القديمة).\n2) استخدم نفس APP_ENC_KEY من .env القديم وإلا تتعذّر قراءة الحقول المشفّرة.\n3) نفّذ data.sql على القاعدة (mysql قاعدة < data.sql).\n4) انسخ مجلد uploads إلى storage/uploads.\n");
        if ($enc) { foreach (array_merge(['data.sql', 'manifest.json'], array_filter(array_map(fn($i) => $zip->getNameIndex($i), range(0, $zip->numFiles - 1)), fn($n) => str_starts_with((string)$n, 'uploads/'))) as $n) { $zip->setEncryptionName($n, \ZipArchive::EM_AES_256, $password); } }
        $zip->close(); @unlink($sql);
        if (!is_file($zipPath)) { throw new HttpException(500, 'فشل إنشاء النسخة'); }
        DB::query('INSERT INTO school_backups (school_id, filename, size_bytes, kind, encrypted, includes_files, row_count, app_version, created_by) VALUES (?,?,?,?,?,?,?,?,?)', [$sid, $name, filesize($zipPath), $kind, $enc ? 1 : 0, $withFiles ? 1 : 0, $d['rows'], app_version(), $userId]);
        return DB::one('SELECT * FROM school_backups WHERE id = ?', [DB::lastId()]);
    }

    // ---------- الإعدادات السحابية ----------
    private static function sget(int $sid, string $k, bool $secret = false): ?string
    {
        $v = DB::value('SELECT svalue FROM settings WHERE school_id = ? AND skey = ?', [$sid, 'backup.' . $k]); if ($v === null || $v === false || $v === '') { return null; }
        return $secret ? Crypto::decrypt((string)$v) : (string)$v;
    }
    private static function sput(int $sid, string $k, ?string $v, bool $secret = false): void
    {
        $st = ($v === null || $v === '') ? null : ($secret ? Crypto::encrypt($v) : $v);
        DB::query('INSERT INTO settings (school_id, skey, svalue) VALUES (?,?,?) ON DUPLICATE KEY UPDATE svalue = VALUES(svalue)', [$sid, 'backup.' . $k, $st]);
    }
    public static function config(?int $sid = null): array
    {
        $sid ??= Tenant::require(); $g = fn($k, $d = '') => self::sget($sid, $k) ?? $d;
        return ['cloud_enabled' => $g('cloud_enabled', '0') === '1', 'endpoint' => $g('endpoint'), 'region' => $g('region', 'us-east-1'), 'bucket' => $g('bucket'), 'prefix' => $g('prefix', 'madrasati'),
            'has_key' => self::sget($sid, 'access_key', true) !== null, 'has_secret' => self::sget($sid, 'secret_key', true) !== null, 'auto' => $g('auto', 'off'), 'keep' => (int)$g('keep', (string)self::KEEP_DEFAULT), 'last_auto' => $g('last_auto'), 'files' => $g('files', '1') === '1'];
    }
    public static function saveConfig(array $d): void
    {
        Auth::authorize('backup.manage'); $sid = Tenant::require(); $t = fn(string $k) => trim((string)($d[$k] ?? ''));
        $auto = $t('auto') ?: 'off'; if (!in_array($auto, ['off', 'daily', 'weekly'], true)) { throw new HttpException(422, 'جدولة غير صالحة', ['auto' => 'غير صالحة']); }
        $keep = (int)($d['keep'] ?? self::KEEP_DEFAULT); if ($keep < 1 || $keep > 60) { throw new HttpException(422, 'عدد النسخ المحفوظة بين 1 و60', ['keep' => 'غير صالح']); }
        $cloud = !empty($d['cloud_enabled']) && $d['cloud_enabled'] !== '0';
        if ($cloud) {
            $ep = rtrim($t('endpoint'), '/'); UrlGuard::check($ep);
            if (!preg_match('/^[a-z0-9][a-z0-9.\-]{1,61}[a-z0-9]$/i', $t('bucket'))) { throw new HttpException(422, 'اسم الحاوية (Bucket) غير صالح', ['bucket' => 'غير صالح']); }
            if (!preg_match('/^[a-z0-9\-]{2,30}$/i', $t('region') ?: 'us-east-1')) { throw new HttpException(422, 'المنطقة (Region) غير صالحة', ['region' => 'غير صالحة']); }
            if ((self::sget($sid, 'access_key', true) === null && $t('access_key') === '') || (self::sget($sid, 'secret_key', true) === null && $t('secret_key') === '')) { throw new HttpException(422, 'مفتاح الوصول والمفتاح السري مطلوبان', ['access_key' => 'مطلوب']); }
            self::sput($sid, 'endpoint', $ep); self::sput($sid, 'region', $t('region') ?: 'us-east-1'); self::sput($sid, 'bucket', $t('bucket')); self::sput($sid, 'prefix', trim($t('prefix'), '/') ?: 'madrasati');
            if ($t('access_key') !== '') { self::sput($sid, 'access_key', $t('access_key'), true); } if ($t('secret_key') !== '') { self::sput($sid, 'secret_key', $t('secret_key'), true); }
        }
        if (!empty($d['clear_keys'])) { self::sput($sid, 'access_key', null, true); self::sput($sid, 'secret_key', null, true); }
        self::sput($sid, 'cloud_enabled', $cloud ? '1' : '0'); self::sput($sid, 'auto', $auto); self::sput($sid, 'keep', (string)$keep); self::sput($sid, 'files', !empty($d['files']) && $d['files'] !== '0' ? '1' : '0');
        AuditService::log('backup.settings', 'school', $sid, null, ['cloud' => $cloud, 'auto' => $auto, 'keep' => $keep, 'bucket' => $cloud ? $t('bucket') : null]);
    }
    private static function client(int $sid): S3Client
    {
        $c = self::config($sid); $ak = self::sget($sid, 'access_key', true); $sk = self::sget($sid, 'secret_key', true);
        if (!$c['endpoint'] || !$c['bucket'] || !$ak || !$sk) { throw new HttpException(422, 'التخزين السحابي غير مضبوط'); }
        return new S3Client($c['endpoint'], $c['region'], $c['bucket'], $ak, $sk);
    }
    public static function testCloud(): void
    {
        Auth::authorize('backup.manage'); $sid = Tenant::require();
        try { self::client($sid)->check(); } catch (HttpException $e) { throw $e; } catch (\Throwable $e) { throw new HttpException(422, 'فشل الاتصال بالتخزين السحابي: ' . $e->getMessage()); }
        AuditService::log('backup.cloud_test', 'school', $sid, null, []);
    }
    /** يرفع نسخة موجودة للسحابة ويحدّث حالتها. */
    public static function upload(int $backupId, ?int $sid = null): void
    {
        $sid ??= Tenant::require(); $b = DB::one('SELECT * FROM school_backups WHERE id = ? AND school_id = ?', [$backupId, $sid]) ?? throw new HttpException(404, 'النسخة غير موجودة');
        $file = self::dir($sid) . '/' . $b['filename']; if (!is_file($file)) { throw new HttpException(404, 'ملف النسخة غير موجود'); }
        try { $c = self::config($sid); self::client($sid)->put($c['prefix'] . '/school-' . $sid . '/' . $b['filename'], $file); DB::query("UPDATE school_backups SET cloud_status = 'ok', cloud_error = NULL, cloud_at = NOW() WHERE id = ?", [$backupId]); }
        catch (\Throwable $e) { DB::query("UPDATE school_backups SET cloud_status = 'failed', cloud_error = ? WHERE id = ?", [mb_substr($e->getMessage(), 0, 250), $backupId]); throw new HttpException(502, 'فشل الرفع إلى السحابة: ' . $e->getMessage()); }
    }

    // ---------- الواجهة ----------
    public static function list(): array { Auth::authorize('backup.manage'); return DB::all('SELECT * FROM school_backups WHERE school_id = ? ORDER BY id DESC LIMIT 100', [Tenant::require()]); }
    public static function makeNow(array $d): array
    {
        Auth::authorize('backup.manage'); $sid = Tenant::require(); $b = self::create($sid, 'manual', (string)($d['password'] ?? ''), !isset($d['with_files']) || $d['with_files'] !== '0', (int)Auth::id());
        AuditService::log('backup.create', 'school', $sid, null, ['file' => $b['filename'], 'size' => $b['size_bytes'], 'encrypted' => (bool)$b['encrypted']]);
        if (!empty($d['to_cloud']) && $d['to_cloud'] !== '0') { self::upload((int)$b['id'], $sid); $b = DB::one('SELECT * FROM school_backups WHERE id = ?', [$b['id']]); }
        self::prune($sid); return $b;
    }
    public static function uploadNow(int $id): void { Auth::authorize('backup.manage'); self::upload($id, Tenant::require()); AuditService::log('backup.upload', 'school', Tenant::require(), null, ['backup' => $id]); }
    /** مسار الملف للتنزيل بعد التحقق من الصلاحية والمدرسة. */
    public static function downloadPath(int $id): array
    {
        Auth::authorize('backup.manage'); $sid = Tenant::require(); $b = DB::one('SELECT * FROM school_backups WHERE id = ? AND school_id = ?', [$id, $sid]) ?? throw new HttpException(404, 'النسخة غير موجودة');
        $p = self::dir($sid) . '/' . $b['filename']; if (!is_file($p) || !preg_match('/^school-\d+-\d{8}-\d{6}\.zip$/', $b['filename'])) { throw new HttpException(404, 'الملف غير موجود'); }
        AuditService::log('backup.download', 'school', $sid, null, ['file' => $b['filename']]); return [$p, $b['filename']];
    }
    public static function remove(int $id): void
    {
        Auth::authorize('backup.manage'); $sid = Tenant::require(); $b = DB::one('SELECT * FROM school_backups WHERE id = ? AND school_id = ?', [$id, $sid]) ?? throw new HttpException(404, 'النسخة غير موجودة');
        @unlink(self::dir($sid) . '/' . basename($b['filename'])); DB::query('DELETE FROM school_backups WHERE id = ?', [$id]); AuditService::log('backup.delete', 'school', $sid, null, ['file' => $b['filename']]);
    }
    /** يُبقي آخر N نسخة محلية. */
    public static function prune(int $sid): void
    {
        $keep = self::config($sid)['keep']; $rows = DB::all('SELECT id, filename FROM school_backups WHERE school_id = ? ORDER BY id DESC', [$sid]);
        foreach (array_slice($rows, $keep) as $r) { @unlink(self::dir($sid) . '/' . basename($r['filename'])); DB::query('DELETE FROM school_backups WHERE id = ?', [$r['id']]); }
    }
    /** للمجدول (cron): ينشئ نسخًا تلقائية للمدارس المستحقة. @return list<string> سجل */
    public static function runDue(?\DateTimeImmutable $now = null): array
    {
        $now ??= new \DateTimeImmutable(); $log = [];
        foreach (DB::all("SELECT DISTINCT school_id FROM settings WHERE skey = 'backup.auto' AND svalue IN ('daily','weekly') AND school_id IS NOT NULL") as $r) {
            $sid = (int)$r['school_id']; $c = self::config($sid);
            if (!DB::value("SELECT 1 FROM schools WHERE id = ? AND status = 'active' AND deleted_at IS NULL", [$sid])) { continue; }
            $last = $c['last_auto'] ? strtotime($c['last_auto']) : 0; $gap = $c['auto'] === 'weekly' ? 6.5 * 86400 : 0.9 * 86400; if ($now->getTimestamp() - $last < $gap) { continue; }
            try {
                $b = self::create($sid, 'auto', null, $c['files']); self::sput($sid, 'last_auto', $now->format('Y-m-d H:i:s'));
                if ($c['cloud_enabled']) { try { self::upload((int)$b['id'], $sid); } catch (\Throwable $e) { $log[] = "مدرسة $sid: فشل الرفع السحابي — " . $e->getMessage(); } }
                self::prune($sid); $log[] = "مدرسة $sid: تم ({$b['filename']})";
            } catch (\Throwable $e) { $log[] = "مدرسة $sid: فشل — " . $e->getMessage(); }
        }
        return $log;
    }
}
