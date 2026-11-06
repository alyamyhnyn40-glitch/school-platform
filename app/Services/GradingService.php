<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\{Auth, DB, HttpException, Tenant};
use App\Repositories\AssessmentRepository;

/** التقييمات والدرجات: تهيئة النظام، الإدخال، الاعتماد والإقفال. */
final class GradingService
{
    // ---------- تهيئة نظام الدرجات ----------
    public static function schemes(): array
    {
        Auth::authorize('grades.view');
        $sid = Tenant::require(); $out = [];
        foreach (DB::all('SELECT * FROM grading_schemes WHERE school_id = ? ORDER BY is_default DESC, id', [$sid]) as $s) {
            $s['types'] = DB::all('SELECT * FROM assessment_types WHERE scheme_id = ? AND school_id = ? ORDER BY sort_order, id', [$s['id'], $sid]);
            $s['scales'] = DB::all('SELECT * FROM grade_scales WHERE scheme_id = ? AND school_id = ? ORDER BY min_percent DESC', [$s['id'], $sid]);
            $out[] = $s;
        }
        return $out;
    }

    /** يحفظ نظامًا كاملًا (جديدًا أو موجودًا) في transaction. data: name,max_score,pass_score,average_method,rounding,stage_id,is_default,types[],scales[]. */
    public static function saveScheme(?int $id, array $d): int
    {
        Auth::authorize('grades.config');
        $sid = Tenant::require();
        $max = (float)($d['max_score'] ?? 0); $pass = (float)($d['pass_score'] ?? 0);
        if ($max <= 0) { throw new HttpException(422, 'الدرجة العظمى يجب أن تكون أكبر من صفر', ['max_score' => 'غير صالح']); }
        if ($pass < 0 || $pass > $max) { throw new HttpException(422, 'درجة النجاح خارج المدى', ['pass_score' => 'غير صالح']); }
        $method = in_array($d['average_method'] ?? 'weighted', ['weighted', 'arithmetic'], true) ? ($d['average_method'] ?? 'weighted') : 'weighted';
        $types = array_values(array_filter((array)($d['types'] ?? []), fn($t) => trim((string)($t['name'] ?? '')) !== ''));
        $scales = array_values(array_filter((array)($d['scales'] ?? []), fn($t) => trim((string)($t['label'] ?? '')) !== ''));
        if ($method === 'weighted') {
            if (!$types) { throw new HttpException(422, 'أضف نوع تقييم واحدًا على الأقل'); }
            $w = array_sum(array_map(fn($t) => (float)$t['weight'], $types));
            if (abs($w - 100) > 0.001) { throw new HttpException(422, "مجموع الأوزان يجب أن يساوي 100 (الحالي $w)", ['types' => 'مجموع الأوزان ≠ 100']); }
        }
        if (!$scales) { throw new HttpException(422, 'أضف سلّم تقديرات'); }
        if (!array_filter($scales, fn($s) => (float)$s['min_percent'] <= 0)) { throw new HttpException(422, 'يجب أن يغطي السلّم النسبة 0% (أضف تقديرًا حدّه الأدنى 0)'); }
        $stage = ($d['stage_id'] ?? '') === '' ? null : (int)$d['stage_id'];
        if ($stage) { DB::value('SELECT 1 FROM stages WHERE id = ? AND school_id = ?', [$stage, $sid]) ?? throw new HttpException(422, 'مرحلة غير صالحة'); }
        return DB::transaction(function () use ($id, $d, $sid, $max, $pass, $method, $types, $scales, $stage) {
            if (!empty($d['is_default'])) { DB::query('UPDATE grading_schemes SET is_default = 0 WHERE school_id = ?', [$sid]); }
            $rounding = min(4, max(0, (int)($d['rounding'] ?? 2)));
            if ($id) {
                DB::value('SELECT 1 FROM grading_schemes WHERE id = ? AND school_id = ?', [$id, $sid]) ?? throw new HttpException(404, 'النظام غير موجود');
                if (DB::value('SELECT 1 FROM assessments a JOIN assessment_types t ON t.id = a.type_id WHERE t.scheme_id = ? AND a.school_id = ? LIMIT 1', [$id, $sid])) {
                    // تغيير الأوزان بعد وجود تقييمات يغيّر النتائج: نسمح بتعديل الإعدادات العامة والسلّم فقط، لا أنواع التقييم
                    $types = null;
                }
                DB::query('UPDATE grading_schemes SET name=?, stage_id=?, max_score=?, pass_score=?, average_method=?, rounding=?, is_default=? WHERE id=? AND school_id=?', [trim((string)$d['name']), $stage, $max, $pass, $method, $rounding, (int)!empty($d['is_default']), $id, $sid]);
                DB::query('DELETE FROM grade_scales WHERE scheme_id = ? AND school_id = ?', [$id, $sid]);
                if ($types !== null) { DB::query('DELETE FROM assessment_types WHERE scheme_id = ? AND school_id = ?', [$id, $sid]); }
            } else {
                DB::query('INSERT INTO grading_schemes (school_id, stage_id, name, max_score, pass_score, average_method, rounding, is_default) VALUES (?,?,?,?,?,?,?,?)', [$sid, $stage, trim((string)$d['name']), $max, $pass, $method, $rounding, (int)!empty($d['is_default'])]);
                $id = DB::lastId();
            }
            if ($types !== null) { foreach ($types as $i => $t) { DB::query('INSERT INTO assessment_types (school_id, scheme_id, name, weight, max_score, sort_order) VALUES (?,?,?,?,?,?)', [$sid, $id, trim((string)$t['name']), (float)$t['weight'], (float)($t['max_score'] ?: $t['weight']), $i + 1]); } }
            foreach ($scales as $s) { DB::query('INSERT INTO grade_scales (school_id, scheme_id, label, min_percent) VALUES (?,?,?,?)', [$sid, $id, trim((string)$s['label']), (float)$s['min_percent']]); }
            AuditService::log('grading.scheme', 'grading_scheme', $id, null, ['name' => $d['name'], 'max' => $max, 'pass' => $pass, 'method' => $method]);
            return $id;
        });
    }

