<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\{Auth, DB, HttpException, Tenant};
use App\Repositories\SectionRepository;

/** حضور الطلاب: تسجيل سريع، إعادة تشغيل آمنة (idempotent) لدعم العمل دون اتصال، وتقارير. */
final class AttendanceService
{
    public const STATUSES = ['present', 'absent', 'late', 'excused'];

    /** يتحقق من الشعبة والتاريخ والنطاق. يعيد الشعبة. */
    private static function guard(int $sectionId, string $date, string $perm): array
    {
        Auth::authorize($perm);
        $sec = (new SectionRepository())->findOrFail($sectionId);
        if (!Scope::canSeeSection($sectionId)) { throw new HttpException(403, 'هذه الشعبة ليست ضمن شعبك'); }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !checkdate((int)substr($date, 5, 2), (int)substr($date, 8, 2), (int)substr($date, 0, 4))) { throw new HttpException(422, 'تاريخ غير صالح'); }
        return $sec;
    }

    /** كشف الحضور لشعبة/تاريخ/حصة (لا ينشئ جلسة عند العرض). */
    public static function roster(int $sectionId, string $date, int $period = 0): array
    {
        self::guard($sectionId, $date, 'attendance.view');
        $sid = Tenant::require();
        $session = DB::one("SELECT id FROM attendance_sessions WHERE school_id = ? AND section_id = ? AND session_date = ? AND period_no = ? AND kind = 'class'", [$sid, $sectionId, $date, $period]);
        $rows = DB::all("SELECT e.id AS enrollment_id, s.id AS student_id, s.full_name, s.student_no, e.roll_no, r.status, r.note
            FROM student_enrollments e JOIN students s ON s.id = e.student_id
            LEFT JOIN attendance_records r ON r.enrollment_id = e.id AND r.session_id = ?
            WHERE e.school_id = ? AND e.section_id = ? AND e.status = 'active' AND s.deleted_at IS NULL ORDER BY s.full_name", [$session['id'] ?? 0, $sid, $sectionId]);
        return ['session_id' => $session ? (int)$session['id'] : null, 'taken' => (bool)$session, 'students' => $rows];
    }

    /** يحفظ الحضور. $records: [{enrollment_id, status, note?, client_uuid?}]. يعيد عدادات. */
    public static function save(int $sectionId, string $date, int $period, array $records): array
    {
        $sec = self::guard($sectionId, $date, 'attendance.take');
        $year = AcademicService::assertYearOpen((int)$sec['academic_year_id']);
        if ($year['status'] !== 'active') { throw new HttpException(422, 'يمكن تسجيل الحضور في السنة الفعّالة فقط'); }
        if ($date > date('Y-m-d')) { throw new HttpException(422, 'لا يمكن تسجيل حضور لتاريخ مستقبلي'); }
        if ($date < $year['starts_on'] || $date > $year['ends_on']) { throw new HttpException(422, 'التاريخ خارج السنة الدراسية'); }
        $canEditPast = Auth::can('attendance.edit');
        $sid = Tenant::require();
        $c = ['created' => 0, 'updated' => 0, 'skipped' => 0];
        $notify = SettingsService::get('attendance.notify_absence', '0') === '1';

        DB::transaction(function () use ($sectionId, $date, $period, $records, $sid, $canEditPast, $notify, &$c) {
            DB::query("INSERT IGNORE INTO attendance_sessions (school_id, section_id, session_date, period_no, taken_by, kind) VALUES (?,?,?,?,?, 'class')", [$sid, $sectionId, $date, $period, Auth::id()]);
            $session = (int)DB::value("SELECT id FROM attendance_sessions WHERE school_id = ? AND section_id = ? AND session_date = ? AND period_no = ? AND kind = 'class'", [$sid, $sectionId, $date, $period]);
            foreach ($records as $r) {
                $eid = (int)($r['enrollment_id'] ?? 0); $status = (string)($r['status'] ?? ''); $uuid = !empty($r['client_uuid']) ? (string)$r['client_uuid'] : null;
                if (!in_array($status, self::STATUSES, true)) { throw new HttpException(422, 'حالة حضور غير صالحة'); }
                if ($uuid !== null && !preg_match('/^[0-9a-f-]{36}$/i', $uuid)) { throw new HttpException(422, 'معرّف العملية غير صالح'); }
                $enr = DB::one("SELECT e.id, e.student_id FROM student_enrollments e WHERE e.id = ? AND e.school_id = ? AND e.section_id = ? AND e.status = 'active'", [$eid, $sid, $sectionId]);
                if (!$enr) { throw new HttpException(422, 'طالب لا ينتمي لهذه الشعبة'); }
                if ($uuid && DB::value('SELECT 1 FROM attendance_records WHERE client_uuid = ? AND school_id = ?', [$uuid, $sid])) { $c['skipped']++; continue; } // إعادة إرسال
                $note = isset($r['note']) && $r['note'] !== '' ? mb_substr((string)$r['note'], 0, 255) : null;
                $old = DB::one('SELECT id, status, note FROM attendance_records WHERE session_id = ? AND enrollment_id = ?', [$session, $eid]);
                if ($old) {
                    if ($old['status'] === $status && ($old['note'] ?? null) === $note) { $c['skipped']++; continue; }
                    if ($date < date('Y-m-d') && !$canEditPast) { throw new HttpException(403, 'تعديل حضور يوم سابق يتطلب صلاحية خاصة'); }
                    DB::query('UPDATE attendance_records SET status = ?, note = ?, client_uuid = ?, updated_by = ? WHERE id = ?', [$status, $note, $uuid, Auth::id(), $old['id']]);
                    AuditService::log('attendance.change', 'attendance_record', (int)$old['id'], ['status' => $old['status'], 'note' => $old['note']], ['status' => $status, 'note' => $note, 'date' => $date, 'student_id' => $enr['student_id']]);
                    $c['updated']++; $rid = (int)$old['id'];
                } else {
                    DB::query('INSERT INTO attendance_records (school_id, session_id, enrollment_id, status, note, client_uuid, updated_by) VALUES (?,?,?,?,?,?,?)', [$sid, $session, $eid, $status, $note, $uuid, Auth::id()]);
                    $rid = DB::lastId(); $c['created']++;
                    AuditService::log('attendance.record', 'attendance_record', $rid, null, ['status' => $status, 'date' => $date, 'student_id' => $enr['student_id']]);
                }
                if ($notify && $status === 'absent') { self::notifyAbsence((int)$enr['student_id'], $date); }
            }
        });
        return $c;
    }

    /** دفعة عمليات مخزنة دون اتصال: لكل عملية نتيجة مستقلة (فشل إحداها لا يوقف البقية). */
    public static function sync(array $ops): array
    {
        $out = [];
        foreach ($ops as $i => $op) {
            try {
                $res = self::save((int)($op['section_id'] ?? 0), (string)($op['date'] ?? ''), (int)($op['period'] ?? 0), (array)($op['records'] ?? []));
                $out[] = ['i' => $i, 'ok' => true] + $res;
            } catch (HttpException $e) { $out[] = ['i' => $i, 'ok' => false, 'status' => $e->status, 'message' => $e->getMessage()]; }
        }
        return $out;
    }

    private static function notifyAbsence(int $studentId, string $date): void
    {
        $sid = Tenant::require();
        $stu = DB::one('SELECT full_name FROM students WHERE id = ? AND school_id = ?', [$studentId, $sid]);
        $school = DB::value('SELECT name FROM schools WHERE id = ?', [$sid]);
        $body = "{$school}: نفيدكم بغياب الطالب/ة {$stu['full_name']} بتاريخ $date.";
        foreach (DB::all('SELECT g.phone FROM student_guardians sg JOIN guardians g ON g.id = sg.guardian_id WHERE sg.student_id = ? AND sg.school_id = ? AND sg.can_view = 1 AND g.phone IS NOT NULL', [$studentId, $sid]) as $g) {
            if (DB::value("SELECT 1 FROM notification_queue WHERE school_id = ? AND recipient = ? AND body = ?", [$sid, $g['phone'], $body])) { continue; }
            DB::query("INSERT INTO notification_queue (school_id, channel, recipient, body) VALUES (?, 'sms', ?, ?)", [$sid, $g['phone'], $body]);
        }
    }

    /** ملخص الحضور لكل طالب في نطاق تاريخ/شعبة، مع نسبة الحضور. */
    public static function summary(string $from, string $to, ?int $sectionId = null): array
    {
        Auth::authorizeAny('attendance.view', 'reports.view');   // التقرير مشروع لمن يملك «التقارير» أيضًا
        $sid = Tenant::require(); $scope = Scope::sectionIds();
        $w = 'a.school_id = ? AND a.session_date BETWEEN ? AND ?'; $p = [$sid, $from, $to];
        if ($sectionId) { if ($scope !== null && !in_array($sectionId, $scope, true)) { throw new HttpException(403, 'خارج نطاقك'); } $w .= ' AND a.section_id = ?'; $p[] = $sectionId; }
        elseif ($scope !== null) { $w .= $scope ? ' AND a.section_id IN (' . implode(',', $scope) . ')' : ' AND 1=0'; }
        $rows = DB::all("SELECT s.id AS student_id, s.full_name, sc.name AS section_name, g.name AS grade_name,
              SUM(r.status = 'present') AS present, SUM(r.status = 'absent') AS absent, SUM(r.status = 'late') AS late, SUM(r.status = 'excused') AS excused, COUNT(*) AS total
            FROM attendance_records r JOIN attendance_sessions a ON a.id = r.session_id JOIN student_enrollments e ON e.id = r.enrollment_id
            JOIN students s ON s.id = e.student_id JOIN sections sc ON sc.id = a.section_id JOIN grade_levels g ON g.id = sc.grade_level_id
            WHERE $w GROUP BY s.id, s.full_name, sc.name, g.name ORDER BY g.sort_order, sc.name, s.full_name", $p);
        foreach ($rows as &$r) {
            foreach (['present', 'absent', 'late', 'excused', 'total'] as $k) { $r[$k] = (int)$r[$k]; }
            $r['attendance_pct'] = $r['total'] ? round((($r['present'] + $r['late'] + $r['excused']) / $r['total']) * 100, 1) : null;
            $r['absence_pct'] = $r['total'] ? round($r['absent'] / $r['total'] * 100, 1) : null;
        }
        return $rows;
    }

    /** الطلاب كثيرو الغياب: غياب ≥ عدد الأيام أو نسبة الغياب ≥ الحد (قابلان للتهيئة). */
    public static function frequentAbsentees(string $from, string $to, int $minAbsences = 3, float $minPct = 20.0, ?int $sectionId = null): array
    {
        return array_values(array_filter(self::summary($from, $to, $sectionId), fn($r) => $r['absent'] >= $minAbsences || ($r['absence_pct'] ?? 0) >= $minPct));
    }

    /** نسبة حضور يومية لنطاق (لخط الاتجاه). */
    public static function dailyTrend(string $from, string $to, ?int $sectionId = null): array
    {
        Auth::authorizeAny('attendance.view', 'reports.view');
        $scope = Scope::sectionIds(); $w = 'a.school_id = ? AND a.session_date BETWEEN ? AND ?'; $p = [Tenant::require(), $from, $to];
        if ($sectionId) { $w .= ' AND a.section_id = ?'; $p[] = $sectionId; }
        if ($scope !== null) { $w .= $scope ? ' AND a.section_id IN (' . implode(',', $scope) . ')' : ' AND 1=0'; }
        return DB::all("SELECT a.session_date AS d, ROUND(100 * SUM(r.status IN ('present','late','excused')) / COUNT(*), 1) AS pct FROM attendance_records r JOIN attendance_sessions a ON a.id = r.session_id WHERE $w GROUP BY a.session_date ORDER BY a.session_date", $p);
    }
}
