<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\{Auth, DB, HttpException, Tenant};

/** لوحة الأداء الأكاديمي وتقاريرها: مؤشرات، نسب النجاح والرسوب، المواد، توزيع المعدلات، والأوائل — لفصل أو للسنة كاملة. */
final class AcademicReportService
{
    public const BUCKETS = ['أقل من 50' => [0, 50], '50–59' => [50, 60], '60–69' => [60, 70], '70–79' => [70, 80], '80–89' => [80, 90], '90–100' => [90, 100.01]];

    /** @param ?int $termId null = النتيجة السنوية @return array */
    public static function build(int $yearId, ?int $termId, ?int $gradeId = null, int $top = 10): array
    {
        Auth::authorize('grades.view'); $sid = Tenant::require();
        $year = DB::one('SELECT * FROM academic_years WHERE id = ? AND school_id = ?', [$yearId, $sid]) ?? throw new HttpException(404, 'السنة غير موجودة');
        if ($termId && !DB::value('SELECT 1 FROM terms WHERE id = ? AND academic_year_id = ?', [$termId, $yearId])) { throw new HttpException(422, 'الفصل لا يتبع السنة المختارة'); }
        $secs = DB::all('SELECT sc.id, sc.name, sc.grade_level_id, g.name AS grade, g.sort_order FROM sections sc JOIN grade_levels g ON g.id = sc.grade_level_id WHERE sc.school_id = ? AND sc.academic_year_id = ?' . ($gradeId ? ' AND sc.grade_level_id = ?' : '') . ' ORDER BY g.sort_order, sc.name', $gradeId ? [$sid, $yearId, $gradeId] : [$sid, $yearId]);
        $students = []; $bySection = []; $byGrade = []; $bySubject = []; $passMark = null; $max = 100.0;
        foreach ($secs as $sc) {
            if (!Scope::canSeeSection((int)$sc['id'])) { continue; }
            try { $r = $termId ? ResultsService::compute($termId, (int)$sc['id'], false) : ResultsService::yearCompute($yearId, (int)$sc['id'], false); } catch (\Throwable) { continue; }
            $max = (float)($r['scheme']['max_score'] ?? 100); $passMark ??= $r['scheme']['pass_score'] ?? null;
            $avgs = []; $pass = 0; $fail = 0;
            foreach ($r['students'] as $st) {
                if ($st['average'] === null) { continue; }
                $avgs[] = (float)$st['average']; $st['result'] === 'ناجح' ? $pass++ : $fail++;
                $students[] = ['student_id' => (int)$st['student_id'], 'name' => $st['full_name'], 'student_no' => $st['student_no'], 'grade' => $sc['grade'], 'grade_id' => (int)$sc['grade_level_id'], 'section' => $sc['name'], 'average' => (float)$st['average'], 'letter' => $st['letter'], 'result' => $st['result'], 'failed' => (int)$st['failed'], 'rank_in_section' => $st['rank'] ?? null];
                foreach ($r['subjects'] as $sub) {
                    $x = $st['subjects'][$sub['id']] ?? null; if (!$x || empty($x['has_assessments'])) { continue; }
                    $k = $sub['name']; $bySubject[$k] ??= ['subject' => $k, 'sum' => 0.0, 'n' => 0, 'fail' => 0];
                    $bySubject[$k]['sum'] += (float)$x['percent']; $bySubject[$k]['n']++; if ($x['passed'] === false) { $bySubject[$k]['fail']++; }
                }
            }
            if (!$avgs) { continue; }
            $bySection[] = ['section_id' => (int)$sc['id'], 'grade' => $sc['grade'], 'section' => $sc['name'], 'students' => count($avgs), 'average' => round(array_sum($avgs) / count($avgs), 2), 'highest' => max($avgs), 'lowest' => min($avgs), 'pass' => $pass, 'fail' => $fail, 'pass_rate' => round($pass / count($avgs) * 100, 1)];
            $g = (int)$sc['grade_level_id']; $byGrade[$g] ??= ['grade' => $sc['grade'], 'sum' => 0.0, 'n' => 0, 'pass' => 0, 'fail' => 0];
            $byGrade[$g]['sum'] += array_sum($avgs); $byGrade[$g]['n'] += count($avgs); $byGrade[$g]['pass'] += $pass; $byGrade[$g]['fail'] += $fail;
        }
        $n = count($students); $passN = count(array_filter($students, fn($s) => $s['result'] === 'ناجح'));
        $dist = array_fill_keys(array_keys(self::BUCKETS), 0); foreach ($students as $s) { $p = $s['average'] / ($max ?: 100) * 100; foreach (self::BUCKETS as $k => [$lo, $hi]) { if ($p >= $lo && $p < $hi) { $dist[$k]++; break; } } }
        $subjects = array_map(fn($x) => ['subject' => $x['subject'], 'average' => round($x['sum'] / max(1, $x['n']), 2), 'students' => $x['n'], 'fail' => $x['fail'], 'pass_rate' => round(($x['n'] - $x['fail']) / max(1, $x['n']) * 100, 1)], array_values($bySubject));
        usort($subjects, fn($a, $b) => $a['average'] <=> $b['average']);
        $grades = array_map(fn($x) => ['grade' => $x['grade'], 'average' => round($x['sum'] / max(1, $x['n']), 2), 'students' => $x['n'], 'pass' => $x['pass'], 'fail' => $x['fail'], 'pass_rate' => round($x['pass'] / max(1, $x['n']) * 100, 1)], array_values($byGrade));
        usort($students, fn($a, $b) => $b['average'] <=> $a['average'] ?: strcmp($a['name'], $b['name']));
        $ranks = GradeCalculator::rank(array_map(fn($s) => $s['average'], $students)); foreach ($students as $i => &$s) { $s['rank'] = $ranks[$i]; } unset($s);
        $topByGrade = []; foreach ($students as $s) { if (count($topByGrade[$s['grade']] ?? []) < 3) { $topByGrade[$s['grade']][] = $s; } }
        return ['year' => $year, 'term_id' => $termId, 'grade_id' => $gradeId, 'max' => $max,
            'kpi' => ['students' => $n, 'average' => $n ? round(array_sum(array_column($students, 'average')) / $n, 2) : null, 'pass' => $passN, 'fail' => $n - $passN, 'pass_rate' => $n ? round($passN / $n * 100, 1) : null, 'sections' => count($bySection)],
            'grades' => $grades, 'sections' => $bySection, 'subjects' => $subjects, 'distribution' => $dist,
            'top' => array_slice(array_values(array_filter($students, fn($s) => $s['result'] === 'ناجح')), 0, $top), 'top_by_grade' => $topByGrade,
            'failing' => array_values(array_filter($students, fn($s) => $s['result'] !== 'ناجح')), 'students' => $students];
    }

