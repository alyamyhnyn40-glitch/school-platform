<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\{Auth, DB, HttpException, Tenant};

/** الأدوار والمستخدمون على مستوى المدرسة. */
final class RbacService
{
    /** كتالوج الصلاحيات المتاحة لأدوار المدرسة مجمّعة بالوحدة (بلا صلاحيات المنصة). */
    public static function catalog(): array
    {
        Auth::authorize('rbac.manage');
        $out = [];
        foreach (DB::all("SELECT code, module, label FROM permissions WHERE module <> 'platform' ORDER BY module, id") as $p) { $out[$p['module']][$p['code']] = $p['label']; }
        return $out;
    }
    public static function moduleLabels(): array
    {
        return ['school' => 'المدرسة', 'academic' => 'الأكاديمي', 'students' => 'الطلاب', 'staff' => 'الموظفون', 'import' => 'الاستيراد', 'timetable' => 'الجدول', 'attendance' => 'الحضور', 'grades' => 'الدرجات', 'reports' => 'التقارير', 'fees' => 'الرسوم', 'lms' => 'التعليم الإلكتروني', 'comms' => 'التواصل', 'portal' => 'البوابة', 'extended' => 'الوحدات الموسعة'];
    }

    public static function roles(): array
    {
        Auth::authorize('rbac.manage');
        return DB::all('SELECT r.*, (SELECT COUNT(*) FROM role_permissions rp WHERE rp.role_id = r.id) AS perms, (SELECT COUNT(*) FROM user_roles ur JOIN users u ON u.id = ur.user_id WHERE ur.role_id = r.id AND u.deleted_at IS NULL) AS users
            FROM roles r WHERE r.school_id = ? ORDER BY r.is_system DESC, r.id', [Tenant::require()]);
    }
    public static function role(int $id): array
    {
        Auth::authorize('rbac.manage');
        $r = DB::one('SELECT * FROM roles WHERE id = ? AND school_id = ?', [$id, Tenant::require()]) ?? throw new HttpException(404, 'الدور غير موجود');
        $r['perms'] = array_column(DB::all('SELECT p.code FROM role_permissions rp JOIN permissions p ON p.id = rp.permission_id WHERE rp.role_id = ?', [$id]), 'code');
        return $r;
    }

    private static function cleanPerms(array $perms): array
    {
        $valid = array_column(DB::all("SELECT code FROM permissions WHERE module <> 'platform'"), 'code');
        $perms = array_values(array_unique(array_map('strval', $perms)));
        foreach ($perms as $p) { if (!in_array($p, $valid, true)) { throw new HttpException(422, 'صلاحية غير معروفة: ' . $p); } }
        foreach ($perms as $p) { if (!Auth::can($p)) { throw new HttpException(403, 'لا يمكنك منح صلاحية لا تملكها: ' . $p); } }   // منع تصعيد الصلاحيات
        return $perms;
    }

    public static function saveRole(?int $id, string $name, array $perms): int
    {
        Auth::authorize('rbac.manage');
        $name = trim($name); $sid = Tenant::require();
        if (mb_strlen($name) < 2) { throw new HttpException(422, 'اسم الدور مطلوب', ['name' => 'مطلوب']); }
        $perms = self::cleanPerms($perms);
        return DB::transaction(function () use ($id, $name, $perms, $sid) {
            if ($id) {
                $old = self::role($id);
                if ($old['code'] === 'school_admin') { throw new HttpException(422, 'دور مدير المدرسة ثابت ولا يُعدَّل'); }
                if (!$old['is_system']) { DB::query('UPDATE roles SET name = ? WHERE id = ?', [$name, $id]); }
                $before = $old['perms'];
            } else {
                if (DB::value('SELECT 1 FROM roles WHERE school_id = ? AND name = ?', [$sid, $name])) { throw new HttpException(422, 'اسم الدور مستخدم', ['name' => 'مستخدم']); }
                DB::query('INSERT INTO roles (school_id, code, name, is_system) VALUES (?,?,?,0)', [$sid, 'custom_' . bin2hex(random_bytes(4)), $name]);
                $id = DB::lastId(); $before = [];
            }
            DB::query('DELETE FROM role_permissions WHERE role_id = ?', [$id]);
            foreach ($perms as $p) { DB::query('INSERT INTO role_permissions (role_id, permission_id) SELECT ?, id FROM permissions WHERE code = ?', [$id, $p]); }
            AuditService::log('role.save', 'role', $id, ['perms' => array_values(array_diff($before, $perms))], ['name' => $name, 'perms' => array_values(array_diff($perms, $before))]);
            return $id;
        });
    }

    public static function deleteRole(int $id): void
    {
        $r = self::role($id);
        if ($r['is_system']) { throw new HttpException(422, 'لا يمكن حذف دور أساسي'); }
        if (DB::value('SELECT 1 FROM user_roles WHERE role_id = ? LIMIT 1', [$id])) { throw new HttpException(409, 'الدور مُسند لمستخدمين. انقلهم لدور آخر أولًا.'); }
        DB::query('DELETE FROM role_permissions WHERE role_id = ?', [$id]); DB::query('DELETE FROM roles WHERE id = ? AND school_id = ?', [$id, Tenant::require()]);
        AuditService::log('role.delete', 'role', $id, ['name' => $r['name']]);
    }

    // ---------- المستخدمون ----------
    public static function users(): array
    {
        Auth::authorize('rbac.manage');
        $rows = DB::all("SELECT u.id, u.full_name, u.email, u.phone, u.username, u.user_type, u.status, u.last_login_at FROM users u WHERE u.school_id = ? AND u.deleted_at IS NULL AND u.user_type IN ('staff','student','guardian') ORDER BY u.user_type = 'staff' DESC, u.full_name LIMIT 500", [Tenant::require()]);
        $roles = [];
        foreach (DB::all('SELECT ur.user_id, r.id, r.name FROM user_roles ur JOIN roles r ON r.id = ur.role_id WHERE r.school_id = ?', [Tenant::require()]) as $x) { $roles[$x['user_id']][] = ['id' => (int)$x['id'], 'name' => $x['name']]; }
        foreach ($rows as &$r) { $r['roles'] = $roles[$r['id']] ?? []; }
        return $rows;
    }
    private static function staffUser(int $uid): array
    {
        return DB::one("SELECT * FROM users WHERE id = ? AND school_id = ? AND user_type = 'staff' AND deleted_at IS NULL", [$uid, Tenant::require()]) ?? throw new HttpException(404, 'المستخدم غير موجود أو ليس موظفًا');
    }
    private static function assertAdminRemains(?int $excludeUser = null, ?array $excludeRoles = null): void
    {
        $n = (int)DB::value("SELECT COUNT(DISTINCT u.id) FROM users u JOIN user_roles ur ON ur.user_id = u.id JOIN roles r ON r.id = ur.role_id
            WHERE u.school_id = ? AND r.school_id = ? AND r.code = 'school_admin' AND u.status = 'active' AND u.deleted_at IS NULL AND u.user_type = 'staff'" . ($excludeUser ? ' AND u.id <> ?' : ''), $excludeUser ? [Tenant::require(), Tenant::require(), $excludeUser] : [Tenant::require(), Tenant::require()]);
        if ($n < 1) { throw new HttpException(422, 'يجب أن يبقى مدير مدرسة فعّال واحد على الأقل'); }
    }

    public static function setUserRoles(int $uid, array $roleIds): void
    {
        Auth::authorize('rbac.manage');
        $u = self::staffUser($uid); $sid = Tenant::require();
        $roleIds = array_values(array_unique(array_map('intval', $roleIds)));
        foreach ($roleIds as $rid) {
            $r = DB::one('SELECT code FROM roles WHERE id = ? AND school_id = ?', [$rid, $sid]) ?? throw new HttpException(422, 'دور غير صالح');
            if (in_array($r['code'], ['student', 'guardian'], true)) { throw new HttpException(422, 'هذا الدور للطلاب وأولياء الأمور فقط'); }
            foreach (array_column(DB::all('SELECT p.code FROM role_permissions rp JOIN permissions p ON p.id = rp.permission_id WHERE rp.role_id = ?', [$rid]), 'code') as $perm) {
                if (!Auth::can($perm)) { throw new HttpException(403, 'لا يمكنك إسناد دور يتضمن صلاحية لا تملكها'); }
            }
        }
        $before = array_column(DB::all('SELECT role_id FROM user_roles WHERE user_id = ?', [$uid]), 'role_id');
        DB::transaction(function () use ($uid, $roleIds, $u) {
            DB::query('DELETE ur FROM user_roles ur JOIN roles r ON r.id = ur.role_id WHERE ur.user_id = ? AND r.school_id = ?', [$uid, Tenant::require()]);
            foreach ($roleIds as $rid) { DB::query('INSERT INTO user_roles (user_id, role_id) VALUES (?,?)', [$uid, $rid]); }
            self::assertAdminRemains();
        });
        AuditService::log('user.roles', 'user', $uid, ['roles' => $before], ['roles' => $roleIds]);
    }

    public static function setUserStatus(int $uid, bool $active): void
    {
        Auth::authorize('rbac.manage');
        $u = self::staffUser($uid);
        if ($uid === Auth::id() && !$active) { throw new HttpException(422, 'لا يمكنك تعطيل حسابك'); }
        DB::transaction(function () use ($uid, $active) {
            DB::query('UPDATE users SET status = ? WHERE id = ? AND school_id = ?', [$active ? 'active' : 'disabled', $uid, Tenant::require()]);
            self::assertAdminRemains();
        });
        AuditService::log('user.status', 'user', $uid, ['status' => $u['status']], ['status' => $active ? 'active' : 'disabled']);
    }

    /** إعادة تعيين كلمة مرور موظف. يعيد [اسم الدخول, كلمة مؤقتة]. */
    public static function resetPassword(int $uid): array
    {
        Auth::authorize('rbac.manage');
        $u = self::staffUser($uid); $temp = SchoolService::tempPassword();
        DB::query('UPDATE users SET password_hash = ?, must_change_password = 1, failed_attempts = 0, locked_until = NULL WHERE id = ?', [PasswordHasher::hash($temp), $uid]);
        AuditService::log('user.password_reset', 'user', $uid);
        return [$u['email'] ?: $u['phone'], $temp];
    }

    // ---------- صلاحيات مباشرة لمستخدم (منح/حجب فوق الأدوار) ----------
    public static function userOverrides(int $uid): array
    {
        Auth::authorize('rbac.manage'); self::staffUser($uid);
        return DB::all('SELECT p.code, p.label AS name, p.module, up.effect FROM user_permissions up JOIN permissions p ON p.id = up.permission_id WHERE up.user_id = ? AND up.school_id = ? ORDER BY p.module, p.code', [$uid, Tenant::require()]);
    }
    /** @param array<string,string> $map [perm_code => 'allow'|'deny'|'inherit'] */
    public static function setUserOverrides(int $uid, array $map): array
    {
        Auth::authorize('rbac.manage'); $u = self::staffUser($uid); $sid = Tenant::require();
        $catalog = []; foreach (DB::all("SELECT id, code, module FROM permissions") as $p) { $catalog[$p['code']] = $p; }
        $set = []; $before = array_column(self::userOverrides($uid), 'effect', 'code');
        foreach ($map as $code => $effect) {
            $code = (string)$code; if ($effect === 'inherit' || $effect === '' || $effect === null) { continue; }
            if (!in_array($effect, ['allow', 'deny'], true)) { throw new HttpException(422, 'قيمة غير صالحة للصلاحية ' . $code); }
            $p = $catalog[$code] ?? throw new HttpException(422, 'صلاحية غير معروفة: ' . $code);
            if ($p['module'] === 'platform') { throw new HttpException(422, 'صلاحيات المنصة لا تُمنح لمستخدمي المدارس'); }
            if ($effect === 'allow' && !Auth::can($code)) { throw (new HttpException(403, 'لا يمكنك منح صلاحية لا تملكها: ' . $code))->withPerm($code); }
            if ($effect === 'deny' && $uid === Auth::id() && $code === 'rbac.manage') { throw new HttpException(422, 'لا يمكنك حجب صلاحية إدارة الصلاحيات عن نفسك'); }
            $set[$code] = [$p['id'], $effect];
        }
        DB::transaction(function () use ($uid, $sid, $set) {
            DB::query('DELETE FROM user_permissions WHERE user_id = ? AND school_id = ?', [$uid, $sid]);
            foreach ($set as [$pid, $eff]) { DB::query('INSERT INTO user_permissions (user_id, permission_id, school_id, effect, granted_by) VALUES (?,?,?,?,?)', [$uid, $pid, $sid, $eff, Auth::id()]); }
            // لا يُسمح بأن تبقى المدرسة بلا من يملك إدارة الصلاحيات
            $ok = DB::value("SELECT 1 FROM users u WHERE u.school_id = ? AND u.status = 'active' AND u.deleted_at IS NULL AND u.user_type = 'staff' AND (
                  EXISTS (SELECT 1 FROM user_roles ur JOIN role_permissions rp ON rp.role_id = ur.role_id JOIN permissions p ON p.id = rp.permission_id WHERE ur.user_id = u.id AND p.code = 'rbac.manage')
                  OR EXISTS (SELECT 1 FROM user_permissions x JOIN permissions p ON p.id = x.permission_id WHERE x.user_id = u.id AND p.code = 'rbac.manage' AND x.effect = 'allow'))
                AND NOT EXISTS (SELECT 1 FROM user_permissions x JOIN permissions p ON p.id = x.permission_id WHERE x.user_id = u.id AND p.code = 'rbac.manage' AND x.effect = 'deny') LIMIT 1", [$sid]);
            if (!$ok) { throw new HttpException(422, 'يجب أن يبقى مستخدم واحد على الأقل يملك صلاحية إدارة الصلاحيات'); }
        });
        AuditService::log('user.permissions', 'user', $uid, ['overrides' => $before], ['overrides' => array_map(fn($x) => $x[1], $set)]);
        return array_map(fn($x) => $x[1], $set);
    }
    /** الصلاحيات الفعلية للمستخدم (أدوار + منح − حجب) مع مصدر كل صلاحية. */
    public static function effective(int $uid): array
    {
        Auth::authorize('rbac.manage'); $u = self::staffUser($uid);
        $fromRoles = array_column(DB::all('SELECT DISTINCT p.code FROM user_roles ur JOIN role_permissions rp ON rp.role_id = ur.role_id JOIN permissions p ON p.id = rp.permission_id JOIN roles r ON r.id = ur.role_id WHERE ur.user_id = ? AND r.school_id = ?', [$uid, Tenant::require()]), 'code');
        return ['roles' => $fromRoles, 'overrides' => array_column(self::userOverrides($uid), 'effect', 'code'), 'effective' => \App\Services\AuthService::permissionsFor($u)];
    }
}
