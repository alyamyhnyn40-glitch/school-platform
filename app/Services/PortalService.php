<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\{Auth, DB, HttpException, Tenant};

/** بوابة الطالب وولي الأمر: قراءة فقط، لأبنائهم/لأنفسهم فقط، والدرجات المعتمدة وحدها. */
final class PortalService
{
    /** معرّفات الطلاب المسموح بها للمستخدم الحالي. */
    public static function studentIds(): array
    {
        Auth::authorize('portal.view');
        return match (Auth::type()) {
            'guardian' => Scope::guardianStudentIds(),
            'student' => array_map('intval', array_column(DB::all('SELECT id FROM students WHERE user_id = ? AND school_id = ? AND deleted_at IS NULL', [Auth::id(), Tenant::require()]), 'id')),
            default => throw new HttpException(403, 'هذه البوابة للطلاب وأولياء الأمور'),
        };
    }
    public static function assertStudent(int $studentId): void
    {
        if (!in_array($studentId, self::studentIds(), true)) { throw new HttpException(403, 'لا تملك صلاحية الاطلاع على هذا الطالب'); }
    }

    public static function children(): array
    {
        $ids = self::studentIds(); if (!$ids) { return []; }
        $year = AcademicService::activeYear();
        return DB::all('SELECT s.id, s.full_name, s.student_no, s.status, g.name AS grade_name, sc.name AS section_name FROM students s
            LEFT JOIN student_enrollments e ON e.student_id = s.id AND e.academic_year_id = ? AND e.status = "active" LEFT JOIN sections sc ON sc.id = e.section_id LEFT JOIN grade_levels g ON g.id = sc.grade_level_id
            WHERE s.school_id = ? AND s.id IN (' . implode(',', $ids) . ') AND s.deleted_at IS NULL ORDER BY s.full_name', [$year['id'] ?? 0, Tenant::require()]);
    }

    public static function overview(int $studentId): array
    {
        self::assertStudent($studentId);
        $sid = Tenant::require(); $year = AcademicService::activeYear();
        $stu = DB::one('SELECT id, full_name, student_no, status FROM students WHERE id = ? AND school_id = ?', [$studentId, $sid]) ?? throw new HttpException(404, 'الطالب غير موجود');
        $enr = $year ? DB::one("SELECT e.id, e.section_id, g.name AS grade_name, sc.name AS section_name FROM student_enrollments e JOIN sections sc ON sc.id = e.section_id JOIN grade_levels g ON g.id = sc.grade_level_id
            WHERE e.student_id = ? AND e.academic_year_id = ? AND e.school_id = ?", [$studentId, $year['id'], $sid]) : null;
        $out = ['student' => $stu + ['grade_name' => $enr['grade_name'] ?? null, 'section_name' => $enr['section_name'] ?? null], 'year' => $year, 'attendance' => null, 'recent' => [], 'terms' => [], 'cards' => []];
        if (!$enr) { return $out; }
        $from = date('Y-m-d', strtotime('-30 day'));
        $a = DB::one("SELECT SUM(r.status='present') p, SUM(r.status='absent') a, SUM(r.status='late') l, SUM(r.status='excused') x, COUNT(*) t FROM attendance_records r JOIN attendance_sessions s ON s.id = r.session_id
            WHERE r.enrollment_id = ? AND r.school_id = ? AND s.session_date >= ?", [$enr['id'], $sid, $from]);
        $t = (int)$a['t']; $out['attendance'] = ['present' => (int)$a['p'], 'absent' => (int)$a['a'], 'late' => (int)$a['l'], 'excused' => (int)$a['x'], 'total' => $t, 'pct' => $t ? round((((int)$a['p'] + (int)$a['l'] + (int)$a['x']) / $t) * 100, 1) : null, 'from' => $from];
        $out['recent'] = DB::all("SELECT s.session_date AS d, r.status, r.note FROM attendance_records r JOIN attendance_sessions s ON s.id = r.session_id WHERE r.enrollment_id = ? AND r.school_id = ? AND r.status <> 'present' ORDER BY s.session_date DESC LIMIT 10", [$enr['id'], $sid]);
        foreach ((new \App\Repositories\TermRepository())->where(['academic_year_id' => (int)$year['id']], 'term_no') as $term) {
            $res = ResultsService::compute((int)$term['id'], (int)$enr['section_id'], true);
            $row = null; foreach ($res['students'] as $x) { if ((int)$x['student_id'] === $studentId) { $row = $x; } }
            if (!$row || $row['average'] === null) { continue; }
            $subs = []; foreach ($res['subjects'] as $sub) { $y = $row['subjects'][$sub['id']]; if ($y['has_assessments']) { $subs[] = ['name' => $sub['name'], 'total' => $y['total'], 'percent' => $y['percent'], 'letter' => $y['letter'], 'passed' => $y['passed']]; } }
            $out['terms'][] = ['name' => $term['name'], 'subjects' => $subs, 'average' => $row['average'], 'letter' => $row['letter'], 'rank' => $row['rank'], 'class_size' => count($res['students']), 'result' => $row['result'], 'max' => $res['scheme']['max_score'], 'provisional' => !$row['complete'] || !$row['all_approved']];
        }
        foreach (DB::all("SELECT rc.id, rc.snapshot, t.name AS term_name FROM report_cards rc JOIN student_enrollments e ON e.id = rc.enrollment_id LEFT JOIN terms t ON t.id = rc.term_id WHERE e.student_id = ? AND rc.school_id = ? ORDER BY rc.id DESC", [$studentId, $sid]) as $c) {
            $snap = json_decode($c['snapshot'], true); if (!empty($snap['incomplete'])) { continue; }   // المسوّدات لا تظهر للأسرة
            $out['cards'][] = ['id' => (int)$c['id'], 'term' => $c['term_name'] ?: 'النتيجة النهائية', 'average' => $snap['average'], 'result' => $snap['result']];
        }
        return $out;
    }
}
