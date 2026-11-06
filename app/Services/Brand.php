<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\{Auth, HttpException};

/**
 * هوية المنصة وإعداداتها العامة (اسم، شعار، لون، اتصال، خيارات عامة). تُخزَّن في settings (school_id = NULL).
 * القراءة عامة (تستخدمها الواجهات وصفحة الهبوط) بلا أي سر؛ والكتابة لمن يملك platform.settings.
 */
final class Brand
{
    private static ?array $cache = null;
    public const DEFAULTS = ['name' => 'مدرستي', 'name_en' => 'Madrasati', 'tagline' => 'منصة إدارة المدارس والتعليم عن بُعد', 'color' => '#0f766e', 'contact_email' => '', 'contact_phone' => '', 'contact_whatsapp' => '', 'address' => '', 'website' => '', 'footer' => '',
        'allow_school_requests' => '1', 'allow_online_payments' => '1', 'maintenance' => '0', 'maintenance_message' => 'المنصة تحت الصيانة حاليًا. نعود قريبًا.'];
    private const LOGO_MAX = 1048576; private const LOGO_DIM = 512;

    public static function all(): array
    {
        if (self::$cache !== null) { return self::$cache; }
        $o = self::DEFAULTS;
        try { foreach (array_keys($o) as $k) { $v = PlatformSettings::get('brand.' . $k); if ($v !== null && $v !== '') { $o[$k] = $v; } } $o['logo'] = PlatformSettings::get('brand.logo_file'); }
        catch (\Throwable) { $o['logo'] = null; }   // قبل تشغيل migrations
        return self::$cache = $o;
    }
    public static function forget(): void { self::$cache = null; }
    public static function get(string $k): string { return (string)(self::all()[$k] ?? ''); }
    public static function name(): string { return self::get('name') ?: self::DEFAULTS['name']; }
    public static function hasLogo(): bool { return !empty(self::all()['logo']); }
    public static function color(): string { $c = self::get('color'); return preg_match('/^#[0-9a-fA-F]{6}$/', $c) ? strtolower($c) : self::DEFAULTS['color']; }
    public static function enabled(string $k): bool { return self::get($k) === '1'; }
    /** سطر CSS يغيّر لون الهوية في كل الواجهات. */
    public static function css(): string
    {
        $c = self::color(); [$r, $g, $b] = sscanf($c, '#%02x%02x%02x'); $d = sprintf('#%02x%02x%02x', (int)($r * .8), (int)($g * .8), (int)($b * .8));
        return ":root{--c-primary:$c;--c-primary-d:$d;--bs-primary:$c;--bs-primary-rgb:$r,$g,$b;--bs-link-color:$c;--bs-link-hover-color:$d}.btn-primary{--bs-btn-bg:$c;--bs-btn-border-color:$c;--bs-btn-hover-bg:$d;--bs-btn-hover-border-color:$d;--bs-btn-active-bg:$d}";
    }
    public static function logoPath(): ?string
    {
        $n = self::all()['logo'] ?? null; if (!$n || !preg_match('/^[a-f0-9]{32}\.(png|jpg|webp)$/', $n)) { return null; }
        $p = storage_path('uploads/brand/' . $n); return is_file($p) ? $p : null;
    }
    /** اسم المدرسة الحالية للمستخدم المسجّل (أو null لمشغّل المنصة خارج وضع الدعم). */
    public static function schoolName(): ?string
    {
        try { $id = \App\Core\Tenant::id(); if ($id === null) { return null; } } catch (\Throwable) { return null; }
        static $c = []; return $c[$id] ??= (string)\App\Core\DB::value('SELECT name FROM schools WHERE id = ?', [$id]) ?: null;
    }
    /** اسم السنة الدراسية الفعّالة للمدرسة الحالية. */
    public static function activeYear(): ?string
    {
        try { $id = \App\Core\Tenant::id(); if ($id === null) { return null; } } catch (\Throwable) { return null; }
        static $c = []; return $c[$id] ??= (string)\App\Core\DB::value("SELECT name FROM academic_years WHERE school_id = ? AND status = 'active' ORDER BY starts_on DESC LIMIT 1", [$id]) ?: null;
    }
    /** شعار المدرسة الحالية (يُعتمد عند قبول طلب التسجيل). */
    public static function schoolLogoPath(): ?string
    {
        try { if (\App\Core\Tenant::id() === null) { return null; } $n = SettingsService::get('logo_file'); } catch (\Throwable) { return null; }
        if (!$n || !preg_match('/^\d+\.(png|jpg|webp)$/', $n)) { return null; } $p = storage_path('uploads/school-logos/' . $n); return is_file($p) ? $p : null;
    }
    public static function logoMime(): string { return str_ends_with((string)(self::all()['logo'] ?? ''), '.png') ? 'image/png' : (str_ends_with((string)(self::all()['logo'] ?? ''), '.webp') ? 'image/webp' : 'image/jpeg'); }

