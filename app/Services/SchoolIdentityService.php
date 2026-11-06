<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\{Auth, DB, HttpException, Tenant};

/** هوية المدرسة: الاسم والشعار وبيانات التواصل (صلاحية school.settings). */
final class SchoolIdentityService
{
    public const LOGO_MAX = 1048576;
    public static function get(): array
    {
        Auth::authorize('school.settings');
        return DB::one('SELECT id, name, name_en, city, governorate, address, phone, email FROM schools WHERE id = ?', [Tenant::require()]) ?? [];
    }
    public static function save(array $d, ?array $logo, bool $removeLogo): void
    {
        Auth::authorize('school.settings'); $sid = Tenant::require(); $t = fn(string $k, int $max) => mb_substr(trim((string)($d[$k] ?? '')), 0, $max);
        $name = $t('name', 200); if (mb_strlen($name) < 3) { throw new HttpException(422, 'اسم المدرسة مطلوب (3 أحرف فأكثر)', ['name' => 'مطلوب']); }
        $email = $t('email', 190); if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) { throw new HttpException(422, 'بريد غير صالح', ['email' => 'غير صالح']); }
        DB::query('UPDATE schools SET name = ?, name_en = ?, city = ?, governorate = ?, address = ?, phone = ?, email = ? WHERE id = ?',
            [$name, $t('name_en', 200) ?: null, $t('city', 100) ?: null, $t('governorate', 100) ?: null, $t('address', 255) ?: null, $t('phone', 30) ?: null, $email ?: null, $sid]);
        $dir = storage_path('uploads/school-logos');
        if ($logo) {
            $tmp = ImageUpload::storeSafe($logo, $dir, self::LOGO_MAX, 512); $ext = pathinfo($tmp, PATHINFO_EXTENSION);
            foreach (['png', 'jpg', 'webp'] as $e) { @unlink("$dir/$sid.$e"); } rename("$dir/$tmp", "$dir/$sid.$ext"); SettingsService::set('logo_file', "$sid.$ext"); Brand::forget();
        } elseif ($removeLogo) { foreach (['png', 'jpg', 'webp'] as $e) { @unlink("$dir/$sid.$e"); } SettingsService::set('logo_file', null); Brand::forget(); }
        AuditService::log('school.identity', 'school', $sid, null, ['name' => $name, 'logo_changed' => $logo !== null || $removeLogo]);
    }
}
