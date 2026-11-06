<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\{Auth, DB, HttpException, Tenant};

/**
 * الخطة الدراسية: لكل (سنة، صف) قائمة مواد، ولكل مادة: الفصل (أو كل الفصول)، الحصص، الدرجة العظمى، الساعات المعتمدة، واختيارية أم إجبارية.
 * «تطبيق الخطة» يُنشئ/يحدّث مواد الشعب من الخطة، ولا يحذف مادة لها تقييمات (حفاظًا على الدرجات).
 */
final class StudyPlanService
{
    public static function plan(int $yearId, int $gradeId): array
    {
        Auth::authorizeLookup();
        return DB::all('SELECT p.*, sub.name AS subject_name, sub.code, t.name AS term_name FROM study_plans p JOIN subjects sub ON sub.id = p.subject_id LEFT JOIN terms t ON t.id = p.term_id
            WHERE p.school_id = ? AND p.academic_year_id = ? AND p.grade_level_id = ? ORDER BY p.sort_order, sub.name', [Tenant::require(), $yearId, $gradeId]);
    }
    /** ملخص الخطة لكل صف في السنة: عدد المواد والاختيارية والساعات والشعب. */
    public static function summary(int $yearId): array
    {
        Auth::authorizeLookup(); $sid = Tenant::require();
        return DB::all('SELECT g.id, g.name, st.name AS stage_name,
              (SELECT COUNT(*) FROM study_plans p WHERE p.grade_level_id = g.id AND p.academic_year_id = ?) AS subjects,
              (SELECT COUNT(*) FROM study_plans p WHERE p.grade_level_id = g.id AND p.academic_year_id = ? AND p.is_elective = 1) AS electives,
              (SELECT COALESCE(SUM(p.credit_hours),0) FROM study_plans p WHERE p.grade_level_id = g.id AND p.academic_year_id = ?) AS hours,
              (SELECT COUNT(*) FROM sections sc WHERE sc.grade_level_id = g.id AND sc.academic_year_id = ?) AS sections
            FROM grade_levels g LEFT JOIN stages st ON st.id = g.stage_id WHERE g.school_id = ? ORDER BY st.sort_order, g.sort_order, g.id', [$yearId, $yearId, $yearId, $yearId, $sid]);
    }

    /** يستبدل خطة (سنة، صف) بالعناصر المرسلة. items: [{subject_id, term_id?, weekly_periods, max_score?, credit_hours, is_elective}] */
    public static function save(int $yearId, int $gradeId, array $items): int
    {
        Auth::authorize('academic.manage'); $sid = Tenant::require();
        AcademicService::assertYearOpen($yearId);
        if (!DB::value('SELECT 1 FROM grade_levels WHERE id = ? AND school_id = ?', [$gradeId, $sid])) { throw new HttpException(404, 'الصف غير موجود'); }
        $terms = array_map('intval', array_column(DB::all('SELECT id FROM terms WHERE academic_year_id = ? AND school_id = ?', [$yearId, $sid]), 'id'));
        $rows = []; $seen = [];
        foreach (array_values($items) as $i => $it) {
            $n = $i + 1; $subId = (int)($it['subject_id'] ?? 0); if (!$subId) { continue; }
            $name = DB::value('SELECT name FROM subjects WHERE id = ? AND school_id = ?', [$subId, $sid]); if (!$name) { throw new HttpException(422, "السطر $n: المادة غير موجودة"); }
            if (isset($seen[$subId])) { throw new HttpException(422, "المادة «{$name}» مكررة في الخطة (السطر $n). كل مادة مرة واحدة: اختر «كل الفصول» أو فصلًا محددًا."); }
            $seen[$subId] = true;
            $term = ($it['term_id'] ?? '') === '' || (int)$it['term_id'] === 0 ? null : (int)$it['term_id'];
            if ($term !== null && !in_array($term, $terms, true)) { throw new HttpException(422, "السطر $n («{$name}»): الفصل المختار لا يتبع هذه السنة"); }
            $periods = (int)($it['weekly_periods'] ?? 1); if ($periods < 1 || $periods > 40) { throw new HttpException(422, "السطر $n («{$name}»): عدد الحصص الأسبوعية بين 1 و40"); }
            $max = ($it['max_score'] ?? '') === '' ? null : (float)$it['max_score']; if ($max !== null && ($max <= 0 || $max > 1000)) { throw new HttpException(422, "السطر $n («{$name}»): الدرجة العظمى بين 1 و1000"); }
            $hours = ($it['credit_hours'] ?? '') === '' ? 1.0 : (float)$it['credit_hours']; if ($hours < 0 || $hours > 20) { throw new HttpException(422, "السطر $n («{$name}»): الساعات المعتمدة بين 0 و20"); }
            $rows[] = [$subId, $term, $periods, $max, $hours, !empty($it['is_elective']) && $it['is_elective'] !== '0' ? 1 : 0, $n];
        }
        DB::transaction(function () use ($rows, $yearId, $gradeId, $sid) {
            DB::query('DELETE FROM study_plans WHERE school_id = ? AND academic_year_id = ? AND grade_level_id = ?', [$sid, $yearId, $gradeId]);
            foreach ($rows as [$sub, $term, $p, $max, $h, $el, $ord]) {
                DB::query('INSERT INTO study_plans (school_id, academic_year_id, grade_level_id, subject_id, term_id, weekly_periods, max_score, credit_hours, is_elective, sort_order) VALUES (?,?,?,?,?,?,?,?,?,?)', [$sid, $yearId, $gradeId, $sub, $term, $p, $max, $h, $el, $ord]);
                DB::query('INSERT IGNORE INTO subject_grade_levels (subject_id, grade_level_id, school_id) VALUES (?,?,?)', [$sub, $gradeId, $sid]);   // المادة تُربط بالصف تلقائيًا
            }
        });
        AuditService::log('study_plan.save', 'grade_level', $gradeId, null, ['year' => $yearId, 'subjects' => count($rows)]);
        return count($rows);
    }

    /** يطبّق الخطة على شعب الصف (أو كل الصفوف). @return array{added:int, updated:int, kept:list<string>, sections:int} */
    public static function apply(int $yearId, ?int $gradeId = null): array
    {
        Auth::authorize('academic.manage'); $sid = Tenant::require(); AcademicService::assertYearOpen($yearId);
        $out = ['added' => 0, 'updated' => 0, 'removed' => 0, 'kept' => [], 'sections' => 0];
        $sections = DB::all('SELECT sc.id, sc.name, sc.grade_level_id, g.name AS grade FROM sections sc JOIN grade_levels g ON g.id = sc.grade_level_id WHERE sc.school_id = ? AND sc.academic_year_id = ?' . ($gradeId ? ' AND sc.grade_level_id = ?' : ''), $gradeId ? [$sid, $yearId, $gradeId] : [$sid, $yearId]);
        DB::transaction(function () use ($sections, $yearId, $sid, &$out) {
            foreach ($sections as $sc) {
                $plan = DB::all('SELECT * FROM study_plans WHERE school_id = ? AND academic_year_id = ? AND grade_level_id = ?', [$sid, $yearId, $sc['grade_level_id']]);
                if (!$plan) { continue; } $out['sections']++; $keep = [];
                foreach ($plan as $p) {
                    $keep[] = (int)$p['subject_id'];
                    $ex = DB::one('SELECT id FROM section_subjects WHERE section_id = ? AND subject_id = ?', [$sc['id'], $p['subject_id']]);
                    if ($ex) { DB::query('UPDATE section_subjects SET term_id = ?, weekly_periods = ?, max_score = ?, credit_hours = ?, is_elective = ? WHERE id = ?', [$p['term_id'], $p['weekly_periods'], $p['max_score'], $p['credit_hours'], $p['is_elective'], $ex['id']]); $out['updated']++; }
                    else { DB::query('INSERT INTO section_subjects (school_id, section_id, subject_id, term_id, weekly_periods, max_score, credit_hours, is_elective) VALUES (?,?,?,?,?,?,?,?)', [$sid, $sc['id'], $p['subject_id'], $p['term_id'], $p['weekly_periods'], $p['max_score'], $p['credit_hours'], $p['is_elective']]); $out['added']++; }
                }
                // مواد الشعبة غير الموجودة في الخطة: تُحذف إن لم يكن لها أي تقييم/حصص/محتوى، وإلا تُترك ويُبلَّغ عنها
                foreach (DB::all('SELECT ss.id, sub.name FROM section_subjects ss JOIN subjects sub ON sub.id = ss.subject_id WHERE ss.section_id = ? AND ss.subject_id NOT IN (' . implode(',', array_map('intval', $keep)) . ')', [$sc['id']]) as $extra) {
                    if (DB::value('SELECT 1 FROM assessments WHERE section_subject_id = ? LIMIT 1', [$extra['id']])) { $out['kept'][] = "{$sc['grade']} / {$sc['name']}: {$extra['name']} (لها تقييمات)"; continue; }
                    try { DB::query('DELETE FROM teacher_assignments WHERE section_subject_id = ?', [$extra['id']]); DB::query('DELETE FROM section_subjects WHERE id = ?', [$extra['id']]); $out['removed']++; }
                    catch (\PDOException) { $out['kept'][] = "{$sc['grade']} / {$sc['name']}: {$extra['name']} (مرتبطة بحصص أو محتوى)"; }
                }
            }
        });
        AuditService::log('study_plan.apply', 'academic_year', $yearId, null, ['grade' => $gradeId] + array_diff_key($out, ['kept' => 1]));
        return $out;
    }

    /** ينسخ الخطة من سنة لأخرى (مع مطابقة الفصول برقمها). @return int عدد المواد المنسوخة */
    public static function copy(int $fromYear, int $toYear): int
    {
        Auth::authorize('academic.manage'); $sid = Tenant::require(); AcademicService::assertYearOpen($toYear);
        if ($fromYear === $toYear) { throw new HttpException(422, 'اختر سنة مختلفة للنسخ منها'); }
        $termMap = []; foreach (DB::all('SELECT a.id AS old_id, b.id AS new_id FROM terms a JOIN terms b ON b.term_no = a.term_no AND b.academic_year_id = ? WHERE a.academic_year_id = ? AND a.school_id = ?', [$toYear, $fromYear, $sid]) as $m) { $termMap[(int)$m['old_id']] = (int)$m['new_id']; }
        $n = 0;
        DB::transaction(function () use ($fromYear, $toYear, $sid, $termMap, &$n) {
            foreach (DB::all('SELECT * FROM study_plans WHERE school_id = ? AND academic_year_id = ?', [$sid, $fromYear]) as $p) {
                $term = $p['term_id'] === null ? null : ($termMap[(int)$p['term_id']] ?? null);
                $n += DB::query('INSERT IGNORE INTO study_plans (school_id, academic_year_id, grade_level_id, subject_id, term_id, weekly_periods, max_score, credit_hours, is_elective, sort_order) VALUES (?,?,?,?,?,?,?,?,?,?)',
                    [$sid, $toYear, $p['grade_level_id'], $p['subject_id'], $term, $p['weekly_periods'], $p['max_score'], $p['credit_hours'], $p['is_elective'], $p['sort_order']])->rowCount();
            }
        });
        AuditService::log('study_plan.copy', 'academic_year', $toYear, null, ['from' => $fromYear, 'subjects' => $n]);
        return $n;
    }

    // ---------- المواد الاختيارية ----------
    public static function electiveStudents(int $sectionSubjectId): array
    {
        Auth::authorize('academic.view'); $sid = Tenant::require();
        $ss = DB::one('SELECT ss.*, sub.name AS subject_name FROM section_subjects ss JOIN subjects sub ON sub.id = ss.subject_id WHERE ss.id = ? AND ss.school_id = ?', [$sectionSubjectId, $sid]) ?? throw new HttpException(404, 'المادة غير موجودة');
        $rows = DB::all("SELECT e.id AS enrollment_id, s.full_name, s.student_no, (SELECT 1 FROM student_electives x WHERE x.enrollment_id = e.id AND x.section_subject_id = ?) AS chosen
            FROM student_enrollments e JOIN students s ON s.id = e.student_id WHERE e.section_id = ? AND e.status = 'active' AND s.deleted_at IS NULL ORDER BY s.full_name", [$sectionSubjectId, $ss['section_id']]);
        return ['subject' => $ss, 'students' => $rows];
    }
    public static function setElectiveStudents(int $sectionSubjectId, array $enrollmentIds): int
    {
        Auth::authorize('academic.manage'); $sid = Tenant::require();
        $ss = DB::one('SELECT ss.*, sc.academic_year_id FROM section_subjects ss JOIN sections sc ON sc.id = ss.section_id WHERE ss.id = ? AND ss.school_id = ?', [$sectionSubjectId, $sid]) ?? throw new HttpException(404, 'المادة غير موجودة');
        if (!(int)$ss['is_elective']) { throw new HttpException(422, 'المادة إجبارية لكل طلاب الشعبة؛ اجعلها «اختيارية» في الخطة الدراسية أولًا'); }
        AcademicService::assertYearOpen((int)$ss['academic_year_id']);
        $valid = array_map('intval', array_column(DB::all("SELECT id FROM student_enrollments WHERE section_id = ? AND status = 'active'", [$ss['section_id']]), 'id'));
        $ids = array_values(array_intersect(array_map('intval', $enrollmentIds), $valid));
        DB::transaction(function () use ($sectionSubjectId, $ids, $sid) {
            DB::query('DELETE FROM student_electives WHERE section_subject_id = ? AND school_id = ?', [$sectionSubjectId, $sid]);
            foreach ($ids as $e) { DB::query('INSERT INTO student_electives (school_id, enrollment_id, section_subject_id) VALUES (?,?,?)', [$sid, $e, $sectionSubjectId]); }
        });
        AuditService::log('elective.set', 'section_subject', $sectionSubjectId, null, ['students' => count($ids)]);
        return count($ids);
    }
    /** خريطة الاختياري: [section_subject_id => [enrollment_id => true]] لمواد اختيارية في شعبة. */
    public static function electiveMap(int $sectionId): array
    {
        $m = [];
        foreach (DB::all('SELECT x.section_subject_id, x.enrollment_id FROM student_electives x JOIN section_subjects ss ON ss.id = x.section_subject_id WHERE ss.section_id = ? AND ss.is_elective = 1', [$sectionId]) as $r) { $m[(int)$r['section_subject_id']][(int)$r['enrollment_id']] = true; }
        return $m;
    }
}
