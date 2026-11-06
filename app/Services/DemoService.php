<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\{DB, HttpException};

/** يحمّل مدرسة تجريبية كاملة (من database/demo/demo-data.sql) — للتجربة والتدريب فقط. كلمة مرور كل حساباتها Demo12345. */
final class DemoService
{
    public const PASSWORD = 'Demo12345';
    /** SQL التجريبي بلا تعليقات وبلا استعلامات عرض (SELECT) حتى يعمل مع PDO في تنفيذ واحد. */
    public static function sql(): string
    {
        $f = base_path('database/demo/demo-data.sql'); if (!is_file($f)) { throw new HttpException(500, 'ملف البيانات التجريبية غير موجود'); }
        $out = []; foreach (preg_split('/\R/u', (string)file_get_contents($f)) as $l) { $t = ltrim($l); if ($t === '' || str_starts_with($t, '--') || preg_match('/^SELECT\s+(IF\(|\'تم)/u', $t)) { continue; } $out[] = $l; }
        return implode("\n", $out);
    }
    /** @return int رقم المدرسة التجريبية */
    public static function load(string $platformEmail, string $platformPassword): int
    {
        $_SESSION = []; AuthService::attemptPassword($platformEmail, $platformPassword, '127.0.0.1');
        $plan = DB::value('SELECT id FROM subscription_plans ORDER BY price DESC, id DESC LIMIT 1'); if (!$plan) { throw new HttpException(500, 'لا توجد خطة اشتراك'); }
        [$sid] = SchoolService::create(['name' => 'مدرسة الأمل التجريبية', 'city' => 'صنعاء', 'governorate' => 'أمانة العاصمة', 'plan_id' => $plan, 'admin_name' => 'مدير المدرسة', 'admin_email' => 'director@demo.test', 'gender_policy' => 'mixed', 'base_currency' => 'YER']);
        DB::query("UPDATE users SET password_hash = ?, must_change_password = 0 WHERE school_id = ?", [PasswordHasher::hash(self::PASSWORD), $sid]);
        $pdo = DB::pdo(); $pdo->exec(self::sql());
        // الخطة الدراسية للمدرسة التجريبية تُستنتج من مواد شعبها
        DB::query('INSERT IGNORE INTO study_plans (school_id, academic_year_id, grade_level_id, subject_id, weekly_periods, max_score) SELECT ss.school_id, sc.academic_year_id, sc.grade_level_id, ss.subject_id, MAX(ss.weekly_periods), MAX(ss.max_score) FROM section_subjects ss JOIN sections sc ON sc.id = ss.section_id WHERE ss.school_id = ? GROUP BY ss.school_id, sc.academic_year_id, sc.grade_level_id, ss.subject_id', [$sid]);
        DB::query("UPDATE users SET password_hash = ?, must_change_password = 0 WHERE school_id = ? AND user_type <> 'platform'", [PasswordHasher::hash(self::PASSWORD), $sid]);
        $_SESSION = []; return (int)$sid;
    }
}
