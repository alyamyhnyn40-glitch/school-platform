<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\{Auth, DB, Env, HttpException, Tenant};
use App\Repositories\{DisciplineRepository, EnrollmentRepository, SectionRepository, StatusHistoryRepository, StudentGuardianRepository, StudentRepository};

/** ملفات الطلاب ودورة حياتهم وأولياء الأمور. */
final class StudentService
{
    public static function search(int $page, int $per, string $q, ?int $sectionId, ?int $gradeId, string $status): array
    {
        Auth::authorize('students.view');
        $sid = Tenant::require(); $per = min(100, max(1, $per)); $page = max(1, $page);
        $year = AcademicService::activeYear();
        $w = 's.school_id = ? AND s.deleted_at IS NULL'; $p = [$year['id'] ?? 0, $sid];
        if ($status !== '' && in_array($status, ['active', 'promoted', 'repeated', 'withdrawn', 'transferred', 'graduated'], true)) { $w .= ' AND s.status = ?'; $p[] = $status; }
        if ($q !== '') { $l = '%' . addcslashes($q, '%_\\') . '%'; $w .= ' AND (s.full_name LIKE ? OR s.student_no LIKE ? OR s.phone LIKE ?)'; array_push($p, $l, $l, $l); }
        if ($sectionId) { $w .= ' AND e.section_id = ?'; $p[] = $sectionId; }
        if ($gradeId) { $w .= ' AND sc.grade_level_id = ?'; $p[] = $gradeId; }
        $scope = Scope::sectionIds();
        if ($scope !== null) { if (!$scope) { $w .= ' AND 1=0'; } else { $w .= ' AND e.section_id IN (' . implode(',', $scope) . ')'; } }
        $from = 'FROM students s LEFT JOIN student_enrollments e ON e.student_id = s.id AND e.academic_year_id = ? AND e.status = "active"
                 LEFT JOIN sections sc ON sc.id = e.section_id LEFT JOIN grade_levels g ON g.id = sc.grade_level_id';
        // ترتيب المعاملات: الاستعلام الفرعي للسنة أولًا ثم school_id
        $total = (int)DB::value("SELECT COUNT(*) $from WHERE $w", $p);
        $rows = DB::all("SELECT s.id, s.student_no, s.full_name, s.gender, s.status, s.phone, g.name AS grade_name, sc.name AS section_name, e.section_id $from WHERE $w ORDER BY s.full_name LIMIT $per OFFSET " . (($page - 1) * $per), $p);
        return ['rows' => $rows, 'meta' => ['page' => $page, 'per_page' => $per, 'total' => $total, 'pages' => (int)ceil($total / $per)]];
    }

