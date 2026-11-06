<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\{Auth, HttpException};
use App\Services\Sms\{HttpSmsGateway, SmsConfig, SmsFactory};

/** إعداد مزودي الرسائل (SMS وواتساب) من لوحة مشغّل المنصة (صلاحية platform.settings). المفاتيح السرية لا تُعرض ولا تدخل التدقيق. */
final class SmsSettingsService
{
    private static function ch(string $ch): string { if (!isset(SmsConfig::CHANNELS[$ch])) { throw new HttpException(404, 'قناة غير معروفة'); } return $ch; }
    public static function current(string $ch = 'sms'): array
    {
        Auth::authorize('platform.settings'); self::ch($ch);
        return ['channel' => $ch, 'label' => SmsConfig::CHANNELS[$ch]['label'], 'driver' => SmsConfig::driver($ch), 'http_url' => SmsConfig::url($ch), 'http_method' => SmsConfig::method($ch), 'http_body' => SmsConfig::body($ch),
            'success_code' => SmsConfig::successCode($ch), 'success_match' => SmsConfig::successMatch($ch),
            'sender' => SmsConfig::sender($ch), 'has_api_key' => SmsConfig::hasSecret($ch, 'api_key'), 'has_username' => SmsConfig::hasSecret($ch, 'username'), 'has_password' => SmsConfig::hasSecret($ch, 'password'), 'has_headers' => SmsConfig::headers($ch) !== [], 'header_names' => array_keys(SmsConfig::headers($ch)), 'from_db' => SmsConfig::hasDb($ch)];
    }
    public static function save(array $d, string $ch = 'sms'): void
    {
        Auth::authorize('platform.settings'); self::ch($ch); $p = SmsConfig::CHANNELS[$ch]['p'] . '.';
        $driver = (string)($d['driver'] ?? 'log'); if (!in_array($driver, ['log', 'http'], true)) { throw new HttpException(422, 'نوع المزود غير صالح', ['driver' => 'غير صالح']); }
        $headers = trim((string)($d['http_headers'] ?? ''));
        if ($driver === 'http') {
            $url = trim((string)($d['http_url'] ?? ''));
            if (!preg_match('#^https?://[^\s]+$#i', $url)) { throw new HttpException(422, 'رابط المزود يجب أن يبدأ بـ https:// (أو http://)', ['http_url' => 'غير صالح']); }
            $tpl = trim((string)($d['http_body'] ?? '')); if ($tpl === '' || !str_contains($tpl, '{message}') || !(str_contains($tpl, '{to}') || str_contains($tpl, '{to_digits}'))) { throw new HttpException(422, 'قالب الجسم يجب أن يحوي {message} و{to} (أو {to_digits})', ['http_body' => 'ناقص']); }
            $m = strtoupper((string)($d['http_method'] ?? 'POST')); if (!in_array($m, ['POST', 'GET'], true)) { throw new HttpException(422, 'الطريقة POST أو GET', ['http_method' => 'غير صالحة']); }
            $code = (int)($d['success_code'] ?? 200); if ($code < 100 || $code > 599) { throw new HttpException(422, 'رمز النجاح غير صالح', ['success_code' => 'غير صالح']); }
            if ($headers !== '' && (!is_array(json_decode($headers, true)))) { throw new HttpException(422, 'الترويسات يجب أن تكون JSON صالحًا، مثل {"Authorization":"Bearer ..."}', ['http_headers' => 'JSON غير صالح']); }
            PlatformSettings::set($p . 'http_url', $url); PlatformSettings::set($p . 'http_method', $m); PlatformSettings::set($p . 'http_body', $tpl);
            PlatformSettings::set($p . 'success_code', (string)$code); PlatformSettings::set($p . 'success_match', trim((string)($d['success_match'] ?? '')) ?: null);
            if ($headers !== '') { PlatformSettings::set($p . 'http_headers', $headers, true); }   // الترك فارغًا يُبقي المحفوظ
            if (!empty($d['clear_headers'])) { PlatformSettings::set($p . 'http_headers', null, true); }
        }
        if ($driver === 'http') {
            PlatformSettings::set($p . 'sender', mb_substr(trim((string)($d['sender'] ?? '')), 0, 60) ?: null);
            foreach (['api_key', 'username', 'password'] as $k) {
                $v = trim((string)($d[$k] ?? '')); if ($v !== '') { PlatformSettings::set($p . $k, mb_substr($v, 0, 500), true); }
                if (!empty($d['clear_' . $k])) { PlatformSettings::set($p . $k, null, true); }
            }
        }
        PlatformSettings::set($p . 'driver', $driver);
        AuditService::log('platform.' . $ch . '_settings', 'platform', null, null, ['driver' => $driver, 'host' => $driver === 'http' ? parse_url((string)($d['http_url'] ?? ''), PHP_URL_HOST) : null, 'headers_changed' => $headers !== '']);
    }
    /** يرسل رسالة اختبار بالإعداد المحفوظ ويعيد تفاصيل التشخيص. */
    public static function test(string $phone, string $ch = 'sms'): array
    {
        Auth::authorize('platform.settings'); self::ch($ch);
        $p = Phone::normalize($phone); if (!preg_match('/^\+\d{9,15}$/', $p)) { throw new HttpException(422, 'رقم الهاتف غير صالح', ['phone' => 'غير صالح']); }
        $ok = SmsFactory::makeFor($ch)->send($p, 'رسالة اختبار من منصة مدرستي. إن وصلتك فالمزود يعمل.');
        AuditService::log('platform.' . $ch . '_test', 'platform', null, null, ['ok' => $ok, 'driver' => SmsConfig::driver($ch)]);
        $log = $ch === 'whatsapp' ? 'whatsapp.log' : 'sms.log';
        return ['ok' => $ok, 'driver' => SmsConfig::driver($ch), 'detail' => SmsConfig::driver($ch) === 'http' ? HttpSmsGateway::$last : ['note' => "الوضع التجريبي: الرسالة كُتبت في storage/logs/$log ولم تُرسل فعليًا"]];
    }
}
