<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\{DB, Env, HttpException};
use App\Middleware\RateLimitMiddleware;

/**
 * استعادة كلمة المرور برمز تحقق (OTP) يصل بالبريد: طلب الرمز ← إدخال الرمز وكلمة جديدة.
 * مبادئ: ردّ موحّد (لا يكشف وجود الحساب)، رمز 6 أرقام مجزّأ بـ HMAC، صلاحية 15 دقيقة، 5 محاولات، رمز واحد فعّال، قيود معدل لكل بريد وIP، وإشعار بالتغيير.
 */
final class PasswordCodeService
{
    public const TTL_MIN = 15; public const MAX_TRIES = 5; private const PER_HOUR = 5;
    private static function hash(string $code, string $email): string { return hash_hmac('sha256', $email . '|' . trim($code), (string)Env::get('APP_ENC_KEY', 'k')); }

    /** يرسل الرمز إن وُجد حساب فعّال بهذا البريد. لا يرمي ولا يكشف شيئًا للمستدعي الخارجي إلا تجاوز المعدل. */
    public static function request(string $emailRaw, string $ip): void
    {
        $email = mb_strtolower(trim($emailRaw));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { throw new HttpException(422, 'أدخل بريدًا إلكترونيًا صحيحًا', ['email' => 'غير صالح']); }
        if (!RateLimitMiddleware::hit("pwcode:$email", VerificationSettings::int('email.per_hour'), 3600) || !RateLimitMiddleware::hit("pwcode-ip:$ip", 20, 3600)) { throw new HttpException(429, 'تجاوزت عدد الطلبات المسموح بها. حاول بعد ساعة.'); }
        $u = DB::one('SELECT id, full_name, status, school_id, user_type FROM users WHERE email = ? AND deleted_at IS NULL', [$email]);
        if (!$u || $u['status'] !== 'active') { SecurityLog::record('login_failed', 200, 'طلب رمز استعادة لبريد غير مسجّل/معطّل', null, null, null, null, $ip); return; }
        $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        DB::query("UPDATE email_codes SET used_at = NOW() WHERE user_id = ? AND purpose = 'reset' AND used_at IS NULL", [$u['id']]);
        DB::query("INSERT INTO email_codes (user_id, email, purpose, code_hash, expires_at, ip) VALUES (?,?, 'reset', ?, ?, ?)", [$u['id'], $email, self::hash($code, $email), date('Y-m-d H:i:s', time() + VerificationSettings::int('email.ttl') * 60), $ip]);
        $vars = ['code' => $code, 'minutes' => VerificationSettings::int('email.ttl'), 'name' => $u['full_name']]; $body = VerificationSettings::render(VerificationSettings::text('email.template'), $vars);
        if (!EmailService::send($email, VerificationSettings::render(VerificationSettings::text('email.subject'), $vars), $body)) { SecurityLog::record('login_failed', 500, 'تعذّر إرسال بريد الاستعادة: ' . (EmailService::$last['error'] ?? ''), null, null, $u['school_id'] ? (int)$u['school_id'] : null, (int)$u['id'], $ip); }
    }

    /** يتحقق من الرمز ويضبط كلمة المرور الجديدة. */
    public static function reset(string $emailRaw, string $code, string $new, string $ip): void
    {
        $email = mb_strtolower(trim($emailRaw)); $bad = new HttpException(422, 'الرمز غير صحيح أو منتهي', ['code' => 'غير صحيح']);
        if (!RateLimitMiddleware::hit("pwreset-ip:$ip", 30, 3600)) { throw new HttpException(429, 'محاولات كثيرة. حاول لاحقًا.'); }
        if ($err = PasswordHasher::validate($new)) { throw new HttpException(422, $err, ['password' => $err]); }
        $row = DB::one("SELECT * FROM email_codes WHERE email = ? AND purpose = 'reset' AND used_at IS NULL ORDER BY id DESC LIMIT 1", [$email]);
        if (!$row || strtotime($row['expires_at']) < time() || (int)$row['attempts'] >= VerificationSettings::int('email.tries')) { throw $bad; }
        DB::query('UPDATE email_codes SET attempts = attempts + 1 WHERE id = ?', [$row['id']]);
        if (!hash_equals($row['code_hash'], self::hash($code, $email))) { SecurityLog::record('login_failed', 422, 'رمز استعادة خاطئ', null, null, null, (int)$row['user_id'], $ip); throw $bad; }
        $u = DB::one('SELECT id, full_name, status, school_id FROM users WHERE id = ? AND deleted_at IS NULL', [$row['user_id']]); if (!$u || $u['status'] !== 'active') { throw $bad; }
        DB::transaction(function () use ($row, $u, $new) {
            DB::query('UPDATE email_codes SET used_at = NOW() WHERE id = ?', [$row['id']]);
            DB::query('UPDATE users SET password_hash = ?, must_change_password = 0, failed_attempts = 0, locked_until = NULL WHERE id = ?', [PasswordHasher::hash($new), $u['id']]);
            DB::query('UPDATE password_resets SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL', [$u['id']]);   // تُبطَل روابط الاستعادة القديمة
            AuditService::log('password_reset', 'user', (int)$u['id'], null, ['via' => 'email_code'], $u['school_id'] ? (int)$u['school_id'] : null);
        });
        EmailService::send($email, 'تم تغيير كلمة المرور — ' . Brand::name(), "مرحبًا {$u['full_name']}،\n\nتم تغيير كلمة مرور حسابك للتو. إن لم تكن أنت فتواصل مع إدارة المدرسة فورًا.");
    }
}
