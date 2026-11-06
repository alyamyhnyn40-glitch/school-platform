<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\{Auth, DB, HttpException, Tenant};

/** تحليلات متقدمة للقراءة فقط. كل رقم هنا وصفي؛ الارتباط لا يعني السببية والتوقع استرشادي. */
final class AnalyticsService
{
    private static function year(): array { return AcademicService::activeYear() ?? throw new HttpException(422, 'لا توجد سنة دراسية فعّالة'); }

    /** نسبة الدرجات المحصَّلة لكل تسجيل (enrollment) في فصل: مجموع الدرجات ÷ مجموع الدرجات العظمى للتقييمات المرصودة، بالمعتمد والمقفل فقط. */
    private static function enrollmentPercents(int $termId): array
    {
        $out = [];
        foreach (DB::all("SELECT g.enrollment_id AS eid, SUM(g.score) AS got, SUM(a.max_score) AS mx FROM grades g JOIN assessments a ON a.id = g.assessment_id
            WHERE g.school_id = ? AND a.term_id = ? AND a.status IN ('approved','locked') AND g.score IS NOT NULL AND g.is_absent = 0 GROUP BY g.enrollment_id", [Tenant::require(), $termId]) as $r) {
            if ((float)$r['mx'] > 0) { $out[(int)$r['eid']] = round((float)$r['got'] / (float)$r['mx'] * 100, 2); }
        }
        return $out;
    }
    public static function pearson(array $x, array $y): ?float
    {
        $n = count($x); if ($n < 3) { return null; } $mx = array_sum($x) / $n; $my = array_sum($y) / $n; $sxy = $sx = $sy = 0.0;
        for ($i = 0; $i < $n; $i++) { $dx = $x[$i] - $mx; $dy = $y[$i] - $my; $sxy += $dx * $dy; $sx += $dx * $dx; $sy += $dy * $dy; }
        if ($sx <= 0 || $sy <= 0) { return null; } return round($sxy / sqrt($sx * $sy), 3);
    }
    /** انحدار خطي بسيط للقيم المتتالية؛ يعيد القيمة التالية المتوقعة (مقيَّدة 0–100) أو null إن قلّت النقاط عن 3. */
    public static function project(array $values): ?float
    {
        $n = count($values); if ($n < 3) { return null; } $xs = range(1, $n); $mx = array_sum($xs) / $n; $my = array_sum($values) / $n; $num = $den = 0.0;
        foreach ($xs as $i => $x) { $num += ($x - $mx) * ($values[$i] - $my); $den += ($x - $mx) ** 2; }
        $b = $den > 0 ? $num / $den : 0; return round(max(0, min(100, $my + $b * (($n + 1) - $mx))), 1);
    }

    public static function overview(): array
    {
        Auth::authorize('analytics.view'); $sid = Tenant::require(); $y = self::year();
        $status = []; foreach (DB::all('SELECT status, COUNT(*) n FROM students WHERE school_id = ? AND deleted_at IS NULL GROUP BY status', [$sid]) as $r) { $status[$r['status']] = (int)$r['n']; }
        $att = DB::one("SELECT SUM(r.status IN ('present','late')) AS p, SUM(r.status = 'excused') AS x, COUNT(*) AS n FROM attendance_records r JOIN attendance_sessions a ON a.id = r.session_id WHERE r.school_id = ? AND a.kind <> 'live' AND a.session_date >= ?", [$sid, date('Y-m-d', strtotime('-30 day'))]);
        $den = (int)$att['n'] - (int)$att['x'];
        $risk = []; foreach (DB::all("SELECT indicator_code c, COUNT(DISTINCT student_id) n FROM risk_flags WHERE school_id = ? AND status = 'open' GROUP BY indicator_code", [$sid]) as $r) { $risk[$r['c']] = (int)$r['n']; }
        return ['year' => $y['name'], 'students_by_status' => $status, 'attendance_rate_30d' => $den > 0 ? round((int)$att['p'] / $den * 100, 1) : null, 'open_risk_by_indicator' => $risk,
            'open_risk_students' => (int)DB::value("SELECT COUNT(DISTINCT student_id) FROM risk_flags WHERE school_id = ? AND status = 'open'", [$sid])];
    }
    /** متوسط ونسبة نجاح كل مادة في فصل (نجاح = نسبة الطالب في المادة ≥ حد النجاح الافتراضي للمدرسة). */
    public static function subjects(int $termId): array
    {
        Auth::authorize('analytics.view'); $sid = Tenant::require();
        $term = DB::one('SELECT * FROM terms WHERE id = ? AND school_id = ?', [$termId, $sid]) ?? throw new HttpException(404, 'الفصل غير موجود');
        $pass = (float)(DB::value('SELECT pass_score / max_score * 100 FROM grading_schemes WHERE school_id = ? ORDER BY is_default DESC, id LIMIT 1', [$sid]) ?? 50);
        $rows = DB::all("SELECT sub.name AS subject, g.enrollment_id AS eid, SUM(g.score) AS got, SUM(a.max_score) AS mx FROM grades g JOIN assessments a ON a.id = g.assessment_id JOIN section_subjects ss ON ss.id = a.section_subject_id JOIN subjects sub ON sub.id = ss.subject_id
            WHERE g.school_id = ? AND a.term_id = ? AND a.status IN ('approved','locked') AND g.score IS NOT NULL AND g.is_absent = 0 GROUP BY sub.name, g.enrollment_id", [$sid, $termId]);
        $by = []; foreach ($rows as $r) { if ((float)$r['mx'] > 0) { $by[$r['subject']][] = (float)$r['got'] / (float)$r['mx'] * 100; } }
        $out = []; foreach ($by as $name => $v) { $out[] = ['subject' => $name, 'students' => count($v), 'average' => round(array_sum($v) / count($v), 1), 'pass_rate' => round(count(array_filter($v, fn($p) => $p >= $pass)) / count($v) * 100, 1)]; }
        usort($out, fn($a, $b) => $a['average'] <=> $b['average']); return ['term' => $term['name'], 'pass_percent' => $pass, 'subjects' => $out];
    }
    /** ارتباط نسبة الحضور بالتحصيل خلال فصل. */
    public static function attendanceVsGrades(int $termId): array
    {
        Auth::authorize('analytics.view'); $sid = Tenant::require();
        $term = DB::one('SELECT * FROM terms WHERE id = ? AND school_id = ?', [$termId, $sid]) ?? throw new HttpException(404, 'الفصل غير موجود');
        $pct = self::enrollmentPercents($termId); $att = [];
        foreach (DB::all("SELECT r.enrollment_id eid, SUM(r.status IN ('present','late')) p, SUM(r.status = 'absent') ab FROM attendance_records r JOIN attendance_sessions a ON a.id = r.session_id WHERE r.school_id = ? AND a.kind <> 'live' AND a.session_date BETWEEN ? AND ? GROUP BY r.enrollment_id", [$sid, $term['starts_on'], $term['ends_on']]) as $r) {
            $d = (int)$r['p'] + (int)$r['ab']; if ($d > 0) { $att[(int)$r['eid']] = (int)$r['p'] / $d * 100; }
        }
        $x = $y = []; $points = []; foreach ($pct as $eid => $g) { if (isset($att[$eid])) { $x[] = $att[$eid]; $y[] = $g; $points[] = ['attendance' => round($att[$eid], 1), 'grade' => $g]; } }
        $r = self::pearson($x, $y); $n = count($x);
        $note = $r === null ? 'بيانات غير كافية (يلزم 3 طلاب على الأقل ببيانات حضور ودرجات متباينة)' : ($n < 10 ? 'عينة صغيرة (' . $n . ' طلاب) — لا تعتمد على هذا الرقم' : 'الارتباط لا يعني السببية');
        return ['term' => $term['name'], 'n' => $n, 'r' => $r, 'note' => $note, 'points' => array_slice($points, 0, 500)];
    }
    /** متوسط كل شعبة عبر فصول السنة الفعّالة + توقع الفصل التالي (استرشادي). */
    public static function sectionTrends(): array
    {
        Auth::authorize('analytics.view'); $sid = Tenant::require(); $y = self::year();
        $terms = DB::all('SELECT id, name FROM terms WHERE school_id = ? AND academic_year_id = ? ORDER BY term_no', [$sid, $y['id']]); $sections = DB::all('SELECT s.id, s.name, g.name AS grade FROM sections s JOIN grade_levels g ON g.id = s.grade_level_id WHERE s.school_id = ? AND s.academic_year_id = ? ORDER BY g.sort_order, s.name', [$sid, $y['id']]);
        $secOf = []; foreach (DB::all('SELECT id, section_id FROM student_enrollments WHERE school_id = ? AND academic_year_id = ?', [$sid, $y['id']]) as $e) { $secOf[(int)$e['id']] = (int)$e['section_id']; }
        $avg = []; foreach ($terms as $t) { $bySec = []; foreach (self::enrollmentPercents((int)$t['id']) as $eid => $p) { if (isset($secOf[$eid])) { $bySec[$secOf[$eid]][] = $p; } } foreach ($bySec as $s => $v) { $avg[$s][$t['id']] = round(array_sum($v) / count($v), 1); } }
        $out = []; foreach ($sections as $s) {
            $series = []; foreach ($terms as $t) { $series[] = $avg[$s['id']][$t['id']] ?? null; } $vals = array_values(array_filter($series, fn($v) => $v !== null));
            $out[] = ['section' => $s['grade'] . ' / ' . $s['name'], 'series' => $series, 'projection' => self::project($vals)];
        }
        return ['terms' => array_column($terms, 'name'), 'sections' => $out, 'note' => 'التوقع خطّي بسيط ويُعرض عند توفر 3 فصول على الأقل؛ استرشادي وليس قرارًا.'];
    }
    /** حركة الطلاب: التحاق وانسحاب/نقل/تخرّج حسب الحالة. */
    public static function movement(): array
    {
        Auth::authorize('analytics.view'); $sid = Tenant::require();
        return ['by_status' => DB::all('SELECT status, COUNT(*) AS n FROM students WHERE school_id = ? AND deleted_at IS NULL GROUP BY status', [$sid]),
            'admissions' => DB::all('SELECT status, COUNT(*) AS n FROM admission_applications WHERE school_id = ? GROUP BY status', [$sid])];
    }
}
