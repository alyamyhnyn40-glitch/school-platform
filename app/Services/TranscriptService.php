<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\{Auth, DB, HttpException, Tenant};

/**
 * السجل الأكاديمي للطالب عبر كل السنوات: نتائج كل فصل (المعدل الفصلي) والنتيجة السنوية (المعدل السنوي) والمعدل التراكمي،
 * مع GPA إن عرّف نظام الدرجات نقاطًا للتقديرات. السنوات المغلقة تُقرأ من لقطة مجمَّدة (academic_records) لا تتغير بتعديلات لاحقة.
 */
final class TranscriptService
{
    /** @return array{student: array, years: list<array>, cumulative: array} */
    public static function forStudent(int $studentId): array
    {
        $st = StudentService::profile($studentId)['student'] ?? null;   // يفحص الصلاحية والنطاق
        if (!$st) { throw new HttpException(404, 'الطالب غير موجود'); }
        $st = array_intersect_key($st, array_flip(['id', 'full_name', 'student_no', 'status', 'gender', 'birth_date']));   // لا تُمرَّر الحقول الحساسة
        if (!Auth::can('grades.view') && !Auth::can('counseling.manage')) { throw (new HttpException(403, 'لا تملك صلاحية عرض الدرجات'))->withPerm('grades.view'); }
        return self::build($studentId, false) + ['student' => $st];
    }
    /** للبوابة (الطالب/وليه): التقييمات المعتمدة فقط. يُستدعى بعد فحص PortalService. */
    public static function forPortal(int $studentId): array
    {
        $st = DB::one('SELECT id, full_name, student_no FROM students WHERE id = ? AND school_id = ? AND deleted_at IS NULL', [$studentId, Tenant::require()]) ?? throw new HttpException(404, 'الطالب غير موجود');
        return self::build($studentId, true) + ['student' => $st];
    }

