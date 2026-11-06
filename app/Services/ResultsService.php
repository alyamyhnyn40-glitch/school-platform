<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\{Auth, DB, HttpException, Tenant};

/** نتائج الفصل لشعبة: درجات المواد، المعدل، الترتيب، النتيجة، مع علامة الاكتمال. */
final class ResultsService
{
    /** @return array{section: array, term: array, scheme: array, subjects: array, students: array} */
    public static function termResults(int $termId, int $sectionId): array
    {
        Auth::authorize('grades.view');
        if (!Scope::canSeeSection($sectionId)) { throw new HttpException(403, 'خارج نطاقك'); }
        return self::compute($termId, $sectionId, false);
    }

    /** الحساب الفعلي بلا فحص صلاحية (تستدعيه الخدمات بعد فحصها). $onlyApproved: للبوابة، تُحتسب التقييمات المعتمدة فقط. */
    public static function compute(int $termId, int $sectionId, bool $onlyApproved): array
    {
        $sid = Tenant::require();
        $term = DB::one('SELECT * FROM terms WHERE id = ? AND school_id = ?', [$termId, $sid]) ?? throw new HttpException(404, 'الفصل غير موجود');
        $sec = AcademicService::sectionRaw($sectionId);
        if ((int)$sec['academic_year_id'] !== (int)$term['academic_year_id']) { throw new HttpException(422, 'الفصل لا يتبع سنة الشعبة'); }
        $scheme = GradingService::schemeForSection($sectionId);
        // مواد الفصل فقط: المادة المخصصة لفصل آخر لا تظهر (اختلاف مواد الفصل الأول والثاني)
        $subjects = DB::all('SELECT ss.id, ss.subject_id, sub.name, ss.term_id, ss.credit_hours, ss.is_elective FROM section_subjects ss JOIN subjects sub ON sub.id = ss.subject_id WHERE ss.school_id = ? AND ss.section_id = ? AND (ss.term_id IS NULL OR ss.term_id = ?) ORDER BY sub.name', [$sid, $sectionId, $termId]);
        $electives = StudyPlanService::electiveMap($sectionId); $weighted = self::creditWeighted();
        $enrs = DB::all("SELECT e.id AS enrollment_id, s.id AS student_id, s.full_name, s.student_no FROM student_enrollments e JOIN students s ON s.id = e.student_id
            WHERE e.school_id = ? AND e.section_id = ? AND e.status IN ('active','promoted','repeated') AND s.deleted_at IS NULL ORDER BY s.full_name", [$sid, $sectionId]);
        $assessBySubject = []; $maxBySubject = [];
        foreach (DB::all('SELECT id, section_subject_id, type_id, max_score, status FROM assessments WHERE school_id = ? AND term_id = ? ' . ($onlyApproved ? "AND status IN ('approved','locked') " : '') . 'AND section_subject_id IN (SELECT id FROM section_subjects WHERE section_id = ?)', [$sid, $termId, $sectionId]) as $a) { $assessBySubject[$a['section_subject_id']][] = $a; }
        $grades = [];
        foreach (DB::all('SELECT g.assessment_id, g.enrollment_id, g.score, g.is_absent FROM grades g JOIN assessments a ON a.id = g.assessment_id WHERE g.school_id = ? AND a.term_id = ?', [$sid, $termId]) as $g) {
            $grades[$g['enrollment_id']][$g['assessment_id']] = ['score' => $g['score'] === null ? null : (float)$g['score'], 'is_absent' => (bool)$g['is_absent']];
        }
        $students = []; $avgs = [];
        foreach ($enrs as $e) {
            $subs = []; $pcts = []; $failed = 0; $complete = true; $allApproved = true;
            $hrs = [];
            foreach ($subjects as $sub) {
                if ((int)$sub['is_elective'] && empty($electives[(int)$sub['id']][(int)$e['enrollment_id']])) { $subs[$sub['id']] = ['total' => 0, 'percent' => 0, 'letter' => null, 'passed' => null, 'has_assessments' => false, 'not_taken' => true, 'entered' => 0, 'complete' => true]; continue; }   // مادة اختيارية لم يخترها الطالب
                $as = $assessBySubject[$sub['id']] ?? [];
                $calc = GradeCalculator::subject($scheme, $scheme['types'], $as, $grades[$e['enrollment_id']] ?? []);
                foreach ($as as $a) { if ($a['status'] === 'open') { $allApproved = false; } }
                $pass = GradeCalculator::passed($scheme, $calc['total']);
                // لا يُحتسب في المعدل إلا ما لدى الطالب فيه درجة مُدخلة؛ مادة فيها تقييمات ولا درجات لهذا الطالب = ناقصة ومستثناة (مثلًا طالب انضم لاحقًا)
                $hasData = $as && $calc['entered'] > 0;
                if ($hasData) { $pcts[] = $calc['percent']; $hrs[] = (float)$sub['credit_hours']; if (!$pass) { $failed++; } }
                if ($as && !$calc['complete']) { $complete = false; } // مادة بلا أي تقييم في الفصل تُستثنى من المعدل وتُعرض في unassessed
                $subs[$sub['id']] = $calc + ['letter' => $hasData ? GradeCalculator::letter($scheme['scales'], $calc['percent']) : null, 'passed' => $hasData ? $pass : null, 'has_assessments' => $hasData];
            }
            $avg = self::mean($pcts, $weighted ? $hrs : null, (int)$scheme['rounding']);
            $avgs[$e['enrollment_id']] = $avg ?? -1;
            $students[$e['enrollment_id']] = $e + ['subjects' => $subs, 'average' => $avg, 'letter' => $avg === null ? null : GradeCalculator::letter($scheme['scales'], $avg), 'failed' => $failed, 'complete' => $complete, 'all_approved' => $allApproved,
                'result' => $avg === null ? null : ($failed === 0 ? 'ناجح' : "راسب في $failed " . ($failed === 1 ? 'مادة' : 'مواد'))];
        }
        foreach (GradeCalculator::rank($avgs) as $eid => $rk) { $students[$eid]['rank'] = $avgs[$eid] < 0 ? null : $rk; }
        return ['section' => $sec, 'term' => $term, 'unassessed' => array_values(array_map(fn($x) => $x['name'], array_filter($subjects, fn($x) => empty($assessBySubject[$x['id']])))), 'scheme' => ['max_score' => $scheme['max_score'], 'pass_score' => $scheme['pass_score'], 'name' => $scheme['name']], 'subjects' => $subjects, 'students' => array_values($students)];
    }

    /** مقارنة الشعب في متوسط الفصل. */
    public static function compareSections(int $termId): array
    {
        Auth::authorize('grades.view');
        $out = [];
        foreach (AcademicService::sections() as $sc) {
            if (!Scope::canSeeSection((int)$sc['id'])) { continue; }
            $term = DB::value('SELECT academic_year_id FROM terms WHERE id = ? AND school_id = ?', [$termId, Tenant::require()]);
            if ((int)$sc['academic_year_id'] !== (int)$term) { continue; }
            try { $r = self::termResults($termId, (int)$sc['id']); } catch (HttpException) { continue; }
            $avgs = array_values(array_filter(array_column($r['students'], 'average'), fn($v) => $v !== null));
            $out[] = ['section_id' => (int)$sc['id'], 'grade' => $sc['grade_name'], 'section' => $sc['name'], 'students' => count($r['students']), 'with_results' => count($avgs),
                'average' => $avgs ? round(array_sum($avgs) / count($avgs), 2) : null, 'highest' => $avgs ? max($avgs) : null, 'lowest' => $avgs ? min($avgs) : null,
                'passed' => count(array_filter($r['students'], fn($s) => $s['result'] === 'ناجح'))];
        }
        return $out;
    }

    /** النتيجة النهائية للسنة: لكل مادة متوسط درجاتها عبر الفصول التي فيها بيانات (أوزان فصول متساوية)، والمعدل متوسط المواد. */
    public static function yearResults(int $yearId, int $sectionId, bool $onlyApproved = false): array
    {
        Auth::authorize('grades.view');
        if (!Scope::canSeeSection($sectionId)) { throw new HttpException(403, 'خارج نطاقك'); }
        return self::yearCompute($yearId, $sectionId, $onlyApproved);
    }
    /** حساب النتيجة السنوية بلا فحص صلاحية (للخدمات بعد فحصها). */
    public static function yearCompute(int $yearId, int $sectionId, bool $onlyApproved = false): array
    {
        $sid = Tenant::require();
        $terms = (new \App\Repositories\TermRepository())->where(['academic_year_id' => $yearId], 'term_no');
        if (!$terms) { throw new HttpException(422, 'لا توجد فصول في هذه السنة'); }
        $sec = AcademicService::sectionRaw($sectionId);
        if ((int)$sec['academic_year_id'] !== $yearId) { throw new HttpException(422, 'الشعبة لا تتبع هذه السنة'); }
        $per = []; $subjects = []; $scheme = null; $students = [];   // $subjects: اتحاد مواد كل الفصول
        foreach ($terms as $t) {
            $r = self::compute((int)$t['id'], $sectionId, $onlyApproved); $scheme = $r['scheme']; foreach ($r['subjects'] as $x) { $subjects[$x['id']] = $x; }
            foreach ($r['students'] as $st) { $per[$st['enrollment_id']][] = $st; $students[$st['enrollment_id']] = $st; }
        }
        $sch = GradingService::schemeForSection($sectionId); $out = []; $avgs = [];
        foreach ($students as $eid => $base) {
            $subs = []; $pcts = []; $hrs = []; $failed = 0; $complete = true; $approved = true; $withData = 0;
            foreach ($subjects as $sub) {
                $tot = []; $pc = [];
                foreach ($per[$eid] as $st) { $x = $st['subjects'][$sub['id']] ?? null; if ($x && $x['has_assessments']) { $tot[] = $x['total']; $pc[] = $x['percent']; } }
                if (!$tot) { $subs[$sub['id']] = ['total' => 0, 'percent' => 0, 'letter' => null, 'passed' => null, 'has_assessments' => false]; continue; }
                $total = round(array_sum($tot) / count($tot), (int)$sch['rounding']); $percent = round(array_sum($pc) / count($pc), (int)$sch['rounding']); $pass = GradeCalculator::passed($sch, $total);
                $pcts[] = $percent; $hrs[] = (float)$sub['credit_hours']; if (!$pass) { $failed++; }
                $subs[$sub['id']] = ['total' => $total, 'percent' => $percent, 'letter' => GradeCalculator::letter($sch['scales'], $percent), 'passed' => $pass, 'has_assessments' => true];
            }
            foreach ($per[$eid] as $st) { if (!$st['complete']) { $complete = false; } if (!$st['all_approved']) { $approved = false; } if ($st['average'] !== null) { $withData++; } }
            $avg = self::mean($pcts, self::creditWeighted() ? $hrs : null, (int)$sch['rounding']); $avgs[$eid] = $avg ?? -1;
            $out[$eid] = ['enrollment_id' => $eid, 'student_id' => $base['student_id'], 'full_name' => $base['full_name'], 'student_no' => $base['student_no'], 'subjects' => $subs, 'average' => $avg,
                'letter' => $avg === null ? null : GradeCalculator::letter($sch['scales'], $avg), 'failed' => $failed, 'complete' => $complete && $withData === count($terms), 'all_approved' => $approved,
                'result' => $avg === null ? null : ($failed === 0 ? 'ناجح' : "راسب في $failed " . ($failed === 1 ? 'مادة' : 'مواد'))];
        }
        foreach (GradeCalculator::rank($avgs) as $eid => $rk) { $out[$eid]['rank'] = $avgs[$eid] < 0 ? null : $rk; }
        usort($out, fn($a, $b) => strcmp($a['full_name'], $b['full_name']));
        uasort($subjects, fn($a, $b) => strcmp($a['name'], $b['name']));
        return ['section' => $sec, 'year_id' => $yearId, 'terms' => $terms, 'scheme' => $scheme, 'subjects' => array_values($subjects), 'students' => $out];
    }

    /** هل يُحتسب المعدل موزونًا بالساعات المعتمدة؟ (إعداد مدرسة) */
    public static function creditWeighted(): bool { try { return SettingsService::get('grades.credit_weighted', '0') === '1'; } catch (\Throwable) { return false; } }
    /** متوسط حسابي أو موزون بالساعات (ساعات صفرية لكل المواد = متوسط حسابي). */
    public static function mean(array $values, ?array $weights, int $round): ?float
    {
        if (!$values) { return null; }
        if ($weights !== null && array_sum($weights) > 0) { $acc = 0.0; foreach ($values as $i => $v) { $acc += $v * ($weights[$i] ?? 0); } return round($acc / array_sum($weights), $round); }
        return round(array_sum($values) / count($values), $round);
    }
}
