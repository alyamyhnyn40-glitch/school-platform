<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\{Auth, DB, HttpException, Session};

/** عمليات مشغّل المنصة على المدارس. كل عملية تفحص الصلاحية وتسجل في التدقيق. */
final class SchoolService
{
    public static function list(int $page, int $per, string $q, string $status): array
    {
        Auth::authorize('platform.schools');
        $per = min(100, max(1, $per)); $page = max(1, $page);
        $where = 's.deleted_at IS NULL'; $p = [];
        if ($q !== '') { $where .= ' AND (s.name LIKE ? OR s.code LIKE ? OR s.city LIKE ?)'; $l = '%' . addcslashes($q, '%_\\') . '%'; array_push($p, $l, $l, $l); }
        if (in_array($status, ['active', 'suspended', 'archived'], true)) { $where .= ' AND s.status = ?'; $p[] = $status; }
        $total = (int)DB::value("SELECT COUNT(*) FROM schools s WHERE $where", $p);
        $rows = DB::all("SELECT s.id, s.code, s.name, s.city, s.status, s.created_at,
              (SELECT COUNT(*) FROM students st WHERE st.school_id = s.id AND st.status = 'active' AND st.deleted_at IS NULL) AS students,
              (SELECT p.name FROM school_subscriptions ss JOIN subscription_plans p ON p.id = ss.plan_id WHERE ss.school_id = s.id AND ss.status = 'active' ORDER BY ss.id DESC LIMIT 1) AS plan_name,
              (SELECT ss.ends_on FROM school_subscriptions ss WHERE ss.school_id = s.id AND ss.status = 'active' ORDER BY ss.id DESC LIMIT 1) AS ends_on
            FROM schools s WHERE $where ORDER BY s.id DESC LIMIT $per OFFSET " . (($page - 1) * $per), $p);
        return ['rows' => $rows, 'meta' => ['page' => $page, 'per_page' => $per, 'total' => $total, 'pages' => (int)ceil($total / $per)]];
    }

    public static function find(int $id): array
    {
        Auth::authorize('platform.schools');
        return DB::one('SELECT * FROM schools WHERE id = ? AND deleted_at IS NULL', [$id]) ?? throw new HttpException(404, 'المدرسة غير موجودة');
    }

    /** ينشئ مدرسة + أدوارها + إعداداتها + مدير + اشتراك. يعيد [school_id, temp_password]. */
    public static function create(array $d): array
    {
        Auth::authorize('platform.schools');
        $plan = DB::one('SELECT * FROM subscription_plans WHERE id = ? AND is_active = 1', [(int)$d['plan_id']]) ?? throw new HttpException(422, 'خطة الاشتراك غير صالحة', ['plan_id' => 'اختر خطة']);
        $email = mb_strtolower(trim((string)$d['admin_email']));
        $phone = !empty($d['admin_phone']) ? Phone::normalize((string)$d['admin_phone']) : null;
        self::assertUserFree($email, $phone);
        $temp = self::tempPassword();

        $schoolId = DB::transaction(function () use ($d, $plan, $email, $phone, $temp) {
            $gp = in_array($d['gender_policy'] ?? 'mixed', ['mixed', 'boys', 'girls'], true) ? ($d['gender_policy'] ?? 'mixed') : 'mixed';
            $cur = in_array($d['base_currency'] ?? 'YER', ['YER', 'USD', 'SAR'], true) ? ($d['base_currency'] ?? 'YER') : 'YER';
            $code = !empty($d['code']) ? trim((string)$d['code']) : 'S' . strtoupper(bin2hex(random_bytes(3)));
            if (DB::value('SELECT 1 FROM schools WHERE code = ?', [$code])) { throw new HttpException(422, 'رمز المدرسة مستخدم', ['code' => 'مستخدم']); }
            DB::query('INSERT INTO schools (code, name, city, governorate, address, phone, email, gender_policy, base_currency) VALUES (?,?,?,?,?,?,?,?,?)', [
                $code, trim((string)$d['name']), $d['city'] ?? null, $d['governorate'] ?? null, $d['address'] ?? null, $d['phone'] ?? null, $d['email'] ?? null,
                $gp, $cur]);
            $sid = DB::lastId();
            $roles = SeedService::seedSchoolRoles($sid);
            SeedService::seedSchoolDefaults($sid);
            DB::query("INSERT INTO users (school_id, user_type, full_name, email, phone, password_hash, must_change_password) VALUES (?, 'staff', ?, ?, ?, ?, 1)",
                [$sid, trim((string)$d['admin_name']), $email, $phone, PasswordHasher::hash($temp)]);
            DB::query('INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)', [DB::lastId(), $roles['school_admin']]);
            self::applySubscription($sid, $plan, $d['starts_on'] ?? date('Y-m-d'), $d['ends_on'] ?? null, (float)($d['amount'] ?? $plan['price']));
            AuditService::log('school.create', 'school', $sid, null, ['name' => $d['name'], 'plan' => $plan['name']], $sid);
            return $sid;
        });
        return [$schoolId, $temp];
    }

    public static function update(int $id, array $d): void
    {
        $old = self::find($id);
        $fields = ['name', 'name_en', 'city', 'governorate', 'address', 'phone', 'email', 'gender_policy', 'base_currency'];
        $new = [];
        foreach ($fields as $f) { if (array_key_exists($f, $d)) { $new[$f] = $d[$f] === '' ? null : $d[$f]; } }
        if (!$new) { return; }
        [$o, $n] = AuditService::diff($old, $new);
        if (!$n) { return; }
        DB::query('UPDATE schools SET ' . implode(',', array_map(fn($k) => "$k = ?", array_keys($n))) . ', updated_at = NOW() WHERE id = ?', [...array_values($n), $id]);
        AuditService::log('school.update', 'school', $id, $o, $n, $id);
    }

    public static function setStatus(int $id, string $status): void
    {
        $old = self::find($id);
        if (!in_array($status, ['active', 'suspended', 'archived'], true)) { throw new HttpException(422, 'حالة غير صالحة'); }
        if ($old['status'] === $status) { return; }
        DB::query('UPDATE schools SET status = ?, updated_at = NOW() WHERE id = ?', [$status, $id]);
        AuditService::log('school.status', 'school', $id, ['status' => $old['status']], ['status' => $status], $id);
    }

    public static function changeSubscription(int $id, int $planId, string $starts, ?string $ends, float $amount): void
    {
        self::find($id);
        $plan = DB::one('SELECT * FROM subscription_plans WHERE id = ?', [$planId]) ?? throw new HttpException(422, 'خطة غير صالحة');
        if ($ends !== null && $ends !== '' && $ends < $starts) { throw new HttpException(422, 'تاريخ النهاية قبل البداية', ['ends_on' => 'غير صالح']); }
        DB::transaction(function () use ($id, $plan, $starts, $ends, $amount) {
            DB::query("UPDATE school_subscriptions SET status = 'cancelled' WHERE school_id = ? AND status = 'active'", [$id]);
            self::applySubscription($id, $plan, $starts, $ends ?: null, $amount);
            AuditService::log('school.subscription', 'school', $id, null, ['plan' => $plan['name'], 'starts_on' => $starts, 'ends_on' => $ends], $id);
        });
    }

    private static function applySubscription(int $sid, array $plan, string $starts, ?string $ends, float $amount): void
    {
        DB::query("INSERT INTO school_subscriptions (school_id, plan_id, starts_on, ends_on, status, amount) VALUES (?,?,?,?,'active',?)", [$sid, $plan['id'], $starts, $ends ?: null, $amount]);
        foreach (['core', 'fees', 'lms', 'comms', 'extended'] as $m) {
            DB::query('INSERT INTO school_modules (school_id, module, enabled) VALUES (?,?,?) ON DUPLICATE KEY UPDATE enabled = VALUES(enabled)', [$sid, $m, (int)($m === 'core' || in_array($m, json_decode($plan['modules'], true) ?: [], true))]);
        }
    }

    public static function stats(int $id): array
    {
        $q = fn(string $sql) => (int)DB::value($sql, [$id]);
        $sub = SubscriptionService::current($id);
        return [
            'students' => $q("SELECT COUNT(*) FROM students WHERE school_id = ? AND status='active' AND deleted_at IS NULL"),
            'staff' => $q('SELECT COUNT(*) FROM staff WHERE school_id = ? AND deleted_at IS NULL'),
            'users' => $q('SELECT COUNT(*) FROM users WHERE school_id = ? AND deleted_at IS NULL'),
            'logins_30d' => $q("SELECT COUNT(*) FROM audit_logs WHERE school_id = ? AND action = 'login' AND created_at >= NOW() - INTERVAL 30 DAY"),
            'storage_mb' => SubscriptionService::storageUsedMb($id),
            'plan' => $sub,
        ];
    }

    /** إعادة تعيين كلمة مرور مدير المدرسة الأول (للدعم). يعيد كلمة مرور مؤقتة. */
    public static function resetAdminPassword(int $schoolId): array
    {
        self::find($schoolId);
        $u = DB::one("SELECT u.id, u.email, u.full_name FROM users u JOIN user_roles ur ON ur.user_id = u.id JOIN roles r ON r.id = ur.role_id
                      WHERE u.school_id = ? AND r.code = 'school_admin' AND u.deleted_at IS NULL ORDER BY u.id LIMIT 1", [$schoolId]) ?? throw new HttpException(404, 'لا يوجد مدير');
        $temp = self::tempPassword();
        DB::query('UPDATE users SET password_hash = ?, must_change_password = 1, failed_attempts = 0, locked_until = NULL WHERE id = ?', [PasswordHasher::hash($temp), $u['id']]);
        AuditService::log('school.admin_reset', 'user', (int)$u['id'], null, null, $schoolId);
        return [$u, $temp];
    }

    // ---- الدعم الفني (وصول مضبوط ومسجل) ----
    public static function enterSupport(int $schoolId, string $reason): void
    {
        Auth::authorize('platform.support');
        if (mb_strlen(trim($reason)) < 5) { throw new HttpException(422, 'اذكر سبب الدخول (5 أحرف على الأقل)', ['reason' => 'مطلوب']); }
        $school = self::find($schoolId);
        if (Session::get('support_mode')) { self::exitSupport(); }
        DB::query('INSERT INTO support_access_logs (admin_user_id, school_id, reason) VALUES (?,?,?)', [Auth::id(), $schoolId, trim($reason)]);
        Session::set('support_mode', ['school_id' => $schoolId, 'log_id' => DB::lastId(), 'school_name' => $school['name']]);
        Session::set('school_id', $schoolId);
        AuditService::log('support.enter', 'school', $schoolId, null, ['reason' => trim($reason)], $schoolId);
    }

    public static function exitSupport(): void
    {
        $m = Session::get('support_mode');
        if (!$m) { return; }
        DB::query('UPDATE support_access_logs SET ended_at = NOW() WHERE id = ?', [$m['log_id']]);
        AuditService::log('support.exit', 'school', (int)$m['school_id'], null, null, (int)$m['school_id']);
        Session::forget('support_mode');
        Session::set('school_id', null);
    }

    private static function assertUserFree(string $email, ?string $phone): void
    {
        if (DB::value('SELECT 1 FROM users WHERE email = ?', [$email])) { throw new HttpException(422, 'البريد مستخدم من حساب آخر', ['admin_email' => 'مستخدم']); }
        if ($phone && DB::value('SELECT 1 FROM users WHERE phone = ?', [$phone])) { throw new HttpException(422, 'الهاتف مستخدم من حساب آخر', ['admin_phone' => 'مستخدم']); }
    }
    public static function tempPassword(): string
    {
        $a = 'abcdefghjkmnpqrstuvwxyz'; $b = '23456789'; $s = '';
        for ($i = 0; $i < 8; $i++) { $s .= $a[random_int(0, strlen($a) - 1)]; }
        return ucfirst($s) . $b[random_int(0, 7)] . $b[random_int(0, 7)];
    }
}
