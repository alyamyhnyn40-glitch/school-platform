<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\{Auth, DB, HttpException, Tenant};
use App\Repositories\StaffRepository;

final class StaffService
{
    public static function search(int $page, int $per, string $q, string $kind): array
    {
        Auth::authorize('staff.view');
        $r = new StaffRepository();
        $where = []; if ($kind === 'teachers') { $where['is_teacher'] = 1; }
        return $r->paginate($page, $per, $where, ['full_name', 'phone', 'employee_no', 'job_title'], $q, 'full_name');
    }
    public static function teachers(): array { Auth::authorize('academic.view'); return (new StaffRepository())->where(['is_teacher' => 1, 'status' => 'active'], 'full_name'); }

    /** ينشئ موظفًا، واختياريًا حساب دخول بدور محدد. يعيد [id, temp_password|null]. */
    public static function create(array $d): array
    {
        Auth::authorize('staff.manage');
        $sid = Tenant::require(); $temp = null;
        $id = DB::transaction(function () use ($d, $sid, &$temp) {
            $userId = null;
            if (!empty($d['create_account'])) {
                if (!empty($d['role_id']) && !DB::value('SELECT 1 FROM roles WHERE id = ? AND school_id = ?', [(int)$d['role_id'], $sid])) { throw new HttpException(422, 'الدور غير صالح'); }
                $email = ($d['email'] ?? '') !== '' ? mb_strtolower(trim((string)$d['email'])) : null;
                $phone = ($d['phone'] ?? '') !== '' ? Phone::normalize((string)$d['phone']) : null;
                if (!$email && !$phone) { throw new HttpException(422, 'أدخل بريدًا أو هاتفًا لإنشاء الحساب', ['email' => 'مطلوب']); }
                if ($email && DB::value('SELECT 1 FROM users WHERE email = ?', [$email])) { throw new HttpException(422, 'البريد مستخدم', ['email' => 'مستخدم']); }
                if ($phone && DB::value('SELECT 1 FROM users WHERE phone = ?', [$phone])) { throw new HttpException(422, 'الهاتف مستخدم', ['phone' => 'مستخدم']); }
                $temp = SchoolService::tempPassword();
                DB::query("INSERT INTO users (school_id, user_type, full_name, email, phone, password_hash, must_change_password) VALUES (?, 'staff', ?, ?, ?, ?, 1)", [$sid, trim((string)$d['full_name']), $email, $phone, PasswordHasher::hash($temp)]);
                $userId = DB::lastId();
                $roleId = !empty($d['role_id']) ? (int)$d['role_id'] : (int)DB::value('SELECT id FROM roles WHERE school_id = ? AND code = ?', [$sid, !empty($d['is_teacher']) ? 'teacher' : 'vice_principal']);
                if ($roleId) { DB::query('INSERT INTO user_roles (user_id, role_id) VALUES (?,?)', [$userId, $roleId]); }
            }
            $repo = new StaffRepository();
            try {
                $id = $repo->insert(['user_id' => $userId, 'employee_no' => ($d['employee_no'] ?? '') ?: null, 'full_name' => trim((string)$d['full_name']), 'gender' => ($d['gender'] ?? '') ?: null,
                    'birth_date' => ($d['birth_date'] ?? '') ?: null, 'national_id_enc' => Crypto::encrypt($d['national_id'] ?? null), 'phone' => ($d['phone'] ?? '') !== '' ? Phone::normalize((string)$d['phone']) : null,
                    'email' => ($d['email'] ?? '') ?: null, 'address' => $d['address'] ?? null, 'job_title' => $d['job_title'] ?? null, 'specialization' => $d['specialization'] ?? null,
                    'is_teacher' => (int)!empty($d['is_teacher']), 'hire_date' => ($d['hire_date'] ?? '') ?: null]);
            } catch (\PDOException $e) { throw AcademicService::dbError($e, 'الرقم الوظيفي مستخدم'); }
            AuditService::log('staff.create', 'staff', $id, null, ['name' => $d['full_name'], 'account' => (bool)$userId]);
            return $id;
        });
        return [$id, $temp];
    }

    public static function find(int $id): array
    {
        Auth::authorize('staff.view');
        $s = (new StaffRepository())->findOrFail($id);
        $s['national_id'] = Crypto::decrypt($s['national_id_enc']); unset($s['national_id_enc']);
        $s['qualifications'] = DB::all('SELECT * FROM staff_qualifications WHERE staff_id = ? AND school_id = ?', [$id, Tenant::require()]);
        $s['assignments'] = DB::all('SELECT sub.name AS subject, sc.name AS section, g.name AS grade, y.name AS year FROM teacher_assignments ta JOIN section_subjects ss ON ss.id = ta.section_subject_id
            JOIN subjects sub ON sub.id = ss.subject_id JOIN sections sc ON sc.id = ss.section_id JOIN grade_levels g ON g.id = sc.grade_level_id JOIN academic_years y ON y.id = sc.academic_year_id
            WHERE ta.staff_id = ? AND ta.school_id = ? ORDER BY y.starts_on DESC, g.sort_order', [$id, Tenant::require()]);
        return $s;
    }

    public static function update(int $id, array $d): void
    {
        Auth::authorize('staff.manage');
        $repo = new StaffRepository(); $old = $repo->findOrFail($id);
        $new = [];
        foreach (['full_name', 'gender', 'birth_date', 'address', 'job_title', 'specialization', 'hire_date', 'employee_no', 'email', 'status'] as $f) { if (array_key_exists($f, $d)) { $new[$f] = $d[$f] === '' ? null : $d[$f]; } }
        if (array_key_exists('phone', $d)) { $new['phone'] = $d['phone'] === '' ? null : Phone::normalize((string)$d['phone']); }
        if (array_key_exists('is_teacher', $d)) { $new['is_teacher'] = (int)!empty($d['is_teacher']); }
        if (array_key_exists('national_id', $d)) { $new['national_id_enc'] = Crypto::encrypt(trim((string)$d['national_id'])); }
        [$o, $n] = AuditService::diff($old, $new); unset($o['national_id_enc'], $n['national_id_enc']);
        $new['updated_at'] = now();
        try { $repo->update($id, $new); } catch (\PDOException $e) { throw AcademicService::dbError($e, 'الرقم الوظيفي مستخدم'); }
        if ($n) { AuditService::log('staff.update', 'staff', $id, $o, $n); }
        if (isset($new['status']) && $old['user_id']) { DB::query('UPDATE users SET status = ? WHERE id = ? AND school_id = ?', [$new['status'] === 'terminated' ? 'disabled' : 'active', $old['user_id'], Tenant::require()]); }
    }

    public static function delete(int $id): void
    {
        Auth::authorize('staff.manage');
        $repo = new StaffRepository(); $s = $repo->findOrFail($id);
        if (DB::value('SELECT 1 FROM teacher_assignments WHERE staff_id = ? LIMIT 1', [$id])) { throw new HttpException(409, 'المعلم مُسند لمواد. أزل الإسناد أولًا.'); }
        $repo->delete($id);
        if ($s['user_id']) { DB::query("UPDATE users SET status = 'disabled' WHERE id = ? AND school_id = ?", [$s['user_id'], Tenant::require()]); }
        AuditService::log('staff.delete', 'staff', $id, ['name' => $s['full_name']]);
    }
}