    public static function profile(int $id): array
    {
        Auth::authorize('students.view');
        $s = (new StudentRepository())->findOrFail($id);
        $sid = Tenant::require();
        $cur = DB::one('SELECT e.section_id FROM student_enrollments e WHERE e.student_id = ? AND e.status = "active" ORDER BY e.id DESC LIMIT 1', [$id]);
        if (!Scope::canSeeSection((int)($cur['section_id'] ?? 0)) && Scope::sectionIds() !== null) { throw new HttpException(403, 'هذا الطالب ليس ضمن شعبك'); }
        $s['national_id'] = Crypto::decrypt($s['national_id_enc']); $s['health_notes'] = Crypto::decrypt($s['health_notes_enc']);
        unset($s['national_id_enc'], $s['health_notes_enc']);
        return [
            'student' => $s,
            'uid' => DB::value('SELECT platform_uid FROM student_identities WHERE id = ?', [$s['identity_id']]),
            'enrollments' => DB::all('SELECT e.*, y.name AS year_name, sc.name AS section_name, g.name AS grade_name FROM student_enrollments e JOIN academic_years y ON y.id = e.academic_year_id
                JOIN sections sc ON sc.id = e.section_id JOIN grade_levels g ON g.id = sc.grade_level_id WHERE e.student_id = ? AND e.school_id = ? ORDER BY y.starts_on DESC', [$id, $sid]),
            'guardians' => DB::all('SELECT sg.*, g.full_name, g.phone, g.whatsapp, g.email, g.user_id FROM student_guardians sg JOIN guardians g ON g.id = sg.guardian_id WHERE sg.student_id = ? AND sg.school_id = ? ORDER BY sg.is_primary DESC, sg.id', [$id, $sid]),
            'discipline' => Auth::can('students.discipline') ? (new DisciplineRepository())->where(['student_id' => $id], 'incident_date DESC') : [],
            'history' => DB::all('SELECT h.*, u.full_name AS by_name FROM student_status_history h LEFT JOIN users u ON u.id = h.changed_by WHERE h.student_id = ? AND h.school_id = ? ORDER BY h.id DESC', [$id, $sid]),
        ];
    }

    /**
     * ملخص شامل للطالب (للقراءة): حضور، نتائج الفصول، شهادات، رسوم، انضباط، شهادات رقمية. كل قسم يظهر لمن يملك صلاحيته فقط
     * (والمعلم ضمن شعبه عبر profile()). لا يغيّر شيئًا.
     */
    public static function overview(int $id): array
    {
        self::profile($id);   // يفحص الصلاحية والنطاق
        $sid = Tenant::require(); $o = ['year' => null, 'current' => null, 'attendance' => null, 'terms' => [], 'report_cards' => [], 'fees' => null, 'discipline' => null, 'credentials' => []];
        $y = AcademicService::activeYear(); $o['year'] = $y['name'] ?? null;
        $cur = $y ? DB::one("SELECT e.id AS enrollment_id, e.section_id, e.roll_no, e.enrolled_on, sc.name AS section_name, g.name AS grade_name, hr.full_name AS homeroom FROM student_enrollments e JOIN sections sc ON sc.id = e.section_id JOIN grade_levels g ON g.id = sc.grade_level_id LEFT JOIN staff hr ON hr.id = sc.homeroom_staff_id WHERE e.student_id = ? AND e.academic_year_id = ? AND e.school_id = ? ORDER BY e.id DESC LIMIT 1", [$id, $y['id'], $sid]) : null;
        $o['current'] = $cur;
        if ($cur && Auth::can('attendance.view')) {
            $r = DB::one("SELECT SUM(r.status = 'present') p, SUM(r.status = 'late') l, SUM(r.status = 'absent') a, SUM(r.status = 'excused') x FROM attendance_records r JOIN attendance_sessions s ON s.id = r.session_id WHERE r.enrollment_id = ? AND r.school_id = ? AND s.kind <> 'live'", [$cur['enrollment_id'], $sid]);
            $den = (int)$r['p'] + (int)$r['l'] + (int)$r['a'];
            $o['attendance'] = ['present' => (int)$r['p'], 'late' => (int)$r['l'], 'absent' => (int)$r['a'], 'excused' => (int)$r['x'], 'rate' => $den > 0 ? round(((int)$r['p'] + (int)$r['l']) / $den * 100, 1) : null,
                'recent_absences' => array_column(DB::all("SELECT s.session_date d FROM attendance_records r JOIN attendance_sessions s ON s.id = r.session_id WHERE r.enrollment_id = ? AND r.school_id = ? AND r.status = 'absent' AND s.kind <> 'live' ORDER BY s.session_date DESC LIMIT 5", [$cur['enrollment_id'], $sid]), 'd')];
        }
        if ($cur && Auth::can('grades.view')) {
            foreach (DB::all('SELECT * FROM terms WHERE school_id = ? AND academic_year_id = ? AND starts_on <= CURDATE() ORDER BY term_no', [$sid, $y['id']]) as $t) {
                try { $res = ResultsService::compute((int)$t['id'], (int)$cur['section_id'], false); } catch (\Throwable) { continue; }
                foreach ($res['students'] as $st) { if ((int)$st['student_id'] === $id) { $subs = []; foreach ($res['subjects'] as $sub) { $x = $st['subjects'][$sub['id']] ?? null; if ($x && !empty($x['has_assessments'])) { $subs[] = ['name' => $sub['name'], 'total' => $x['total'], 'percent' => $x['percent'], 'letter' => $x['letter'], 'passed' => $x['passed']]; } }
                    $o['terms'][] = ['name' => $t['name'], 'average' => $st['average'], 'letter' => $st['letter'] ?? null, 'rank' => $st['rank'] ?? null, 'size' => count($res['students']), 'result' => $st['result'] ?? null, 'complete' => $st['complete'] ?? true, 'subjects' => $subs]; } }
            }
            $o['report_cards'] = DB::all("SELECT rc.id, rc.kind, rc.average, rc.rank_in_section, rc.result, rc.issued_at, t.name AS term_name FROM report_cards rc JOIN student_enrollments e ON e.id = rc.enrollment_id LEFT JOIN terms t ON t.id = rc.term_id WHERE e.student_id = ? AND rc.school_id = ? ORDER BY rc.id DESC LIMIT 20", [$id, $sid]);
        }
        if (Auth::can('fees.view') && SubscriptionService::moduleEnabled($sid, 'fees')) { try { $o['fees'] = FinanceReportService::statement($id)['totals']; } catch (\Throwable) { $o['fees'] = null; } }
        if (Auth::can('students.discipline')) { $o['discipline'] = []; foreach (DB::all('SELECT kind, COUNT(*) n FROM discipline_records WHERE student_id = ? AND school_id = ? AND deleted_at IS NULL GROUP BY kind', [$id, $sid]) as $d) { $o['discipline'][$d['kind']] = (int)$d['n']; } }
        if (Auth::can('reportcards.issue')) { $o['credentials'] = DB::all('SELECT id, title, kind, issued_on, revoked_at FROM credentials WHERE student_id = ? AND school_id = ? ORDER BY id DESC LIMIT 10', [$id, $sid]); }
        return $o;
    }

    public static function create(array $d): int
    {
        Auth::authorize('students.manage');
        $sid = Tenant::require();
        if (SubscriptionService::studentLimitReached($sid)) { throw new HttpException(422, 'تم بلوغ الحد الأقصى للطلاب في اشتراك المدرسة (أو لا يوجد اشتراك فعّال)'); }
        $nid = trim((string)($d['national_id'] ?? ''));
        $hash = $nid !== '' ? hash_hmac('sha256', $nid, (string)Env::get('APP_ENC_KEY', 'k')) : null;
        $id = DB::transaction(function () use ($d, $sid, $nid, $hash) {
            $identity = null;
            if ($hash) {
                $identity = DB::one('SELECT id FROM student_identities WHERE national_id_hash = ?', [$hash]);
                if ($identity && DB::value('SELECT 1 FROM students WHERE school_id = ? AND identity_id = ? AND status = "active" AND deleted_at IS NULL', [$sid, $identity['id']])) {
                    throw new HttpException(422, 'يوجد طالب فعّال بنفس الرقم الوطني في المدرسة', ['national_id' => 'مكرر']);
                }
            }
            if ($identity) { $identityId = (int)$identity['id']; }
            else {
                DB::query('INSERT INTO student_identities (platform_uid, full_name, birth_date, gender, national_id_hash) VALUES (?,?,?,?,?)',
                    [strtoupper(bin2hex(random_bytes(8))), trim((string)$d['full_name']), ($d['birth_date'] ?? '') ?: null, $d['gender'], $hash]);
                $identityId = DB::lastId();
            }
            $no = trim((string)($d['student_no'] ?? '')) ?: self::nextNumber($sid);
            $repo = new StudentRepository();
            try {
                $stuId = $repo->insert(['identity_id' => $identityId, 'student_no' => $no, 'full_name' => trim((string)$d['full_name']), 'gender' => $d['gender'],
                    'birth_date' => ($d['birth_date'] ?? '') ?: null, 'birth_place' => $d['birth_place'] ?? null, 'nationality' => $d['nationality'] ?? null,
                    'national_id_enc' => Crypto::encrypt($nid), 'address' => $d['address'] ?? null, 'phone' => ($d['phone'] ?? '') !== '' ? Phone::normalize((string)$d['phone']) : null,
                    'blood_type' => $d['blood_type'] ?? null, 'health_notes_enc' => Crypto::encrypt($d['health_notes'] ?? null), 'special_needs' => $d['special_needs'] ?? null,
                    'previous_school' => $d['previous_school'] ?? null, 'admission_date' => ($d['admission_date'] ?? '') ?: date('Y-m-d'), 'status' => 'active']);
            } catch (\PDOException $e) { throw AcademicService::dbError($e, 'رقم القيد مستخدم'); }
            self::history($stuId, null, null, 'active', 'تسجيل جديد');
            if (!empty($d['section_id'])) { self::enroll($stuId, (int)$d['section_id']); }
            AuditService::log('student.create', 'student', $stuId, null, ['name' => $d['full_name'], 'no' => $no]);
            return $stuId;
        });
        return $id;
    }

    public static function update(int $id, array $d): void
    {
        Auth::authorize('students.manage');
        $repo = new StudentRepository(); $old = $repo->findOrFail($id);
        $new = [];
        foreach (['full_name', 'gender', 'birth_date', 'birth_place', 'nationality', 'address', 'blood_type', 'special_needs', 'previous_school', 'admission_date'] as $f) {
            if (array_key_exists($f, $d)) { $new[$f] = $d[$f] === '' ? null : $d[$f]; }
        }
        if (array_key_exists('phone', $d)) { $new['phone'] = $d['phone'] === '' ? null : Phone::normalize((string)$d['phone']); }
        if (array_key_exists('national_id', $d)) { $new['national_id_enc'] = Crypto::encrypt(trim((string)$d['national_id'])); }
        if (array_key_exists('health_notes', $d)) { $new['health_notes_enc'] = Crypto::encrypt($d['health_notes']); }
        [$o, $n] = AuditService::diff($old, $new);
        unset($o['national_id_enc'], $n['national_id_enc'], $o['health_notes_enc'], $n['health_notes_enc']);
        $new['updated_at'] = now();
        $repo->update($id, $new);
        if ($n) { AuditService::log('student.update', 'student', $id, $o, $n); }
    }

    public static function delete(int $id): void
    {
        Auth::authorize('students.manage');
        $repo = new StudentRepository(); $s = $repo->findOrFail($id);
        $repo->delete($id);
        AuditService::log('student.delete', 'student', $id, ['name' => $s['full_name']]);
    }

    // ---------- التسجيل في الشعب ----------
    public static function enroll(int $studentId, int $sectionId): int
    {
        Auth::authorize('students.manage');
        $stu = (new StudentRepository())->findOrFail($studentId);
        $sec = (new SectionRepository())->findOrFail($sectionId);
        $year = AcademicService::assertYearOpen((int)$sec['academic_year_id']);
        if ($year['status'] !== 'active') { throw new HttpException(422, 'يمكن التسجيل في السنة الفعّالة فقط'); }
        if (($sec['gender'] === 'boys' && $stu['gender'] === 'female') || ($sec['gender'] === 'girls' && $stu['gender'] === 'male')) { throw new HttpException(422, 'جنس الطالب لا يطابق نوع الشعبة'); }
        $n = (int)DB::value("SELECT COUNT(*) FROM student_enrollments WHERE section_id = ? AND status = 'active'", [$sectionId]);
        if ($sec['capacity'] !== null && $n >= (int)$sec['capacity']) { throw new HttpException(422, 'الشعبة ممتلئة'); }
        $er = new EnrollmentRepository();
        $existing = $er->where(['student_id' => $studentId, 'academic_year_id' => $sec['academic_year_id']])[0] ?? null;
        if ($existing) {
            if ($existing['status'] !== 'active') { throw new HttpException(422, 'الطالب غير فعّال في هذه السنة'); }
            $er->update((int)$existing['id'], ['section_id' => $sectionId]);
            AuditService::log('student.change_section', 'student', $studentId, ['section' => $existing['section_id']], ['section' => $sectionId]);
            return (int)$existing['id'];
        }
        $id = $er->insert(['student_id' => $studentId, 'academic_year_id' => (int)$sec['academic_year_id'], 'section_id' => $sectionId, 'status' => 'active', 'enrolled_on' => date('Y-m-d')]);
        AuditService::log('student.enroll', 'student', $studentId, null, ['section' => $sectionId]);
        return $id;
    }

    // ---------- دورة الحياة ----------
    public static function changeStatus(int $id, string $to, string $reason): void
    {
        Auth::authorize('students.lifecycle');
        if (!in_array($to, ['active', 'withdrawn', 'transferred', 'graduated'], true)) { throw new HttpException(422, 'حالة غير صالحة'); }
        if (mb_strlen(trim($reason)) < 3) { throw new HttpException(422, 'اذكر السبب', ['reason' => 'مطلوب']); }
        $repo = new StudentRepository(); $s = $repo->findOrFail($id);
        if ($s['status'] === $to) { return; }
        if ($to === 'active' && SubscriptionService::studentLimitReached(Tenant::require())) { throw new HttpException(422, 'تم بلوغ حد الطلاب في الاشتراك'); }
        DB::transaction(function () use ($repo, $id, $s, $to, $reason) {
            $repo->update($id, ['status' => $to, 'updated_at' => now()]);
            if (in_array($to, ['withdrawn', 'transferred', 'graduated'], true)) {
                DB::query("UPDATE student_enrollments SET status = ?, left_on = CURDATE() WHERE student_id = ? AND status = 'active' AND school_id = ?", [$to === 'transferred' ? 'transferred' : 'withdrawn', $id, Tenant::require()]);
            }
            if ($to === 'transferred') {
                DB::query("INSERT INTO student_transfers (identity_id, from_school_id, status) VALUES (?,?, 'requested')", [$s['identity_id'], Tenant::require()]);
            }
            self::history($id, null, $s['status'], $to, $reason);
        });
        AuditService::log('student.status', 'student', $id, ['status' => $s['status']], ['status' => $to, 'reason' => $reason]);
    }

    private static function history(int $studentId, ?int $yearId, ?string $from, string $to, ?string $reason): void
    {
        (new StatusHistoryRepository())->insert(['student_id' => $studentId, 'academic_year_id' => $yearId ?? (AcademicService::activeYear()['id'] ?? null), 'from_status' => $from, 'to_status' => $to, 'reason' => $reason, 'changed_by' => Auth::id()]);
    }

    // ---------- أولياء الأمور ----------
    public static function addGuardian(int $studentId, array $d): int
    {
        Auth::authorize('guardians.manage');
        (new StudentRepository())->findOrFail($studentId);
        $phone = ($d['phone'] ?? '') !== '' ? Phone::normalize((string)$d['phone']) : null;
        return DB::transaction(function () use ($studentId, $d, $phone) {
            // ولي الأمر عام عبر المنصة: نعيد استخدامه إن وُجد بنفس الهاتف (D-20)
            $g = $phone ? DB::one('SELECT id FROM guardians WHERE phone = ? AND deleted_at IS NULL', [$phone]) : null;
            if ($g) { $gid = (int)$g['id']; }
            else {
                DB::query('INSERT INTO guardians (full_name, phone, whatsapp, email, job, address, national_id_enc) VALUES (?,?,?,?,?,?,?)',
                    [trim((string)$d['full_name']), $phone, ($d['whatsapp'] ?? '') !== '' ? Phone::normalize((string)$d['whatsapp']) : null, ($d['email'] ?? '') ?: null, $d['job'] ?? null, $d['address'] ?? null, Crypto::encrypt($d['national_id'] ?? null)]);
                $gid = DB::lastId();
            }
            $rel = in_array($d['relation'] ?? 'father', ['father', 'mother', 'brother', 'sister', 'uncle', 'aunt', 'grandfather', 'grandmother', 'other'], true) ? ($d['relation'] ?? 'father') : 'other';
            $repo = new StudentGuardianRepository();
            try { $lid = $repo->insert(['student_id' => $studentId, 'guardian_id' => $gid, 'relation' => $rel, 'is_primary' => (int)!empty($d['is_primary']),
                'can_view' => (int)(($d['can_view'] ?? '1') !== '0'), 'can_pickup' => (int)(($d['can_pickup'] ?? '1') !== '0'), 'restriction_note' => ($d['restriction_note'] ?? '') ?: null,
                'financially_responsible' => (int)!empty($d['financially_responsible'])]); }
            catch (\PDOException $e) { throw AcademicService::dbError($e, 'ولي الأمر مرتبط بالطالب مسبقًا'); }
            if (!empty($d['is_primary'])) { DB::query('UPDATE student_guardians SET is_primary = 0 WHERE student_id = ? AND school_id = ? AND id <> ?', [$studentId, Tenant::require(), $lid]); }
            if (!empty($d['create_account']) && $phone) { self::ensureGuardianAccount($gid, $phone); }
            AuditService::log('guardian.link', 'student', $studentId, null, ['guardian' => $gid, 'relation' => $rel]);
            return $lid;
        });
    }

    /** حساب دخول لولي الأمر (يدخل برمز OTP؛ كلمة المرور عشوائية غير معلنة). */
    private static function ensureGuardianAccount(int $guardianId, string $phone): void
    {
        $g = DB::one('SELECT user_id, full_name FROM guardians WHERE id = ?', [$guardianId]);
        if ($g['user_id']) {   // ولي أمر موجود (ربما من مدرسة أخرى): يُمنح دور ولي الأمر في هذه المدرسة ليتمكن من التبديل إليها
            if (DB::value("SELECT 1 FROM users WHERE id = ? AND user_type = 'guardian'", [$g['user_id']])) { DB::query('INSERT IGNORE INTO user_roles (user_id, role_id) SELECT ?, id FROM roles WHERE school_id = ? AND code = "guardian"', [$g['user_id'], Tenant::require()]); }
            return;
        }
        if (DB::value('SELECT 1 FROM users WHERE phone = ?', [$phone])) { return; }
        DB::query("INSERT INTO users (school_id, user_type, full_name, phone, password_hash) VALUES (?, 'guardian', ?, ?, ?)", [Tenant::require(), $g['full_name'], $phone, PasswordHasher::hashRandom()]);
        $uid = DB::lastId();
        DB::query('INSERT INTO user_roles (user_id, role_id) SELECT ?, id FROM roles WHERE school_id = ? AND code = "guardian"', [$uid, Tenant::require()]);
        DB::query('UPDATE guardians SET user_id = ? WHERE id = ?', [$uid, $guardianId]);
    }

    public static function updateGuardianLink(int $linkId, array $d): void
    {
        Auth::authorize('guardians.manage');
        $repo = new StudentGuardianRepository(); $old = $repo->findOrFail($linkId);
        $new = ['can_view' => (int)!empty($d['can_view']), 'can_pickup' => (int)!empty($d['can_pickup']), 'restriction_note' => ($d['restriction_note'] ?? '') ?: null, 'is_primary' => (int)!empty($d['is_primary'])];
        $repo->update($linkId, $new);
        AuditService::log('guardian.link_update', 'student', (int)$old['student_id'], ['can_view' => $old['can_view'], 'restriction_note' => $old['restriction_note']], ['can_view' => $new['can_view'], 'restriction_note' => $new['restriction_note']]);
    }
    public static function removeGuardianLink(int $linkId): void
    {
        Auth::authorize('guardians.manage');
        $repo = new StudentGuardianRepository(); $l = $repo->findOrFail($linkId); $repo->delete($linkId);
        AuditService::log('guardian.unlink', 'student', (int)$l['student_id'], ['guardian' => $l['guardian_id']]);
    }

    // ---------- الانضباط ----------
    public static function addDiscipline(int $studentId, string $kind, string $date, string $desc, ?string $action): int
    {
        Auth::authorize('students.discipline');
        (new StudentRepository())->findOrFail($studentId);
        if (!in_array($kind, ['violation', 'warning', 'commendation'], true) || trim($desc) === '') { throw new HttpException(422, 'بيانات غير صالحة'); }
        $id = (new DisciplineRepository())->insert(['student_id' => $studentId, 'kind' => $kind, 'incident_date' => $date, 'description' => trim($desc), 'action_taken' => $action ?: null, 'recorded_by' => Auth::id()]);
        AuditService::log('discipline.add', 'student', $studentId, null, ['kind' => $kind]);
        return $id;
    }

    private static function nextNumber(int $sid): string
    {
        $year = (int)date('Y');
        DB::query('INSERT INTO counters (school_id, name, year, value) VALUES (?, "student_no", ?, 1) ON DUPLICATE KEY UPDATE value = LAST_INSERT_ID(value + 1)', [$sid, $year]);
        $v = DB::lastId() ?: 1;
        return $year . str_pad((string)$v, 4, '0', STR_PAD_LEFT);
    }

    // ---------- حساب الطالب (بوابة) ----------
    /** ينشئ حساب دخول للطالب باسم مستخدم = رقم القيد وكلمة مؤقتة. يعيد [username, temp]. */
    public static function createAccount(int $studentId): array
    {
        Auth::authorize('students.manage');
        $s = (new StudentRepository())->findOrFail($studentId);
        if ($s['user_id']) { throw new HttpException(409, 'للطالب حساب مسبقًا'); }
        $username = mb_strtolower((string)$s['student_no']);
        if (DB::value('SELECT 1 FROM users WHERE username = ?', [$username])) { throw new HttpException(409, 'اسم المستخدم (رقم القيد) مستخدم في حساب آخر. غيّر رقم القيد أولًا.'); }
        $temp = SchoolService::tempPassword(); $sid = Tenant::require();
        DB::transaction(function () use ($s, $studentId, $username, $temp, $sid) {
            DB::query("INSERT INTO users (school_id, user_type, full_name, phone, username, password_hash, must_change_password) VALUES (?, 'student', ?, ?, ?, ?, 1)", [$sid, $s['full_name'], null, $username, PasswordHasher::hash($temp)]);
            $uid = DB::lastId();
            DB::query('INSERT INTO user_roles (user_id, role_id) SELECT ?, id FROM roles WHERE school_id = ? AND code = "student"', [$uid, $sid]);
            DB::query('UPDATE students SET user_id = ? WHERE id = ? AND school_id = ?', [$uid, $studentId, $sid]);
        });
        AuditService::log('student.account', 'student', $studentId, null, ['username' => $username]);
        return [$username, $temp];
    }
    public static function resetAccount(int $studentId): array
    {
        Auth::authorize('students.manage');
        $s = (new StudentRepository())->findOrFail($studentId);
        if (!$s['user_id']) { throw new HttpException(404, 'لا يوجد حساب'); }
        $temp = SchoolService::tempPassword();
        DB::query('UPDATE users SET password_hash = ?, must_change_password = 1, failed_attempts = 0, locked_until = NULL, status = "active" WHERE id = ? AND school_id = ?', [PasswordHasher::hash($temp), $s['user_id'], Tenant::require()]);
        AuditService::log('student.account_reset', 'student', $studentId);
        return [mb_strtolower((string)$s['student_no']), $temp];
    }
}