    /** النظام المطبّق على شعبة: نظام مرحلتها ثم الافتراضي. */
    public static function schemeForSection(int $sectionId): array
    {
        $sid = Tenant::require();
        $row = DB::one('SELECT gs.* FROM grading_schemes gs WHERE gs.school_id = ? AND (gs.stage_id = (SELECT g.stage_id FROM sections sc JOIN grade_levels g ON g.id = sc.grade_level_id WHERE sc.id = ? AND sc.school_id = ?) OR gs.is_default = 1)
                        ORDER BY (gs.stage_id IS NOT NULL) DESC, gs.is_default DESC LIMIT 1', [$sid, $sectionId, $sid]);
        if (!$row) { throw new HttpException(422, 'لم يُهيَّأ نظام درجات للمدرسة'); }
        $row['types'] = []; foreach (DB::all('SELECT * FROM assessment_types WHERE scheme_id = ? AND school_id = ? ORDER BY sort_order', [$row['id'], $sid]) as $t) { $row['types'][$t['id']] = $t; }
        $row['scales'] = DB::all('SELECT label, min_percent, gpa_points FROM grade_scales WHERE scheme_id = ? AND school_id = ?', [$row['id'], $sid]);
        return $row;
    }

    // ---------- التقييمات ----------
    /** هل يملك المستخدم صلاحية إدخال درجات هذه المادة/الشعبة؟ (إداري أو معلم مُسند). */
    private static function assertCanEnter(array $ss): void
    {
        if (Auth::can('grades.approve') || Auth::can('grades.config')) { return; }
        $staff = Scope::staffId();
        if (!$staff || !DB::value('SELECT 1 FROM teacher_assignments WHERE section_subject_id = ? AND staff_id = ? AND school_id = ?', [$ss['id'], $staff, Tenant::require()])) {
            throw new HttpException(403, 'لست معلّم هذه المادة في هذه الشعبة');
        }
    }
    private static function sectionSubject(int $id): array
    {
        return DB::one('SELECT ss.*, sub.name AS subject_name, sc.name AS section_name, sc.academic_year_id, sc.grade_level_id FROM section_subjects ss JOIN subjects sub ON sub.id = ss.subject_id JOIN sections sc ON sc.id = ss.section_id
            WHERE ss.id = ? AND ss.school_id = ?', [$id, Tenant::require()]) ?? throw new HttpException(404, 'المادة غير موجودة');
    }

    public static function listAssessments(int $sectionSubjectId, int $termId): array
    {
        Auth::authorize('grades.view'); $ss = self::sectionSubject($sectionSubjectId);
        if (!Scope::canSeeSection((int)$ss['section_id'])) { throw new HttpException(403, 'خارج نطاقك'); }
        return DB::all('SELECT a.*, t.name AS type_name, (SELECT COUNT(*) FROM grades g WHERE g.assessment_id = a.id AND (g.score IS NOT NULL OR g.is_absent = 1)) AS entered
            FROM assessments a JOIN assessment_types t ON t.id = a.type_id WHERE a.school_id = ? AND a.section_subject_id = ? AND a.term_id = ? ORDER BY a.id', [Tenant::require(), $sectionSubjectId, $termId]);
    }

    public static function createAssessment(int $termId, int $sectionSubjectId, int $typeId, string $title, float $max, ?string $date): int
    {
        Auth::authorize('grades.edit');
        $ss = self::sectionSubject($sectionSubjectId); self::assertCanEnter($ss);
        $sid = Tenant::require();
        $term = DB::one('SELECT * FROM terms WHERE id = ? AND school_id = ?', [$termId, $sid]) ?? throw new HttpException(404, 'الفصل غير موجود');
        if ((int)$term['academic_year_id'] !== (int)$ss['academic_year_id']) { throw new HttpException(422, 'الفصل لا يتبع سنة هذه الشعبة'); }
        if (!empty($ss['term_id']) && (int)$ss['term_id'] !== $termId) { throw new HttpException(422, 'هذه المادة مقررة في فصل آخر حسب الخطة الدراسية، فلا تُضاف لها تقييمات في هذا الفصل'); }
        if ($term['grades_locked']) { throw new HttpException(422, 'درجات هذا الفصل مقفلة'); }
        AcademicService::assertYearOpen((int)$ss['academic_year_id']);
        $scheme = self::schemeForSection((int)$ss['section_id']);
        if (!isset($scheme['types'][$typeId])) { throw new HttpException(422, 'نوع التقييم غير تابع لنظام هذه الشعبة', ['type_id' => 'غير صالح']); }
        if ($max <= 0 || trim($title) === '') { throw new HttpException(422, 'عنوان التقييم ودرجته العظمى مطلوبان'); }
        $id = (new AssessmentRepository())->insert(['term_id' => $termId, 'section_subject_id' => $sectionSubjectId, 'type_id' => $typeId, 'title' => trim($title), 'max_score' => $max, 'held_on' => $date ?: null, 'status' => 'open', 'created_by' => Auth::id()]);
        AuditService::log('assessment.create', 'assessment', $id, null, ['title' => $title, 'max' => $max]);
        return $id;
    }

    public static function deleteAssessment(int $id): void
    {
        Auth::authorize('grades.edit');
        $a = (new AssessmentRepository())->findOrFail($id); self::assertCanEnter(self::sectionSubject((int)$a['section_subject_id']));
        if ($a['status'] !== 'open') { throw new HttpException(422, 'لا يمكن حذف تقييم معتمد'); }
        if (DB::value('SELECT 1 FROM grades WHERE assessment_id = ? AND (score IS NOT NULL OR is_absent = 1) LIMIT 1', [$id])) { throw new HttpException(409, 'توجد درجات مُدخلة لهذا التقييم'); }
        DB::query('DELETE FROM grades WHERE assessment_id = ? AND school_id = ?', [$id, Tenant::require()]);
        (new AssessmentRepository())->delete($id);
        AuditService::log('assessment.delete', 'assessment', $id, ['title' => $a['title']]);
    }

    /** شبكة الإدخال: التقييم + طلاب الشعبة + درجاتهم. */
    public static function grid(int $assessmentId): array
    {
        Auth::authorize('grades.view');
        $a = (new AssessmentRepository())->findOrFail($assessmentId); $ss = self::sectionSubject((int)$a['section_subject_id']);
        if (!Scope::canSeeSection((int)$ss['section_id'])) { throw new HttpException(403, 'خارج نطاقك'); }
        $rows = DB::all("SELECT e.id AS enrollment_id, s.id AS student_id, s.full_name, s.student_no, g.score, g.is_absent, g.note
            FROM student_enrollments e JOIN students s ON s.id = e.student_id LEFT JOIN grades g ON g.enrollment_id = e.id AND g.assessment_id = ?
            WHERE e.school_id = ? AND e.section_id = ? AND e.status = 'active' AND s.deleted_at IS NULL ORDER BY s.full_name", [$assessmentId, Tenant::require(), $ss['section_id']]);
        if (!empty($ss['is_elective'])) { $chosen = StudyPlanService::electiveMap((int)$ss['section_id'])[(int)$ss['id']] ?? []; $rows = array_values(array_filter($rows, fn($r) => isset($chosen[(int)$r['enrollment_id']]))); }   // الاختيارية: من اختارها فقط
        $term = DB::one('SELECT grades_locked FROM terms WHERE id = ?', [$a['term_id']]);
        return ['assessment' => $a + ['subject_name' => $ss['subject_name'], 'section_name' => $ss['section_name']], 'rows' => $rows, 'editable' => $a['status'] === 'open' && !$term['grades_locked']];
    }

    /** يحفظ درجات. scores: [{enrollment_id, score|null, is_absent?, note?, client_uuid?}]. يعيد عدادات. */
    public static function saveScores(int $assessmentId, array $scores): array
    {
        Auth::authorize('grades.edit');
        $repo = new AssessmentRepository(); $a = $repo->findOrFail($assessmentId); $ss = self::sectionSubject((int)$a['section_subject_id']);
        self::assertCanEnter($ss); AcademicService::assertYearOpen((int)$ss['academic_year_id']);
        $term = DB::one('SELECT grades_locked FROM terms WHERE id = ? AND school_id = ?', [$a['term_id'], Tenant::require()]);
        if ($a['status'] !== 'open' || $term['grades_locked']) { throw new HttpException(422, 'الدرجات معتمدة/مقفلة. يلزم إعادة فتح التقييم أولًا.'); }
        $sid = Tenant::require(); $c = ['created' => 0, 'updated' => 0, 'skipped' => 0];
        DB::transaction(function () use ($assessmentId, $a, $ss, $scores, $sid, &$c) {
            foreach ($scores as $r) {
                $eid = (int)($r['enrollment_id'] ?? 0);
                if (!DB::value("SELECT 1 FROM student_enrollments WHERE id = ? AND school_id = ? AND section_id = ? AND status = 'active'", [$eid, $sid, $ss['section_id']])) { throw new HttpException(422, 'طالب لا ينتمي لهذه الشعبة'); }
                $absent = !empty($r['is_absent']) && $r['is_absent'] !== '0';
                $raw = $r['score'] ?? null; $score = ($raw === null || $raw === '') ? null : $raw;
                if ($score !== null) {
                    if (!is_numeric($score)) { throw new HttpException(422, 'الدرجة يجب أن تكون رقمًا'); }
                    $score = round((float)$score, 2);
                    if ($score < 0 || $score > (float)$a['max_score']) { throw new HttpException(422, 'الدرجة خارج المدى (0 – ' . (float)$a['max_score'] . ')', ['score' => 'خارج المدى']); }
                }
                if ($absent) { $score = null; }
                $uuid = !empty($r['client_uuid']) ? (string)$r['client_uuid'] : null;
                if ($uuid && DB::value('SELECT 1 FROM grades WHERE client_uuid = ? AND school_id = ?', [$uuid, $sid])) { $c['skipped']++; continue; }
                $note = isset($r['note']) && $r['note'] !== '' ? mb_substr((string)$r['note'], 0, 255) : null;
                $old = DB::one('SELECT id, score, is_absent, note FROM grades WHERE assessment_id = ? AND enrollment_id = ?', [$assessmentId, $eid]);
                if ($old) {
                    $same = ($old['score'] === null ? null : (float)$old['score']) === $score && (int)$old['is_absent'] === (int)$absent && ($old['note'] ?? null) === $note;
                    if ($same) { $c['skipped']++; continue; }
                    DB::query('UPDATE grades SET score = ?, is_absent = ?, note = ?, client_uuid = ?, updated_by = ? WHERE id = ?', [$score, (int)$absent, $note, $uuid, Auth::id(), $old['id']]);
                    AuditService::log('grade.change', 'grade', (int)$old['id'], ['score' => $old['score'], 'is_absent' => (int)$old['is_absent']], ['score' => $score, 'is_absent' => (int)$absent, 'assessment_id' => $assessmentId, 'enrollment_id' => $eid]);
                    $c['updated']++;
                } else {
                    if ($score === null && !$absent && $note === null) { $c['skipped']++; continue; }
                    DB::query('INSERT INTO grades (school_id, assessment_id, enrollment_id, score, is_absent, note, client_uuid, updated_by) VALUES (?,?,?,?,?,?,?,?)', [$sid, $assessmentId, $eid, $score, (int)$absent, $note, $uuid, Auth::id()]);
                    AuditService::log('grade.record', 'grade', DB::lastId(), null, ['score' => $score, 'is_absent' => (int)$absent, 'assessment_id' => $assessmentId, 'enrollment_id' => $eid]);
                    $c['created']++;
                }
            }
        });
        return $c;
    }

    /** المواد التي يمكن للمستخدم الحالي إدخال درجاتها: الكل للإدارة، ومسنَدة للمعلم. (السنة الفعّالة افتراضيًا) */
    public static function mySubjects(?int $yearId = null): array
    {
        Auth::authorize('grades.view');
        $sid = Tenant::require(); $yearId ??= (int)(AcademicService::activeYear()['id'] ?? 0);
        $sql = "SELECT ss.id, sub.name AS subject, sc.name AS section, g.name AS grade, sc.id AS section_id FROM section_subjects ss JOIN subjects sub ON sub.id = ss.subject_id JOIN sections sc ON sc.id = ss.section_id JOIN grade_levels g ON g.id = sc.grade_level_id
                WHERE ss.school_id = ? AND sc.academic_year_id = ?";
        $p = [$sid, $yearId];
        if (!Auth::can('grades.approve') && !Auth::can('grades.config')) {
            $staff = Scope::staffId(); if (!$staff) { return []; }
            $sql .= ' AND ss.id IN (SELECT section_subject_id FROM teacher_assignments WHERE staff_id = ? AND school_id = ?)'; array_push($p, $staff, $sid);
        }
        return DB::all($sql . ' ORDER BY g.sort_order, sc.name, sub.name', $p);
    }

    /** دفعة عمليات إدخال درجات مخزنة دون اتصال. */
    public static function sync(array $ops): array
    {
        $out = [];
        foreach ($ops as $i => $op) {
            try { $out[] = ['i' => $i, 'ok' => true] + self::saveScores((int)($op['assessment_id'] ?? 0), (array)($op['scores'] ?? [])); }
            catch (HttpException $e) { $out[] = ['i' => $i, 'ok' => false, 'status' => $e->status, 'message' => $e->getMessage()]; }
        }
        return $out;
    }

    // ---------- الاعتماد والإقفال ----------
    public static function approve(int $assessmentId): void
    {
        Auth::authorize('grades.approve');
        $repo = new AssessmentRepository(); $a = $repo->findOrFail($assessmentId);
        if ($a['status'] !== 'open') { return; }
        $missing = (int)DB::value("SELECT COUNT(*) FROM student_enrollments e JOIN section_subjects ss ON ss.section_id = e.section_id LEFT JOIN grades g ON g.enrollment_id = e.id AND g.assessment_id = ?
            WHERE ss.id = ? AND e.school_id = ? AND e.status = 'active' AND (g.id IS NULL OR (g.score IS NULL AND g.is_absent = 0))", [$assessmentId, $a['section_subject_id'], Tenant::require()]);
        if ($missing > 0) { throw new HttpException(422, "لا يمكن الاعتماد: $missing طالب بلا درجة (سجّل الغياب إن وُجد)"); }
        $repo->update($assessmentId, ['status' => 'approved', 'approved_by' => Auth::id()]);
        AuditService::log('assessment.approve', 'assessment', $assessmentId, ['status' => 'open'], ['status' => 'approved']);
    }
    public static function reopen(int $assessmentId, string $reason): void
    {
        Auth::authorize('grades.approve');
        if (mb_strlen(trim($reason)) < 5) { throw new HttpException(422, 'اذكر سبب إعادة الفتح (5 أحرف على الأقل)', ['reason' => 'مطلوب']); }
        $repo = new AssessmentRepository(); $a = $repo->findOrFail($assessmentId);
        $term = DB::one('SELECT grades_locked FROM terms WHERE id = ?', [$a['term_id']]);
        if ($term['grades_locked']) { throw new HttpException(422, 'الفصل مقفل. افتح قفل الفصل أولًا.'); }
        $repo->update($assessmentId, ['status' => 'open', 'approved_by' => null, 'locked_at' => null]);
        AuditService::log('assessment.reopen', 'assessment', $assessmentId, ['status' => $a['status']], ['status' => 'open', 'reason' => $reason]);
    }
    /** يقفل درجات فصل: يشترط اعتماد كل تقييماته. */
    public static function lockTerm(int $termId): void
    {
        Auth::authorize('grades.approve');
        $sid = Tenant::require();
        $t = DB::one('SELECT * FROM terms WHERE id = ? AND school_id = ?', [$termId, $sid]) ?? throw new HttpException(404, 'الفصل غير موجود');
        $open = (int)DB::value("SELECT COUNT(*) FROM assessments WHERE term_id = ? AND school_id = ? AND status = 'open'", [$termId, $sid]);
        if ($open > 0) { throw new HttpException(422, "يوجد $open تقييم غير معتمد"); }
        DB::transaction(function () use ($termId, $sid) {
            DB::query("UPDATE assessments SET status = 'locked', locked_at = NOW() WHERE term_id = ? AND school_id = ?", [$termId, $sid]);
            DB::query('UPDATE terms SET grades_locked = 1 WHERE id = ? AND school_id = ?', [$termId, $sid]);
        });
        AuditService::log('term.lock', 'term', $termId, ['grades_locked' => 0], ['grades_locked' => 1]);
    }
    public static function unlockTerm(int $termId, string $reason): void
    {
        Auth::authorize('grades.approve');
        if (mb_strlen(trim($reason)) < 5) { throw new HttpException(422, 'اذكر السبب (5 أحرف على الأقل)', ['reason' => 'مطلوب']); }
        $sid = Tenant::require();
        DB::value('SELECT 1 FROM terms WHERE id = ? AND school_id = ?', [$termId, $sid]) ?? throw new HttpException(404, 'الفصل غير موجود');
        DB::transaction(function () use ($termId, $sid) {
            DB::query("UPDATE assessments SET status = 'approved', locked_at = NULL WHERE term_id = ? AND school_id = ? AND status = 'locked'", [$termId, $sid]);
            DB::query('UPDATE terms SET grades_locked = 0 WHERE id = ? AND school_id = ?', [$termId, $sid]);
        });
        AuditService::log('term.unlock', 'term', $termId, ['grades_locked' => 1], ['grades_locked' => 0, 'reason' => $reason]);
    }
}
