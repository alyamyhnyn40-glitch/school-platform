<?php
declare(strict_types=1);
namespace App\Core;

/**
 * ترقية تلقائية بعد كل تحديث للملفات: عند اختلاف VERSION عن آخر نسخة طُبّقت تُنفَّذ migrations وتُزامَن صلاحيات المديرين.
 * يكفي فك الحزمة الجديدة فوق القديمة (مع إبقاء .env وstorage) وفتح الموقع.
 */
final class Updater
{
    private static function stamp(): string { return storage_path('cache/applied.version'); }
    public static function appliedVersion(): ?string { $f = self::stamp(); return is_file($f) ? trim((string)@file_get_contents($f)) : null; }
    public static function pending(): bool { return self::appliedVersion() !== app_version(); }
    /** يُستدعى مع كل طلب: قراءة ملف صغير فقط عند عدم وجود ترقية. */
    public static function maybe(): void
    {
        if (!self::pending()) { return; }
        $fail = storage_path('cache/update.failed'); if (is_file($fail) && time() - (int)@filemtime($fail) < 60) { return; }   // لا نعيد المحاولة كل طلب
        @mkdir(storage_path('cache'), 0775, true); $lock = @fopen(storage_path('cache/update.lock'), 'c'); if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) { return; }   // عملية واحدة فقط
        try { if (self::pending()) { self::run(); } @unlink($fail); }
        catch (\Throwable $e) { Logger::error('فشل الترقية التلقائية: ' . $e->getMessage(), ['v' => app_version()]); @file_put_contents($fail, $e->getMessage()); }
        finally { flock($lock, LOCK_UN); fclose($lock); }
    }
    /** @return array{ran:list<string>, roles:int} */
    public static function run(): array
    {
        $ran = Migrator::run(); $roles = \App\Services\SeedService::syncAdminRoles();
        @mkdir(storage_path('cache'), 0775, true); file_put_contents(self::stamp(), app_version());
        if ($ran) { Logger::info('تمت ترقية قاعدة البيانات', ['migrations' => $ran, 'version' => app_version()]); }
        return ['ran' => $ran, 'roles' => $roles];
    }
}