    public static function save(array $d, ?array $logo = null, bool $removeLogo = false): void
    {
        Auth::authorize('platform.settings'); $txt = fn(string $k, int $max) => mb_substr(trim((string)($d[$k] ?? '')), 0, $max);
        $name = $txt('name', 80); if (mb_strlen($name) < 2) { throw new HttpException(422, 'اسم المنصة مطلوب', ['name' => 'مطلوب']); }
        $color = $txt('color', 7) ?: '#0f766e'; if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) { throw new HttpException(422, 'اللون بصيغة #RRGGBB', ['color' => 'غير صالح']); }
        $email = $txt('contact_email', 190); if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) { throw new HttpException(422, 'بريد غير صالح', ['contact_email' => 'غير صالح']); }
        $web = $txt('website', 190); if ($web !== '' && !preg_match('#^https?://[^\s]+$#i', $web)) { throw new HttpException(422, 'الموقع يبدأ بـ https://', ['website' => 'غير صالح']); }
        foreach (['contact_phone', 'contact_whatsapp'] as $k) { $v = $txt($k, 30); if ($v !== '' && !preg_match('/^[+0-9 ()-]{6,30}$/', $v)) { throw new HttpException(422, 'رقم غير صالح', [$k => 'غير صالح']); } }
        $vals = ['name' => $name, 'name_en' => $txt('name_en', 80), 'tagline' => $txt('tagline', 160), 'color' => strtolower($color), 'contact_email' => $email, 'contact_phone' => $txt('contact_phone', 30), 'contact_whatsapp' => $txt('contact_whatsapp', 30),
            'address' => $txt('address', 255), 'website' => $web, 'footer' => $txt('footer', 255), 'maintenance_message' => $txt('maintenance_message', 255),
            'allow_school_requests' => !empty($d['allow_school_requests']) && $d['allow_school_requests'] !== '0' ? '1' : '0', 'allow_online_payments' => !empty($d['allow_online_payments']) && $d['allow_online_payments'] !== '0' ? '1' : '0', 'maintenance' => !empty($d['maintenance']) && $d['maintenance'] !== '0' ? '1' : '0'];
        $newLogo = $logo ? self::storeLogo($logo) : null;
        foreach ($vals as $k => $v) { PlatformSettings::set('brand.' . $k, $v); }
        if ($newLogo) { self::dropLogoFile(); PlatformSettings::set('brand.logo_file', $newLogo); } elseif ($removeLogo) { self::dropLogoFile(); PlatformSettings::set('brand.logo_file', null); }
        self::forget();
        AuditService::log('platform.brand', 'platform', null, null, ['name' => $name, 'logo_changed' => $newLogo !== null || $removeLogo, 'maintenance' => $vals['maintenance']]);
    }
    private static function dropLogoFile(): void { $n = PlatformSettings::get('brand.logo_file'); if ($n && preg_match('/^[a-f0-9]{32}\.(png|jpg|webp)$/', $n)) { @unlink(storage_path('uploads/brand/' . $n)); } }
    /** يتحقق من الصورة فعليًا ويعيد ترميزها (يزيل البيانات الوصفية والمحتوى الخبيث) ويحدّ أبعادها. يعيد اسم الملف المخزَّن. */
    public static function storeLogo(array $f): string { return ImageUpload::storeSafe($f, storage_path('uploads/brand'), self::LOGO_MAX, self::LOGO_DIM); }
}
