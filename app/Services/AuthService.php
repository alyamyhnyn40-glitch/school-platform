<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\{Auth, DB, Env, HttpException, Session};
use App\Middleware\RateLimitMiddleware;
use App\Services\Sms\SmsFactory;

final class AuthService
{
    public const MAX_FAILS = 5;
    public const LOCK_MINUTES = 15;
    public const OTP_TTL_MIN = 5;
    public const OTP_MAX_TRIES = 5;
    public const OTP_PER_HOUR = 3;

    public static function findByIdentifier(string $id): ?array
    {
        $id = trim($id);
        if (filter_var($id, FILTER_VALIDATE_EMAIL)) { return DB::one('SELECT * FROM users WHERE email = ? AND deleted_at IS NULL', [mb_strtolower($id)]) ?: null; }
        $row = DB::one('SELECT * FROM users WHERE phone = ? AND deleted_at IS NULL', [Phone::normalize($id)]);
        // اسم المستخدم (رقم قيد الطالب) كخيار ثالث
        return $row ?: (DB::one('SELECT * FROM users WHERE username = ? AND deleted_at IS NULL', [mb_strtolower($id)]) ?: null);
    }

    /** دخول بكلمة مرور. يرمي HttpException(422/423) برسائل عربية عامة (لا تكشف وجود الحساب). */
    public static function attemptPassword(string $identifier, string $password, string $ip): array
    {
        $user = self::findByIdentifier($identifier);
        $generic = new HttpException(422, 'بيانات الدخول غير صحيحة');
        if (!$user) { self::record($identifier, $ip, false); throw $generic; }
        self::assertUsable($user);
        if (!PasswordHasher::verify($password, $user['password_hash'])) {
            $fails = (int)$user['failed_attempts'] + 1;
            $lock = $fails >= self::MAX_FAILS ? date('Y-m-d H:i:s', time() + self::LOCK_MINUTES * 60) : null;
            DB::query('UPDATE users SET failed_attempts = ?, locked_until = ? WHERE id = ?', [$lock ? 0 : $fails, $lock, $user['id']]);
            self::record($identifier, $ip, false);
            if ($lock) { SecurityLog::record('account_locked', 423, 'معرّف: ' . self::mask($identifier), null, null, (int)$user['school_id'] ?: null, (int)$user['id'], $ip); throw new HttpException(423, 'تم قفل الحساب مؤقتًا بعد محاولات فاشلة متعددة. حاول بعد ' . self::LOCK_MINUTES . ' دقيقة.'); }
            throw $generic;
        }
        if (PasswordHasher::needsRehash($user['password_hash'])) {
            DB::query('UPDATE users SET password_hash = ? WHERE id = ?', [PasswordHasher::hash($password), $user['id']]);
        }
        return self::completeLogin($user, $identifier, $ip);
    }

