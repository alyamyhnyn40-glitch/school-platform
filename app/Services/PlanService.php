<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\{Auth, DB, HttpException};

final class PlanService
{
    public const MODULES = ['core' => 'النواة', 'fees' => 'الرسوم', 'lms' => 'التعليم الإلكتروني', 'comms' => 'التواصل', 'extended' => 'الوحدات الموسعة'];

    public static function all(): array
    {
        Auth::authorize('platform.plans');
        return DB::all('SELECT p.*, (SELECT COUNT(*) FROM school_subscriptions s WHERE s.plan_id = p.id AND s.status = "active") AS schools FROM subscription_plans p ORDER BY p.id');
    }
    public static function save(?int $id, array $d): int
    {
        Auth::authorize('platform.plans');
        $mods = array_values(array_intersect(array_keys(self::MODULES), (array)($d['modules'] ?? [])));
        if (!in_array('core', $mods, true)) { array_unshift($mods, 'core'); }
        $cur = in_array($d['currency'] ?? 'USD', ['USD', 'YER', 'SAR'], true) ? ($d['currency'] ?? 'USD') : 'USD';
        $per = in_array($d['billing_period'] ?? 'yearly', ['monthly', 'yearly'], true) ? ($d['billing_period'] ?? 'yearly') : 'yearly';
        $vals = [trim((string)$d['name']), $d['max_students'] === '' || $d['max_students'] === null ? null : (int)$d['max_students'],
            $d['max_storage_mb'] === '' || $d['max_storage_mb'] === null ? null : (int)$d['max_storage_mb'], json_encode($mods),
            (float)($d['price'] ?? 0), $cur, $per, (int)!empty($d['is_active'])];
        if ($id === null) {
            DB::query('INSERT INTO subscription_plans (name, max_students, max_storage_mb, modules, price, currency, billing_period, is_active) VALUES (?,?,?,?,?,?,?,?)', $vals);
            $id = DB::lastId(); AuditService::log('plan.create', 'plan', $id, null, ['name' => $d['name']], null);
        } else {
            $old = DB::one('SELECT * FROM subscription_plans WHERE id = ?', [$id]) ?? throw new HttpException(404, 'الخطة غير موجودة');
            DB::query('UPDATE subscription_plans SET name=?, max_students=?, max_storage_mb=?, modules=?, price=?, currency=?, billing_period=?, is_active=? WHERE id=?', [...$vals, $id]);
            AuditService::log('plan.update', 'plan', $id, ['name' => $old['name'], 'max_students' => $old['max_students']], ['name' => $d['name'], 'max_students' => $vals[1]], null);
            // تحديث وحدات المدارس المشتركة على هذه الخطة
            foreach (DB::all("SELECT DISTINCT school_id FROM school_subscriptions WHERE plan_id = ? AND status = 'active'", [$id]) as $r) {
                foreach (array_keys(self::MODULES) as $m) { DB::query('INSERT INTO school_modules (school_id, module, enabled) VALUES (?,?,?) ON DUPLICATE KEY UPDATE enabled = VALUES(enabled)', [$r['school_id'], $m, (int)in_array($m, $mods, true)]); }
            }
        }
        return $id;
    }
}
