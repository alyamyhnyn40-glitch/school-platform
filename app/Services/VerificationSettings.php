<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\{Auth, HttpException};

/** إعدادات رموز التحقق (SMS والبريد): الصلاحية والمحاولات والحد بالساعة ونصوص الرسائل. تُضبط من لوحة المشغّل. */
final class VerificationSettings
{
    public const DEFAULTS = [
        'sms.ttl' => 5, 'sms.tries' => 5, 'sms.per_hour' => 3,
        'sms.template' => 'رمز الدخول إلى منصة {app}: {code} (صالح {minutes} دقائق). لا تشاركه مع أحد.',
        'email.ttl' => 15, 'email.tries' => 5, 'email.per_hour' => 5,
        'email.subject' => 'رمز استعادة كلمة المرور — {app}',
        'email.template' => "مرحبًا {name}،\n\nرمز استعادة كلمة المرور في {app}:\n\n    {code}\n\nصالح لمدة {minutes} دقيقة. لا تشاركه مع أحد.\nإن لم تطلب هذا الرمز فتجاهل الرسالة.",
    ];
    private static function g(string $k): string
    {
        try { return PlatformSettings::get('otp.' . $k, null) ?? (string)self::DEFAULTS[$k]; } catch (\Throwable) { return (string)self::DEFAULTS[$k]; }
    }
    public static function int(string $k): int { return max(1, (int)self::g($k)); }
    public static function text(string $k): string { return self::g($k); }
    /** يملأ {code} {minutes} {app} {name} في القالب. */
    public static function render(string $tpl, array $v): string
    {
        $map = ['{app}' => Brand::name()]; foreach ($v as $k => $x) { $map['{' . $k . '}'] = (string)$x; }
        return strtr($tpl, $map);
    }
    public static function current(): array
    {
        Auth::authorize('platform.settings'); $o = [];
        foreach (self::DEFAULTS as $k => $_) { $o[$k] = self::g($k); }
        $o['sms_ready'] = \App\Services\Sms\SmsConfig::enabled('sms'); $o['email_ready'] = EmailService::ready();
        return $o;
    }
    public static function save(array $d): void
    {
        Auth::authorize('platform.settings');
        $rng = ['sms.ttl' => [2, 30], 'sms.tries' => [3, 10], 'sms.per_hour' => [1, 20], 'email.ttl' => [5, 60], 'email.tries' => [3, 10], 'email.per_hour' => [1, 20]];
        $lbl = ['sms.ttl' => 'صلاحية رمز SMS', 'sms.tries' => 'محاولات رمز SMS', 'sms.per_hour' => 'حد طلبات SMS بالساعة', 'email.ttl' => 'صلاحية رمز البريد', 'email.tries' => 'محاولات رمز البريد', 'email.per_hour' => 'حد طلبات البريد بالساعة'];
        $vals = [];
        foreach ($rng as $k => [$lo, $hi]) {
            $n = (int)($d[str_replace('.', '_', $k)] ?? self::DEFAULTS[$k]);
            if ($n < $lo || $n > $hi) { throw new HttpException(422, "{$lbl[$k]} بين $lo و$hi", [str_replace('.', '_', $k) => 'خارج النطاق']); }
            $vals[$k] = (string)$n;
        }
        foreach (['sms.template' => 'قالب رسالة SMS', 'email.template' => 'نص البريد'] as $k => $l) {
            $t = trim((string)($d[str_replace('.', '_', $k)] ?? '')); if ($t === '') { $t = (string)self::DEFAULTS[$k]; }
            if (!str_contains($t, '{code}')) { throw new HttpException(422, "$l يجب أن يحوي {code}", [str_replace('.', '_', $k) => 'يلزم {code}']); }
            if (mb_strlen($t) > ($k === 'sms.template' ? 300 : 2000)) { throw new HttpException(422, "$l طويل جدًا", [str_replace('.', '_', $k) => 'طويل']); }
            $vals[$k] = $t;
        }
        $sub = trim((string)($d['email_subject'] ?? '')) ?: (string)self::DEFAULTS['email.subject']; $vals['email.subject'] = mb_substr(str_replace(["\r", "\n"], ' ', $sub), 0, 150);
        foreach ($vals as $k => $v) { PlatformSettings::set('otp.' . $k, $v); }
        AuditService::log('platform.otp_settings', 'platform', null, null, array_intersect_key($vals, $rng));
    }
}
