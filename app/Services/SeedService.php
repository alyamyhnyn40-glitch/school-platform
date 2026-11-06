<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\DB;

/** بيانات أساسية فقط: صلاحيات، عملات، خطة افتراضية، أدوار المدارس. */
final class SeedService
{
    public static function catalog(): array { return require base_path('database/seeds/catalog.php'); }

    public static function seedBase(): void
    {
        $cat = self::catalog();
        foreach ($cat['permissions'] as $module => $perms) {
            foreach ($perms as $code => $label) {
                DB::query('INSERT INTO permissions (code, module, label) VALUES (?,?,?) ON DUPLICATE KEY UPDATE label = VALUES(label), module = VALUES(module)', [$code, $module, $label]);
            }
        }
        foreach ([['YER', 'ريال يمني', 'ر.ي'], ['USD', 'دولار أمريكي', '$'], ['SAR', 'ريال سعودي', 'ر.س']] as $c) {
            DB::query('INSERT IGNORE INTO currencies (code, name, symbol) VALUES (?,?,?)', $c);
        }
        if (!DB::value('SELECT COUNT(*) FROM subscription_plans')) {
            DB::query('INSERT INTO subscription_plans (name, max_students, max_storage_mb, modules, price, currency) VALUES (?,?,?,?,?,?)',
                ['الخطة الأساسية', 500, 2048, json_encode(['core', 'fees', 'lms', 'comms', 'extended']), 0, 'USD']);
        }
    }

    /** ينشئ أدوار المدرسة الافتراضية ويعيد خريطة code=>role_id. */
    public static function seedSchoolRoles(int $schoolId): array
    {
        $cat = self::catalog();
        $schoolPerms = [];
        foreach ($cat['permissions'] as $module => $perms) { if (!in_array($module, ['platform', 'portal'], true)) { $schoolPerms = array_merge($schoolPerms, array_keys($perms)); } }   // portal.view لبوابة الطالب/ولي الأمر فقط
        $ids = [];
        foreach ($cat['roles'] as $code => [$name, $perms]) {
            DB::query('INSERT INTO roles (school_id, code, name, is_system) VALUES (?,?,?,1) ON DUPLICATE KEY UPDATE name = VALUES(name)', [$schoolId, $code, $name]);
            $rid = (int)DB::value('SELECT id FROM roles WHERE school_id = ? AND code = ?', [$schoolId, $code]);
            $ids[$code] = $rid;
            $list = $perms === ['*school'] ? $schoolPerms : $perms;
            foreach ($list as $p) {
                DB::query('INSERT IGNORE INTO role_permissions (role_id, permission_id) SELECT ?, id FROM permissions WHERE code = ?', [$rid, $p]);
            }
        }
        return $ids;
    }

    /**
     * يضمن أن دور «مدير المدرسة» الأساسي يملك كل صلاحيات المدرسة في الكتالوج الحالية (إضافة فقط، لا حذف).
     * يمنع 403 لمدير مدرسة أُنشئت قبل ظهور صلاحيات جديدة. لا يمس بقية الأدوار (قد يكون المدير عدّلها عمدًا).
     * @return int عدد الصلاحيات المضافة
     */
    public static function syncAdminRoles(): int
    {
        $cat = self::catalog(); $codes = [];
        foreach ($cat['permissions'] as $module => $perms) { if (!in_array($module, ['platform', 'portal'], true)) { $codes = array_merge($codes, array_keys($perms)); } }
        $n = 0;
        self::seedBase();   // يضمن وجود كل الصلاحيات بتصنيفها وأوصافها
        foreach ($codes as $c) {
            $st = DB::query("INSERT IGNORE INTO role_permissions (role_id, permission_id) SELECT r.id, p.id FROM roles r JOIN permissions p ON p.code = ? WHERE r.code = 'school_admin' AND r.is_system = 1", [$c]);
            $n += $st->rowCount();
        }
        return $n;
    }

    /** إعدادات أولية للمدرسة: نظام درجات مبدئي قابل للتعديل (D-05). */
    public static function seedSchoolDefaults(int $schoolId): void
    {
        DB::query("INSERT INTO grading_schemes (school_id, name, max_score, pass_score, average_method, is_default) VALUES (?, 'النظام الافتراضي', 100, 50, 'weighted', 1)", [$schoolId]);
        $sid = DB::lastId();
        foreach ([['أعمال الفصل', 30, 30, 1], ['اختبار نهاية الفصل', 70, 70, 2]] as [$n, $w, $m, $o]) {
            DB::query('INSERT INTO assessment_types (school_id, scheme_id, name, weight, max_score, sort_order) VALUES (?,?,?,?,?,?)', [$schoolId, $sid, $n, $w, $m, $o]);
        }
        foreach ([['ممتاز', 90], ['جيد جدًا', 80], ['جيد', 65], ['مقبول', 50], ['ضعيف', 0]] as [$l, $p]) {
            DB::query('INSERT INTO grade_scales (school_id, scheme_id, label, min_percent) VALUES (?,?,?,?)', [$schoolId, $sid, $l, $p]);
        }
        DB::query('INSERT IGNORE INTO tax_settings (school_id, tax_enabled) VALUES (?, 0)', [$schoolId]);
        foreach (['core', 'fees', 'lms', 'comms', 'extended'] as $m) {
            DB::query('INSERT IGNORE INTO school_modules (school_id, module, enabled) VALUES (?,?,1)', [$schoolId, $m]);
        }
        DB::query("INSERT IGNORE INTO payment_methods (school_id, kind, name) VALUES (?, 'cash', 'نقدًا')", [$schoolId]);
    }
}