    /** صفوف Excel لتقرير. */
    public static function exportRows(array $d, string $kind): array
    {
        Auth::authorize('grades.view');
        return match ($kind) {
            'top' => array_merge([['الترتيب', 'الطالب', 'رقم القيد', 'الصف', 'الشعبة', 'المعدل', 'التقدير']], array_map(fn($s) => [$s['rank'], $s['name'], (string)$s['student_no'], $s['grade'], $s['section'], $s['average'], (string)$s['letter']], $d['top'])),
            'sections' => array_merge([['الصف', 'الشعبة', 'الطلاب', 'المتوسط', 'الأعلى', 'الأدنى', 'الناجحون', 'الراسبون', 'نسبة النجاح %']], array_map(fn($x) => [$x['grade'], $x['section'], $x['students'], $x['average'], $x['highest'], $x['lowest'], $x['pass'], $x['fail'], $x['pass_rate']], $d['sections'])),
            'subjects' => array_merge([['المادة', 'المتوسط %', 'الطلاب', 'الراسبون', 'نسبة النجاح %']], array_map(fn($x) => [$x['subject'], $x['average'], $x['students'], $x['fail'], $x['pass_rate']], $d['subjects'])),
            'failing' => array_merge([['الطالب', 'رقم القيد', 'الصف', 'الشعبة', 'المعدل', 'النتيجة']], array_map(fn($s) => [$s['name'], (string)$s['student_no'], $s['grade'], $s['section'], $s['average'], (string)$s['result']], $d['failing'])),
            default => array_merge([['الترتيب', 'الطالب', 'رقم القيد', 'الصف', 'الشعبة', 'المعدل', 'التقدير', 'النتيجة']], array_map(fn($s) => [$s['rank'], $s['name'], (string)$s['student_no'], $s['grade'], $s['section'], $s['average'], (string)$s['letter'], (string)$s['result']], $d['students'])),
        };
    }
}
