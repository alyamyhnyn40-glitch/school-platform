<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\DB;

/** حدود الاشتراك: عدد الطلاب والوحدات المفعّلة والتخزين. */
final class SubscriptionService
{
    public static function current(int $schoolId): ?array
    {
        return DB::one("SELECT s.*, p.name AS plan_name, p.max_students, p.max_storage_mb, p.modules
            FROM school_subscriptions s JOIN subscription_plans p ON p.id = s.plan_id
            WHERE s.school_id = ? AND s.status = 'active' AND s.starts_on <= CURDATE() AND (s.ends_on IS NULL OR s.ends_on >= CURDATE())
            ORDER BY s.id DESC LIMIT 1", [$schoolId]);
    }
    public static function studentLimitReached(int $schoolId): bool
    {
        $sub = self::current($schoolId);
        if (!$sub) { return true; } // لا اشتراك فعّال = لا إضافة
        if ($sub['max_students'] === null) { return false; }
        $n = (int)DB::value("SELECT COUNT(*) FROM students WHERE school_id = ? AND status = 'active' AND deleted_at IS NULL", [$schoolId]);
        return $n >= (int)$sub['max_students'];
    }
    public static function moduleEnabled(int $schoolId, string $module): bool
    {
        $sub = self::current($schoolId);
        if (!$sub || !in_array($module, json_decode($sub['modules'], true) ?: [], true)) { return false; }
        return (bool)DB::value('SELECT enabled FROM school_modules WHERE school_id = ? AND module = ?', [$schoolId, $module]);
    }
    public static function storageUsedMb(int $schoolId): float
    {
        return round((float)DB::value('SELECT COALESCE(SUM(size_bytes),0) FROM files WHERE school_id = ? AND deleted_at IS NULL', [$schoolId]) / 1048576, 2);
    }
    public static function storageLimitReached(int $schoolId, int $addBytes = 0): bool
    {
        $sub = self::current($schoolId);
        if (!$sub || $sub['max_storage_mb'] === null) { return !$sub; }
        return self::storageUsedMb($schoolId) + $addBytes / 1048576 > (int)$sub['max_storage_mb'];
    }
}
