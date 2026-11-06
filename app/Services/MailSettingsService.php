<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\{Auth, HttpException};

/** إعداد البريد (SMTP) من لوحة المشغّل. كلمة مرور SMTP مشفّرة ولا تُعرض ولا تدخل التدقيق. */
final class MailSettingsService
{
    private static function g(string $k, string $d = '', bool $secret = false): string { return (string)PlatformSettings::get('mail.' . $k, $d, $secret); }
    public static function current(): array
    {
        Auth::authorize('platform.settings');
        return ['driver' => EmailService::driver(), 'host' => self::g('host'), 'port' => self::g('port', '587'), 'encryption' => self::g('encryption', 'tls'), 'username' => self::g('username'), 'has_password' => PlatformSettings::has('mail.password'),
            'from_email' => self::g('from_email'), 'from_name' => self::g('from_name'), 'allow_self_signed' => self::g('allow_self_signed') === '1', 'from_db' => PlatformSettings::has('mail.driver')];
    }
    public static function save(array $d): void
    {
        Auth::authorize('platform.settings'); $t = fn(string $k) => trim((string)($d[$k] ?? ''));
        $driver = $t('driver'); if (!in_array($driver, ['log', 'smtp', 'mail'], true)) { throw new HttpException(422, 'طريقة غير صالحة', ['driver' => 'غير صالحة']); }
        $from = $t('from_email'); if ($from !== '' && !filter_var($from, FILTER_VALIDATE_EMAIL)) { throw new HttpException(422, 'بريد المرسل غير صالح', ['from_email' => 'غير صالح']); }
        if ($driver !== 'log' && $from === '') { throw new HttpException(422, 'بريد المرسل مطلوب', ['from_email' => 'مطلوب']); }
        if ($driver === 'smtp') {
            $host = $t('host'); if (!preg_match('/^[A-Za-z0-9.-]{3,190}$/', $host)) { throw new HttpException(422, 'خادم SMTP غير صالح', ['host' => 'غير صالح']); }
            $port = (int)$t('port'); if ($port < 1 || $port > 65535) { throw new HttpException(422, 'المنفذ غير صالح', ['port' => 'غير صالح']); }
            $enc = $t('encryption') ?: 'tls'; if (!in_array($enc, ['ssl', 'tls', 'none'], true)) { throw new HttpException(422, 'التشفير ssl أو tls أو none', ['encryption' => 'غير صالح']); }
            PlatformSettings::set('mail.host', $host); PlatformSettings::set('mail.port', (string)$port); PlatformSettings::set('mail.encryption', $enc); PlatformSettings::set('mail.username', $t('username'));
            if ($t('password') !== '') { PlatformSettings::set('mail.password', $t('password'), true); } if (!empty($d['clear_password'])) { PlatformSettings::set('mail.password', null, true); }
            PlatformSettings::set('mail.allow_self_signed', !empty($d['allow_self_signed']) && $d['allow_self_signed'] !== '0' ? '1' : '0');
        }
        PlatformSettings::set('mail.from_email', $from); PlatformSettings::set('mail.from_name', mb_substr($t('from_name'), 0, 80)); PlatformSettings::set('mail.driver', $driver);
        AuditService::log('platform.mail_settings', 'platform', null, null, ['driver' => $driver, 'host' => $driver === 'smtp' ? $t('host') : null, 'password_changed' => $t('password') !== '']);
    }
    public static function test(string $to): array
    {
        Auth::authorize('platform.settings'); if (!filter_var(trim($to), FILTER_VALIDATE_EMAIL)) { throw new HttpException(422, 'بريد غير صالح', ['to' => 'غير صالح']); }
        if (EmailService::driver() === 'smtp') {
            if (self::g('username') === '') { throw new HttpException(422, 'أدخل اسم مستخدم SMTP واحفظ الإعدادات قبل الاختبار.', ['username' => 'مطلوب']); }
            if (self::g('password', '', true) === '') { throw new HttpException(422, 'أدخل كلمة مرور SMTP أو كلمة مرور التطبيق واحفظ الإعدادات قبل الاختبار.', ['password' => 'مطلوبة']); }
        }
        $ok = EmailService::send($to, 'رسالة اختبار من ' . Brand::name(), "هذه رسالة اختبار من منصة " . Brand::name() . ".\nإن وصلتك فإعداد البريد يعمل.");
        AuditService::log('platform.mail_test', 'platform', null, null, ['ok' => $ok, 'driver' => EmailService::driver()]);
        return ['ok' => $ok] + EmailService::$last;
    }
}