    private static function assertUsable(array $user): void
    {
        if ($user['status'] !== 'active') { throw new HttpException(403, 'هذا الحساب معطّل. تواصل مع إدارة المدرسة.'); }
        if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
            throw new HttpException(423, 'الحساب مقفل مؤقتًا. حاول لاحقًا.');
        }
        if ($user['school_id'] !== null) {
            $st = DB::value('SELECT status FROM schools WHERE id = ? AND deleted_at IS NULL', [$user['school_id']]);
            // ولي الأمر متعدد المدارس: يكفي أن تكون إحدى مدارسه فعّالة
            if ($st !== 'active' && !($user['user_type'] === 'guardian' && self::guardianSchools((int)$user['id']))) { throw new HttpException(403, 'حساب المدرسة غير فعّال. تواصل مع إدارة المنصة.'); }
        }
    }

    // ---------- ولي الأمر متعدد المدارس ----------
    /** المدارس الفعّالة التي لولي الأمر فيها طلاب مرتبطون (وبحساب واحد). */
    public static function guardianSchools(int $userId): array
    {
        return DB::all("SELECT DISTINCT sc.id, sc.name FROM guardians g JOIN student_guardians sg ON sg.guardian_id = g.id JOIN schools sc ON sc.id = sg.school_id
            WHERE g.user_id = ? AND g.deleted_at IS NULL AND sc.status = 'active' AND sc.deleted_at IS NULL ORDER BY sc.name", [$userId]);
    }
    /** يمنح دور «ولي أمر» في المدرسة (بعد التحقق من الارتباط) ليستقيم فحص الصلاحيات. */
    private static function ensureGuardianRole(int $userId, int $schoolId): void
    {
        DB::query('INSERT IGNORE INTO user_roles (user_id, role_id) SELECT ?, id FROM roles WHERE school_id = ? AND code = "guardian"', [$userId, $schoolId]);
    }
    /** يبدّل مدرسة ولي الأمر الحالية في الجلسة. لا يقبل إلا مدرسة له فيها ارتباط فعلي بطالب. */
    public static function switchSchool(int $schoolId): void
    {
        if (Auth::type() !== 'guardian') { throw new HttpException(403, 'التبديل بين المدارس لأولياء الأمور فقط'); }
        $uid = (int)Auth::id();
        if (!in_array($schoolId, array_map(fn($s) => (int)$s['id'], self::guardianSchools($uid)), true)) { throw new HttpException(403, 'ليس لك أبناء في هذه المدرسة'); }
        self::ensureGuardianRole($uid, $schoolId);
        $u = Auth::user(); $u['school_id'] = $schoolId;
        Session::set('user', $u); Session::set('school_id', $schoolId);
        Session::set('perms', self::permissionsFor(['id' => $uid, 'user_type' => 'guardian', 'school_id' => $schoolId]));
        AuditService::log('guardian.switch_school', 'user', $uid, null, ['school_id' => $schoolId], $schoolId);
    }
    /** بعد الدخول: إن كانت مدرسة الحساب الأصلية غير فعّالة ينتقل إلى أول مدرسة فعّالة له. */
    private static function pickGuardianSchool(array $user): void
    {
        if ($user['user_type'] !== 'guardian') { return; }
        $mine = array_map(fn($s) => (int)$s['id'], self::guardianSchools((int)$user['id']));
        if ($mine && !in_array((int)$user['school_id'], $mine, true)) { self::switchSchool($mine[0]); }
    }

    private static function completeLogin(array $user, string $identifier, string $ip): array
    {
        DB::query('UPDATE users SET failed_attempts = 0, locked_until = NULL, last_login_at = NOW() WHERE id = ?', [$user['id']]);
        self::record($identifier, $ip, true);
        Auth::login($user, self::permissionsFor($user));
        self::pickGuardianSchool($user);
        AuditService::log('login', 'user', (int)$user['id'], null, null, $user['school_id'] !== null ? (int)$user['school_id'] : null);
        return $user;
    }

    public static function permissionsFor(array $user): array
    {
        if ($user['user_type'] === 'platform') { return ['*']; }
        $rows = DB::all('SELECT DISTINCT p.code FROM user_roles ur JOIN role_permissions rp ON rp.role_id = ur.role_id
                         JOIN permissions p ON p.id = rp.permission_id JOIN roles r ON r.id = ur.role_id
                         WHERE ur.user_id = ? AND r.school_id = ?', [$user['id'], $user['school_id']]);
        $perms = array_flip(array_column($rows, 'code'));
        // صلاحيات مباشرة للمستخدم فوق أدواره: allow تُضاف، deny تُحجب حتى لو منحها دور
        try {
            foreach (DB::all('SELECT p.code, up.effect FROM user_permissions up JOIN permissions p ON p.id = up.permission_id WHERE up.user_id = ? AND up.school_id = ?', [$user['id'], $user['school_id']]) as $x) {
                if ($x['effect'] === 'allow') { $perms[$x['code']] = true; } else { unset($perms[$x['code']]); }
            }
        } catch (\PDOException) {}   // قبل تشغيل الترقية
        return array_keys($perms);
    }

    private static function record(string $id, string $ip, bool $ok): void
    {
        DB::query('INSERT INTO login_attempts (identifier, ip, success) VALUES (?,?,?)', [mb_substr($id, 0, 190), $ip, (int)$ok]);
        if (!$ok) { SecurityLog::record('login_failed', 422, 'معرّف: ' . self::mask($id), null, null, null, null, $ip); }
    }
    /** إخفاء معرّف الدخول في السجل (أول 3 أحرف فقط). */
    private static function mask(string $id): string { return mb_substr($id, 0, 3) . str_repeat('*', max(0, min(8, mb_strlen($id) - 3))); }

    // ---------- OTP ----------
    public static function requestOtp(string $phoneRaw, string $ip): void
    {
        $phone = Phone::normalize($phoneRaw);
        if (!RateLimitMiddleware::hit("otp-phone:$phone", VerificationSettings::int('sms.per_hour'), 3600)) {
            throw new HttpException(429, 'تم تجاوز عدد الرسائل المسموح بها لهذا الرقم. حاول بعد ساعة.');
        }
        $user = DB::one('SELECT * FROM users WHERE phone = ? AND deleted_at IS NULL', [$phone]);
        // استجابة موحدة سواء وُجد الحساب أم لا (منع تعداد الأرقام)
        if (!$user || $user['status'] !== 'active') { return; }
        self::assertUsable($user);
        $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        DB::query('UPDATE otp_codes SET used_at = NOW() WHERE phone = ? AND used_at IS NULL', [$phone]);
        DB::query('INSERT INTO otp_codes (phone, code_hash, expires_at, ip) VALUES (?,?,?,?)', [
            $phone, hash_hmac('sha256', $code, (string)Env::get('APP_ENC_KEY', 'k')), date('Y-m-d H:i:s', time() + VerificationSettings::int('sms.ttl') * 60), $ip]);
        SmsFactory::make()->send($phone, VerificationSettings::render(VerificationSettings::text('sms.template'), ['code' => $code, 'minutes' => VerificationSettings::int('sms.ttl')]));
    }

    public static function verifyOtp(string $phoneRaw, string $code, string $ip): array
    {
        $phone = Phone::normalize($phoneRaw);
        $bad = new HttpException(422, 'رمز التحقق غير صحيح أو منتهي');
        $row = DB::one('SELECT * FROM otp_codes WHERE phone = ? AND used_at IS NULL ORDER BY id DESC LIMIT 1', [$phone]);
        if (!$row || strtotime($row['expires_at']) < time() || (int)$row['attempts'] >= VerificationSettings::int('sms.tries')) { throw $bad; }
        DB::query('UPDATE otp_codes SET attempts = attempts + 1 WHERE id = ?', [$row['id']]);
        if (!hash_equals($row['code_hash'], hash_hmac('sha256', trim($code), (string)Env::get('APP_ENC_KEY', 'k')))) { self::record($phone, $ip, false); throw $bad; }
        DB::query('UPDATE otp_codes SET used_at = NOW() WHERE id = ?', [$row['id']]);
        $user = DB::one('SELECT * FROM users WHERE phone = ? AND deleted_at IS NULL', [$phone]);
        if (!$user) { throw $bad; }
        self::assertUsable($user);
        return self::completeLogin($user, $phone, $ip);
    }

    // ---------- استعادة كلمة المرور ----------
    /** يعيد الرمز الخام (يُرسل للمستخدم). لا يكشف وجود الحساب للمستدعي الخارجي. */
    public static function requestReset(string $identifier): ?string
    {
        $user = self::findByIdentifier($identifier);
        if (!$user || $user['status'] !== 'active') { return null; }
        $token = bin2hex(random_bytes(24));
        DB::query('INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?,?,?)', [$user['id'], hash('sha256', $token), date('Y-m-d H:i:s', time() + 3600)]);
        $link = rtrim((string)Env::get('APP_URL', ''), '/') . url('password/reset/' . $token);
        $msg = "رابط استعادة كلمة المرور (صالح ساعة): $link";
        if ($user['phone']) { SmsFactory::make()->send($user['phone'], $msg); }
        elseif ($user['email']) { DB::query("INSERT INTO notification_queue (channel, recipient, body) VALUES ('email', ?, ?)", [$user['email'], $msg]); }
        return $token;
    }

    public static function resetPassword(string $token, string $newPassword): void
    {
        if ($err = PasswordHasher::validate($newPassword)) { throw new HttpException(422, $err); }
        $row = DB::one('SELECT * FROM password_resets WHERE token_hash = ? AND used_at IS NULL AND expires_at > NOW()', [hash('sha256', $token)]);
        if (!$row) { throw new HttpException(422, 'رابط الاستعادة غير صالح أو منتهي'); }
        DB::transaction(function () use ($row, $newPassword) {
            DB::query('UPDATE users SET password_hash = ?, must_change_password = 0, failed_attempts = 0, locked_until = NULL WHERE id = ?', [PasswordHasher::hash($newPassword), $row['user_id']]);
            DB::query('UPDATE password_resets SET used_at = NOW() WHERE id = ?', [$row['id']]);
        });
    }

    public static function changePassword(int $userId, ?string $current, string $new): void
    {
        if ($err = PasswordHasher::validate($new)) { throw new HttpException(422, $err); }
        $u = DB::one('SELECT password_hash FROM users WHERE id = ?', [$userId]);
        if ($current !== null && (!$u || !PasswordHasher::verify($current, $u['password_hash']))) { throw new HttpException(422, 'كلمة المرور الحالية غير صحيحة'); }
        DB::query('UPDATE users SET password_hash = ?, must_change_password = 0 WHERE id = ?', [PasswordHasher::hash($new), $userId]);
        AuditService::log('password_change', 'user', $userId);
    }
}
