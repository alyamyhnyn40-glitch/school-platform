<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\{Auth, DB, HttpException, Session};

/** الملف الشخصي للمستخدم الحالي: يرى بياناته فقط ويعدّل اسمه وسمة العرض. */
final class ProfileService
{
    public const TYPE_LABELS = ['platform' => 'مشغّل المنصة', 'staff' => 'موظف', 'student' => 'طالب', 'guardian' => 'ولي أمر'];
    public static function me(): array
    {
        $uid = (int)Auth::id(); $u = DB::one('SELECT id, school_id, user_type, full_name, email, phone, username, theme, last_login_at, created_at FROM users WHERE id = ? AND deleted_at IS NULL', [$uid]) ?? throw new HttpException(404, 'المستخدم غير موجود');
        $school = $u['school_id'] ? DB::one('SELECT id, name, city, phone, email FROM schools WHERE id = ?', [$u['school_id']]) : null;
        $roles = $u['school_id'] ? array_column(DB::all('SELECT r.name FROM user_roles ur JOIN roles r ON r.id = ur.role_id WHERE ur.user_id = ? ORDER BY r.name', [$uid]), 'name') : [];
        $extra = [];
        if ($u['user_type'] === 'staff') { $extra['staff'] = DB::one('SELECT job_title, employee_no FROM staff WHERE user_id = ? AND school_id = ? AND deleted_at IS NULL', [$uid, $u['school_id']]); }
        if ($u['user_type'] === 'student') {
            $extra['student'] = DB::one('SELECT st.id, st.student_no, g.name AS grade, sc.name AS section FROM students st LEFT JOIN student_enrollments e ON e.student_id = st.id AND e.status = "active" LEFT JOIN sections sc ON sc.id = e.section_id LEFT JOIN grade_levels g ON g.id = sc.grade_level_id WHERE st.user_id = ? AND st.school_id = ? AND st.deleted_at IS NULL LIMIT 1', [$uid, $u['school_id']]);
        }
        if ($u['user_type'] === 'guardian') {
            $extra['schools'] = AuthService::guardianSchools($uid);
        }
        return ['user' => $u, 'school' => $school, 'roles' => $roles, 'extra' => $extra, 'type_label' => self::TYPE_LABELS[$u['user_type']] ?? $u['user_type']];
    }
    public static function update(array $d): void
    {
        $name = trim((string)($d['full_name'] ?? '')); $theme = (string)($d['theme'] ?? 'auto');
        if (mb_strlen($name) < 3 || mb_strlen($name) > 200) { throw new HttpException(422, 'الاسم من 3 إلى 200 حرف', ['full_name' => 'غير صالح']); }
        if (!in_array($theme, ['light', 'dark', 'auto'], true)) { throw new HttpException(422, 'سمة غير صالحة', ['theme' => 'غير صالحة']); }
        $uid = (int)Auth::id(); DB::query('UPDATE users SET full_name = ?, theme = ? WHERE id = ?', [$name, $theme, $uid]);
        $s = Session::get('user'); if (is_array($s)) { $s['full_name'] = $name; $s['theme'] = $theme; Session::set('user', $s); }
        AuditService::log('profile.update', 'user', $uid, null, ['theme' => $theme]);
    }
}
