<?php
declare(strict_types=1);
namespace App\Services;

final class PasswordHasher
{
    public static function hash(string $plain): string
    {
        return defined('PASSWORD_ARGON2ID') && in_array('argon2id', password_algos(), true)
            ? password_hash($plain, PASSWORD_ARGON2ID) : password_hash($plain, PASSWORD_BCRYPT, ['cost' => 12]);
    }
    /** كلمة مرور عشوائية غير معلنة (لحسابات OTP فقط): تجزئة رخيصة لأنها طويلة وعشوائية. */
    public static function hashRandom(): string { return password_hash(bin2hex(random_bytes(16)) . 'aA1', PASSWORD_BCRYPT, ['cost' => 4]); }
    public static function verify(string $plain, string $hash): bool { return password_verify($plain, $hash); }
    public static function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, in_array('argon2id', password_algos(), true) ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT);
    }
    /** سياسة كلمة المرور: 8 أحرف على الأقل وتحوي حرفًا ورقمًا. يعيد رسالة الخطأ أو null. */
    public static function validate(string $p): ?string
    {
        if (mb_strlen($p) < 8) { return 'كلمة المرور يجب ألا تقل عن 8 أحرف'; }
        if (!preg_match('/\p{L}/u', $p) || !preg_match('/\d/', $p)) { return 'كلمة المرور يجب أن تحتوي على حرف ورقم'; }
        return null;
    }
}
