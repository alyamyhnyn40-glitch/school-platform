<?php
declare(strict_types=1);
namespace App\Services\Sms;

use App\Core\Env;
use App\Services\PlatformSettings;

/**
 * إعداد مزود الرسائل لقناة (sms | whatsapp): ما حفظه مشغّل المنصة في لوحة التحكم له الأولوية، وإلا يُقرأ من .env (للتوافق).
 * مفاتيح .env: SMS_* للرسائل القصيرة، و WA_* لواتساب.
 */
final class SmsConfig
{
    public const CHANNELS = ['sms' => ['p' => 'sms', 'env' => 'SMS', 'label' => 'الرسائل القصيرة (SMS)'], 'whatsapp' => ['p' => 'wa', 'env' => 'WA', 'label' => 'واتساب']];
    private static function db(string $ch, string $k, bool $secret = false): ?string
    {
        try { return PlatformSettings::get(self::CHANNELS[$ch]['p'] . '.' . $k, null, $secret); } catch (\Throwable) { return null; }   // قبل تشغيل migrations أو بلا قاعدة
    }
    private static function env(string $ch, string $k, mixed $d): mixed { return Env::get(self::CHANNELS[$ch]['env'] . '_' . $k, $d); }
    public static function driver(string $ch = 'sms'): string { return self::db($ch, 'driver') ?? (string)self::env($ch, 'DRIVER', 'log'); }
    public static function url(string $ch = 'sms'): string { return self::db($ch, 'http_url') ?? (string)self::env($ch, 'HTTP_URL', ''); }
    public static function method(string $ch = 'sms'): string { return strtoupper(self::db($ch, 'http_method') ?? (string)self::env($ch, 'HTTP_METHOD', 'POST')); }
    public static function headers(string $ch = 'sms'): array { return json_decode(self::db($ch, 'http_headers', true) ?? (string)self::env($ch, 'HTTP_HEADERS', '{}'), true) ?: []; }
    public static function body(string $ch = 'sms'): string { return self::db($ch, 'http_body') ?? (string)self::env($ch, 'HTTP_BODY', '{"to":"{to}","message":"{message}"}'); }
    public static function successCode(string $ch = 'sms'): int { return (int)(self::db($ch, 'success_code') ?? self::env($ch, 'HTTP_SUCCESS_CODE', 200)); }
    public static function successMatch(string $ch = 'sms'): string { return self::db($ch, 'success_match') ?? ''; }
    /** بيانات اعتماد المزود: sender نص عادي، والبقية مشفّرة. تُستعمل في القوالب كـ {sender} {api_key} {username} {password} {base64_auth}. */
    public static function sender(string $ch = 'sms'): string { return self::db($ch, 'sender') ?? ''; }
    public static function secret(string $ch, string $k): string { return self::db($ch, $k, true) ?? ''; }
    public static function hasSecret(string $ch, string $k): bool { try { return PlatformSettings::has(self::CHANNELS[$ch]['p'] . '.' . $k); } catch (\Throwable) { return false; } }
    /** @return array<string,string> قيم الرموز غير الخاصة بالرسالة */
    public static function tokens(string $ch = 'sms'): array
    {
        $u = self::secret($ch, 'username'); $pw = self::secret($ch, 'password');
        return ['{sender}' => self::sender($ch), '{api_key}' => self::secret($ch, 'api_key'), '{username}' => $u, '{password}' => $pw, '{base64_auth}' => base64_encode($u . ':' . $pw)];
    }
    public static function hasDb(string $ch = 'sms'): bool { try { return PlatformSettings::has(self::CHANNELS[$ch]['p'] . '.driver'); } catch (\Throwable) { return false; } }
    /** هل القناة مفعّلة بمزود حقيقي؟ */
    public static function enabled(string $ch): bool { return self::driver($ch) === 'http' && self::url($ch) !== ''; }
}