    private static function build(int $studentId, bool $onlyApproved): array
    {
        $sid = Tenant::require(); $years = [];
        $enrs = DB::all("SELECT e.id AS enrollment_id, e.status, e.section_id, e.academic_year_id, y.name AS year_name, y.status AS year_status, y.starts_on, sc.name AS section_name, g.name AS grade_name
            FROM student_enrollments e JOIN academic_years y ON y.id = e.academic_year_id JOIN sections sc ON sc.id = e.section_id JOIN grade_levels g ON g.id = sc.grade_level_id
            WHERE e.student_id = ? AND e.school_id = ? ORDER BY y.starts_on, e.id", [$studentId, $sid]);
        foreach ($enrs as $e) {
            $frozen = DB::all('SELECT * FROM academic_records WHERE enrollment_id = ? ORDER BY term_id IS NULL, term_id, subject_name', [$e['enrollment_id']]);
            $years[] = $frozen ? self::fromFrozen($e, $frozen) : self::live($e, $onlyApproved);
        }
        $years = array_values(array_filter($years));
        return ['years' => $years, 'cumulative' => self::cumulative($years)];
    }

    /** يحسب سنة من البيانات الحية. */
    private static function live(array $e, bool $onlyApproved): ?array
    {
        $sid = Tenant::require(); $eid = (int)$e['enrollment_id']; $terms = [];
        $scheme = GradingService::schemeForSection((int)$e['section_id']); $hours = [];
        foreach (DB::all('SELECT ss.id, ss.credit_hours FROM section_subjects ss WHERE ss.section_id = ?', [$e['section_id']]) as $h) { $hours[(int)$h['id']] = (float)$h['credit_hours']; }
        foreach (DB::all('SELECT * FROM terms WHERE academic_year_id = ? AND school_id = ? ORDER BY term_no', [$e['academic_year_id'], $sid]) as $t) {
            try { $r = ResultsService::compute((int)$t['id'], (int)$e['section_id'], $onlyApproved); } catch (\Throwable) { continue; }
            $row = null; foreach ($r['students'] as $s) { if ((int)$s['enrollment_id'] === $eid) { $row = $s; } } if (!$row) { continue; }
            $subs = []; foreach ($r['subjects'] as $sub) { $x = $row['subjects'][$sub['id']] ?? null; if (!$x || empty($x['has_assessments'])) { continue; } $subs[] = self::subjRow($sub['name'], $x, $hours[(int)$sub['id']] ?? 1.0, $scheme); }
            if (!$subs) { continue; }
            $terms[] = ['term_id' => (int)$t['id'], 'name' => $t['name'], 'subjects' => $subs, 'average' => $row['average'], 'letter' => $row['letter'], 'rank' => $row['rank'] ?? null, 'size' => count($r['students']), 'result' => $row['result'], 'gpa' => self::gpa($subs), 'complete' => $row['complete']];
        }
        $annual = null;
        if ($terms) {
            try { $y = ResultsService::yearCompute((int)$e['academic_year_id'], (int)$e['section_id'], $onlyApproved); } catch (\Throwable) { $y = null; }
            foreach ($y['students'] ?? [] as $s) {
                if ((int)$s['enrollment_id'] !== $eid) { continue; } $subs = [];
                foreach ($y['subjects'] as $sub) { $x = $s['subjects'][$sub['id']] ?? null; if ($x && !empty($x['has_assessments'])) { $subs[] = self::subjRow($sub['name'], $x, $hours[(int)$sub['id']] ?? 1.0, $scheme); } }
                $annual = ['subjects' => $subs, 'average' => $s['average'], 'letter' => $s['letter'], 'rank' => $s['rank'] ?? null, 'size' => count($y['students']), 'result' => $s['result'], 'gpa' => self::gpa($subs), 'hours' => array_sum(array_column($subs, 'credit_hours')), 'complete' => $s['complete']];
            }
        }
        return self::yearHead($e) + ['terms' => $terms, 'annual' => $annual, 'frozen' => false, 'max_score' => (float)$scheme['max_score']];
    }
    private static function yearHead(array $e): array
    {
        return ['enrollment_id' => (int)$e['enrollment_id'], 'year_id' => (int)$e['academic_year_id'], 'year' => $e['year_name'], 'year_status' => $e['year_status'], 'grade' => $e['grade_name'], 'section' => $e['section_name'], 'status' => $e['status']];
    }
    private static function subjRow(string $name, array $x, float $hours, array $scheme): array
    {
        $pts = null; if ($x['letter'] !== null) { foreach ($scheme['scales'] as $sc) { if ($sc['label'] === $x['letter'] && $sc['gpa_points'] !== null && (float)$sc['gpa_points'] > 0) { $pts = (float)$sc['gpa_points']; } } }
        return ['subject' => $name, 'total' => (float)$x['total'], 'percent' => (float)$x['percent'], 'letter' => $x['letter'], 'passed' => $x['passed'], 'credit_hours' => $hours, 'gpa_points' => $pts];
    }
    /** GPA موزون بالساعات إن وُجدت نقاط للتقديرات. */
    private static function gpa(array $subs): ?float
    {
        $acc = 0.0; $h = 0.0; foreach ($subs as $s) { if ($s['gpa_points'] === null) { continue; } $w = $s['credit_hours'] > 0 ? $s['credit_hours'] : 1.0; $acc += $s['gpa_points'] * $w; $h += $w; }
        return $h > 0 ? round($acc / $h, 2) : null;
    }
    private static function fromFrozen(array $e, array $rows): array
    {
        $terms = []; $annual = null; $max = null;
        foreach ($rows as $r) {
            $key = $r['term_id'] === null ? 'annual' : (int)$r['term_id'];
            if ($key === 'annual') { $annual ??= ['subjects' => [], 'average' => null, 'letter' => null, 'rank' => null, 'size' => null, 'result' => null, 'gpa' => null, 'hours' => 0, 'complete' => true]; $bucket = &$annual; }
            else { $terms[$key] ??= ['term_id' => $key, 'name' => $r['term_name'], 'subjects' => [], 'average' => null, 'letter' => null, 'rank' => null, 'size' => null, 'result' => null, 'gpa' => null, 'complete' => true]; $bucket = &$terms[$key]; }
            if ($r['subject_id'] === null) { $bucket['average'] = $r['percent'] === null ? null : (float)$r['percent']; $bucket['letter'] = $r['letter']; $bucket['rank'] = $r['rank_in_section'] === null ? null : (int)$r['rank_in_section']; $bucket['result'] = $r['result']; $bucket['gpa'] = $r['gpa_points'] === null ? null : (float)$r['gpa_points']; $max = $r['max_score'] ?? $max; }
            else { $bucket['subjects'][] = ['subject' => $r['subject_name'], 'total' => (float)$r['total'], 'percent' => (float)$r['percent'], 'letter' => $r['letter'], 'passed' => $r['passed'] === null ? null : (bool)$r['passed'], 'credit_hours' => (float)$r['credit_hours'], 'gpa_points' => $r['gpa_points'] === null ? null : (float)$r['gpa_points']]; }
            unset($bucket);
        }
        if ($annual) { $annual['hours'] = array_sum(array_column($annual['subjects'], 'credit_hours')); }
        return self::yearHead($e) + ['terms' => array_values($terms), 'annual' => $annual, 'frozen' => true, 'max_score' => $max === null ? 100.0 : (float)$max];
    }
    /** المعدل التراكمي: متوسط المعدلات السنوية (موزون بساعات كل سنة إن فُعّل الحساب الموزون)، وGPA تراكمي موزون بالساعات. */
    public static function cumulative(array $years): array
    {
        $avgs = []; $w = []; $gp = 0.0; $gh = 0.0; $hours = 0.0; $passed = 0; $counted = 0;
        foreach ($years as $y) {
            $a = $y['annual']; if (!$a || $a['average'] === null) { continue; }
            $avgs[] = (float)$a['average']; $w[] = (float)($a['hours'] ?: 1); $hours += (float)$a['hours']; $counted++;
            if ($a['result'] === 'ناجح') { $passed++; }
            foreach ($a['subjects'] as $s) { if ($s['gpa_points'] !== null) { $h = $s['credit_hours'] > 0 ? $s['credit_hours'] : 1.0; $gp += $s['gpa_points'] * $h; $gh += $h; } }
        }
        return ['average' => ResultsService::mean($avgs, ResultsService::creditWeighted() ? $w : null, 2), 'gpa' => $gh > 0 ? round($gp / $gh, 2) : null, 'years' => $counted, 'years_passed' => $passed, 'hours' => $hours];
    }

    /** يجمّد نتائج سنة (لكل طلابها) في academic_records. يُستدعى قبل إغلاق السنة/الترفيع. @return int عدد الطلاب */
    public static function freezeYear(int $yearId): int
    {
        Auth::authorize('academic.promote'); $sid = Tenant::require();
        $y = DB::one('SELECT * FROM academic_years WHERE id = ? AND school_id = ?', [$yearId, $sid]) ?? throw new HttpException(404, 'السنة غير موجودة');
        $terms = DB::all('SELECT * FROM terms WHERE academic_year_id = ? AND school_id = ? ORDER BY term_no', [$yearId, $sid]); $n = 0;
        DB::transaction(function () use ($y, $yearId, $terms, $sid, &$n) {
            DB::query('DELETE FROM academic_records WHERE academic_year_id = ? AND school_id = ?', [$yearId, $sid]);
            foreach (DB::all('SELECT sc.id, sc.name, g.name AS grade FROM sections sc JOIN grade_levels g ON g.id = sc.grade_level_id WHERE sc.academic_year_id = ? AND sc.school_id = ?', [$yearId, $sid]) as $sec) {
                $scheme = GradingService::schemeForSection((int)$sec['id']); $hours = []; foreach (DB::all('SELECT id, credit_hours FROM section_subjects WHERE section_id = ?', [$sec['id']]) as $h) { $hours[(int)$h['id']] = (float)$h['credit_hours']; }
                $sets = [];
                foreach ($terms as $t) { try { $sets[] = [$t, ResultsService::compute((int)$t['id'], (int)$sec['id'], false)]; } catch (\Throwable) {} }
                try { $sets[] = [null, ResultsService::yearCompute($yearId, (int)$sec['id'], false)]; } catch (\Throwable) {}
                $seen = [];
                foreach ($sets as [$t, $r]) {
                    foreach ($r['students'] as $s) {
                        if ($s['average'] === null) { continue; } $seen[(int)$s['enrollment_id']] = true; $subs = [];
                        foreach ($r['subjects'] as $sub) {
                            $x = $s['subjects'][$sub['id']] ?? null; if (!$x || empty($x['has_assessments'])) { continue; } $row = self::subjRow($sub['name'], $x, $hours[(int)$sub['id']] ?? 1.0, $scheme); $subs[] = $row;
                            self::rec($sid, $s, $y, $t, $sec, (int)($sub['subject_id'] ?? 0) ?: null, $row['subject'], $row['percent'], $row['total'], (float)$scheme['max_score'], $row['letter'], $row['gpa_points'], $row['passed'] === null ? null : (int)$row['passed'], $row['credit_hours'], null, null);
                        }
                        self::rec($sid, $s, $y, $t, $sec, null, null, (float)$s['average'], null, (float)$scheme['max_score'], $s['letter'], self::gpa($subs), null, null, $s['rank'] ?? null, $s['result']);
                    }
                }
                $n += count($seen);
            }
        });
        AuditService::log('year.freeze_records', 'academic_year', $yearId, null, ['students' => $n]);
        return $n;
    }
    private static function rec(int $sid, array $s, array $y, ?array $t, array $sec, ?int $subjectId, ?string $subject, ?float $pct, ?float $total, float $max, ?string $letter, ?float $gpa, ?int $passed, ?float $hours, ?int $rank, ?string $result): void
    {
        DB::query('INSERT INTO academic_records (school_id, student_id, enrollment_id, academic_year_id, term_id, subject_id, year_name, term_name, grade_name, section_name, subject_name, percent, total, max_score, letter, gpa_points, passed, credit_hours, rank_in_section, result) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
            [$sid, $s['student_id'], $s['enrollment_id'], $y['id'], $t['id'] ?? null, $subjectId, $y['name'], $t['name'] ?? null, $sec['grade'], $sec['name'], $subject, $pct, $total, $max, $letter, $gpa, $passed, $hours, $rank, $result]);
    }
}
