<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\{Auth, DB, HttpException, Tenant};
use App\Repositories\{AcademicYearRepository, GradeLevelRepository, SectionRepository, SectionSubjectRepository, StageRepository, StaffRepository, SubjectRepository, TeacherAssignmentRepository, TermRepository};

/** السنوات والفصول والمراحل والصفوف والشعب والمواد والإسناد. */
final class AcademicService
{
    // ---------- قراءة ----------
    public static function years(): array
    {
        Auth::authorizeLookup();
        return (new AcademicYearRepository())->where([], 'starts_on DESC');
    }
    public static function activeYear(): ?array { return (new AcademicYearRepository())->active(); }
    public static function terms(int $yearId): array { Auth::authorizeLookup(); return (new TermRepository())->where(['academic_year_id' => $yearId], 'term_no'); }
    public static function stages(): array { Auth::authorizeLookup(); return (new StageRepository())->where([], 'sort_order, id'); }
    public static function grades(): array
    {
        Auth::authorizeLookup();
        return DB::all('SELECT g.*, s.name AS stage_name, n.name AS next_name FROM grade_levels g JOIN stages s ON s.id = g.stage_id LEFT JOIN grade_levels n ON n.id = g.next_grade_level_id
                        WHERE g.school_id = ? ORDER BY s.sort_order, g.sort_order, g.id', [Tenant::require()]);
    }
    /** المواد مع الصفوف المرتبطة بها. $gradeId: تقتصر على مواد هذا الصف. */
    public static function subjects(?int $gradeId = null): array
    {
        Auth::authorizeLookup(); $sid = Tenant::require(); $w = 's.school_id = ?'; $p = [$sid];
        if ($gradeId) { $w .= ' AND EXISTS (SELECT 1 FROM subject_grade_levels x WHERE x.subject_id = s.id AND x.grade_level_id = ?)'; $p[] = $gradeId; }
        $rows = DB::all("SELECT s.* FROM subjects s WHERE $w ORDER BY s.name", $p);
        $links = []; foreach (DB::all('SELECT x.subject_id, x.grade_level_id, g.name FROM subject_grade_levels x JOIN grade_levels g ON g.id = x.grade_level_id WHERE x.school_id = ? ORDER BY g.sort_order, g.id', [$sid]) as $l) { $links[$l['subject_id']][] = ['id' => (int)$l['grade_level_id'], 'name' => $l['name']]; }
        foreach ($rows as &$r) { $r['grades'] = $links[$r['id']] ?? []; $r['grade_ids'] = array_column($r['grades'], 'id'); } return $rows;
    }
    /** المواد التي يمكن إسنادها لشعبة (المرتبطة بصفها). */
    public static function subjectsForSection(int $sectionId): array { $sec = self::section($sectionId); return self::subjects((int)$sec['grade_level_id']); }
    public static function sections(?int $yearId = null): array
    {
        Auth::authorizeLookup();
        $p = [Tenant::require()]; $w = '';
        if ($yearId) { $w = ' AND sc.academic_year_id = ?'; $p[] = $yearId; }
        return DB::all("SELECT sc.*, g.name AS grade_name, y.name AS year_name, y.status AS year_status,
                (SELECT COUNT(*) FROM student_enrollments e WHERE e.section_id = sc.id AND e.status = 'active') AS students
            FROM sections sc JOIN grade_levels g ON g.id = sc.grade_level_id JOIN academic_years y ON y.id = sc.academic_year_id
            WHERE sc.school_id = ? $w ORDER BY y.starts_on DESC, g.sort_order, sc.name", $p);
    }
    public static function section(int $id): array { Auth::authorizeLookup(); return self::sectionRaw($id); }
    /** بلا فحص صلاحية: للخدمات التي فحصت صلاحيتها (النتائج/البوابة). */
    public static function sectionRaw(int $id): array
    {
        $s = DB::one('SELECT sc.*, g.name AS grade_name, y.name AS year_name, y.status AS year_status FROM sections sc JOIN grade_levels g ON g.id = sc.grade_level_id
                      JOIN academic_years y ON y.id = sc.academic_year_id WHERE sc.id = ? AND sc.school_id = ?', [$id, Tenant::require()]);
        return $s ?? throw new HttpException(404, 'الشعبة غير موجودة');
    }
    public static function sectionSubjects(int $sectionId): array
    {
        Auth::authorizeLookup();
        return DB::all('SELECT ss.*, sub.name AS subject_name, t.name AS term_name,
                (SELECT GROUP_CONCAT(st.full_name SEPARATOR "، ") FROM teacher_assignments ta JOIN staff st ON st.id = ta.staff_id WHERE ta.section_subject_id = ss.id) AS teachers,
                (SELECT COUNT(*) FROM student_electives x WHERE x.section_subject_id = ss.id) AS elective_count
            FROM section_subjects ss JOIN subjects sub ON sub.id = ss.subject_id LEFT JOIN terms t ON t.id = ss.term_id WHERE ss.school_id = ? AND ss.section_id = ? ORDER BY sub.name', [Tenant::require(), $sectionId]);
    }

    // ---------- السنوات والفصول ----------
    public static function createYear(string $name, string $starts, string $ends): int
    {
        Auth::authorize('academic.manage');
        if ($ends <= $starts) { throw new HttpException(422, 'تاريخ نهاية السنة يجب أن يلي بدايتها', ['ends_on' => 'غير صالح']); }
        $r = new AcademicYearRepository();
        if ($r->where(['name' => $name])) { throw new HttpException(422, 'اسم السنة مستخدم', ['name' => 'مستخدم']); }
        $id = $r->insert(['name' => $name, 'starts_on' => $starts, 'ends_on' => $ends, 'status' => 'draft']);
        AuditService::log('year.create', 'academic_year', $id, null, ['name' => $name]);
        return $id;
    }
    public static function activateYear(int $id): void
    {
        Auth::authorize('academic.manage');
        $r = new AcademicYearRepository(); $y = $r->findOrFail($id);
        if ($y['status'] !== 'draft') { throw new HttpException(422, 'يمكن تفعيل السنوات المسودة فقط'); }
        if ($r->active()) { throw new HttpException(422, 'توجد سنة فعّالة. أغلقها أو نفّذ الترفيع إلى هذه السنة.'); }
        $r->update($id, ['status' => 'active']);
        AuditService::log('year.activate', 'academic_year', $id, ['status' => 'draft'], ['status' => 'active']);
    }
    public static function closeYear(int $id): void
    {
        Auth::authorize('academic.promote');
        $r = new AcademicYearRepository(); $y = $r->findOrFail($id);
        if ($y['status'] === 'closed') { return; }
        TranscriptService::freezeYear($id);   // السجل الأكاديمي يُجمَّد قبل الإغلاق
        $r->update($id, ['status' => 'closed', 'closed_at' => now()]);
        AuditService::log('year.close', 'academic_year', $id, ['status' => $y['status']], ['status' => 'closed']);
    }
    public static function assertYearOpen(int $yearId): array
    {
        $y = (new AcademicYearRepository())->findOrFail($yearId);
        if ($y['status'] === 'closed') { throw new HttpException(422, 'السنة الدراسية مغلقة (للقراءة فقط)'); }
        return $y;
    }
    public static function createTerm(int $yearId, string $name, int $no, string $starts, string $ends, bool $final): int
    {
        Auth::authorize('academic.manage');
        $y = self::assertYearOpen($yearId);
        if ($ends <= $starts || $starts < $y['starts_on'] || $ends > $y['ends_on']) { throw new HttpException(422, 'تواريخ الفصل يجب أن تقع داخل السنة', ['starts_on' => 'غير صالح']); }
        $r = new TermRepository();
        if ($r->where(['academic_year_id' => $yearId, 'term_no' => $no])) { throw new HttpException(422, 'رقم الفصل مستخدم في هذه السنة', ['term_no' => 'مستخدم']); }
        $id = $r->insert(['academic_year_id' => $yearId, 'name' => $name, 'term_no' => $no, 'starts_on' => $starts, 'ends_on' => $ends, 'is_final' => (int)$final]);
        AuditService::log('term.create', 'term', $id, null, ['name' => $name, 'year' => $yearId]);
        return $id;
    }

    // ---------- المراحل والصفوف والمواد ----------
    public static function saveStage(?int $id, string $name, int $order): int
    {
        Auth::authorize('academic.manage');
        $r = new StageRepository();
        try { return $id ? (function () use ($r, $id, $name, $order) { $r->findOrFail($id); $r->update($id, ['name' => $name, 'sort_order' => $order]); return $id; })() : $r->insert(['name' => $name, 'sort_order' => $order]); }
        catch (\PDOException $e) { throw self::dbError($e, 'الاسم مستخدم'); }
    }
    public static function saveGrade(?int $id, int $stageId, string $name, int $order, ?int $nextId): int
    {
        Auth::authorize('academic.manage');
        (new StageRepository())->findOrFail($stageId);
        $r = new GradeLevelRepository();
        if ($nextId) { $r->findOrFail($nextId); if ($nextId === $id) { throw new HttpException(422, 'لا يمكن أن يكون الصف التالي هو نفسه'); } }
        $data = ['stage_id' => $stageId, 'name' => $name, 'sort_order' => $order, 'next_grade_level_id' => $nextId ?: null];
        try { if ($id) { $r->findOrFail($id); $r->update($id, $data); return $id; } return $r->insert($data); }
        catch (\PDOException $e) { throw self::dbError($e, 'اسم الصف مستخدم'); }
    }
    /** @param int[] $gradeIds الصفوف المرتبطة بالمادة — إلزامية (صف واحد على الأقل). */
    public static function saveSubject(?int $id, string $name, ?string $code, array $gradeIds = []): int
    {
        Auth::authorize('academic.manage'); $sid = Tenant::require();
        $gradeIds = array_values(array_unique(array_filter(array_map('intval', $gradeIds))));
        if (!$gradeIds) { throw new HttpException(422, 'اختر الصف (أو الصفوف) الذي تُدرَّس فيه المادة أولًا', ['grade_ids' => 'اختر صفًا واحدًا على الأقل']); }
        $valid = array_map('intval', array_column(DB::all('SELECT id FROM grade_levels WHERE school_id = ? AND id IN (' . implode(',', $gradeIds) . ')', [$sid]), 'id'));
        if (count($valid) !== count($gradeIds)) { throw new HttpException(422, 'صف غير صالح', ['grade_ids' => 'غير صالح']); }
        $r = new SubjectRepository(); $data = ['name' => trim($name), 'code' => $code ?: null];
        try {
            return DB::transaction(function () use ($id, $data, $r, $gradeIds, $sid) {
                if ($id) {
                    $r->findOrFail($id);
                    $removed = array_diff(array_map('intval', array_column(DB::all('SELECT grade_level_id FROM subject_grade_levels WHERE subject_id = ?', [$id]), 'grade_level_id')), $gradeIds);
                    if ($removed && DB::value('SELECT 1 FROM section_subjects ss JOIN sections sc ON sc.id = ss.section_id WHERE ss.subject_id = ? AND ss.school_id = ? AND sc.grade_level_id IN (' . implode(',', $removed) . ') LIMIT 1', [$id, $sid])) {
                        throw new HttpException(422, 'لا يمكن فك ارتباط المادة بصف تُدرَّس فيه شعبه حاليًا. أزلها من الشعب أولًا.', ['grade_ids' => 'مستخدمة في شعب']);
                    }
                    $r->update($id, $data);
                } else { $id = $r->insert($data); }
                DB::query('DELETE FROM subject_grade_levels WHERE subject_id = ? AND school_id = ?', [$id, $sid]);
                foreach ($gradeIds as $g) { DB::query('INSERT INTO subject_grade_levels (subject_id, grade_level_id, school_id) VALUES (?,?,?)', [$id, $g, $sid]); }
                AuditService::log('subject.save', 'subject', $id, null, ['name' => $data['name'], 'grades' => $gradeIds]);
                return $id;
            });
        } catch (\PDOException $e) { throw self::dbError($e, 'اسم المادة مستخدم'); }
    }
    public static function deleteSimple(string $kind, int $id): void
    {
        Auth::authorize('academic.manage');
        $repo = match ($kind) { 'stage' => new StageRepository(), 'grade' => new GradeLevelRepository(), 'subject' => new SubjectRepository(), 'term' => new TermRepository(), default => throw new HttpException(404, 'غير معروف') };
        $repo->findOrFail($id);
        try { $repo->delete($id); } catch (\PDOException $e) { throw self::dbError($e, 'لا يمكن الحذف لارتباطه ببيانات أخرى'); }
        AuditService::log("$kind.delete", $kind, $id);
    }

    // ---------- الشعب ----------
    public static function saveSection(?int $id, array $d): int
    {
        Auth::authorize('academic.manage');
        self::assertYearOpen((int)$d['academic_year_id']);
        (new GradeLevelRepository())->findOrFail((int)$d['grade_level_id']);
        $gender = in_array($d['gender'] ?? 'mixed', ['mixed', 'boys', 'girls'], true) ? ($d['gender'] ?? 'mixed') : 'mixed';
        $school = DB::one('SELECT gender_policy FROM schools WHERE id = ?', [Tenant::require()]);
        if ($school['gender_policy'] !== 'mixed' && $gender !== 'mixed' && $gender !== ($school['gender_policy'])) { throw new HttpException(422, 'نوع الشعبة يتعارض مع نوع المدرسة', ['gender' => 'غير مسموح']); }
        $data = ['academic_year_id' => (int)$d['academic_year_id'], 'grade_level_id' => (int)$d['grade_level_id'], 'name' => trim((string)$d['name']), 'gender' => $gender,
            'capacity' => ($d['capacity'] ?? '') === '' ? null : (int)$d['capacity'], 'homeroom_staff_id' => ($d['homeroom_staff_id'] ?? '') === '' ? null : (int)$d['homeroom_staff_id']];
        if ($data['homeroom_staff_id']) { (new StaffRepository())->findOrFail($data['homeroom_staff_id']); }
        $r = new SectionRepository();
        try { if ($id) { $r->findOrFail($id); unset($data['academic_year_id']); $r->update($id, $data); } else { $id = $r->insert($data); } }
        catch (\PDOException $e) { throw self::dbError($e, 'اسم الشعبة مستخدم في هذا الصف والسنة'); }
        AuditService::log('section.save', 'section', $id, null, $data);
        return $id;
    }
    public static function deleteSection(int $id): void
    {
        Auth::authorize('academic.manage');
        $s = (new SectionRepository())->findOrFail($id);
        self::assertYearOpen((int)$s['academic_year_id']);
        if ((int)DB::value('SELECT COUNT(*) FROM student_enrollments WHERE section_id = ?', [$id]) > 0) { throw new HttpException(409, 'لا يمكن حذف شعبة فيها طلاب'); }
        DB::transaction(function () use ($id) {
            DB::query('DELETE ta FROM teacher_assignments ta JOIN section_subjects ss ON ss.id = ta.section_subject_id WHERE ss.section_id = ? AND ta.school_id = ?', [$id, Tenant::require()]);
            DB::query('DELETE FROM section_subjects WHERE section_id = ? AND school_id = ?', [$id, Tenant::require()]);
            (new SectionRepository())->delete($id);
        });
        AuditService::log('section.delete', 'section', $id, ['name' => $s['name']]);
    }

    public static function addSectionSubject(int $sectionId, int $subjectId, int $periods, ?float $max): int
    {
        Auth::authorize('academic.manage');
        $s = (new SectionRepository())->findOrFail($sectionId); self::assertYearOpen((int)$s['academic_year_id']);
        (new SubjectRepository())->findOrFail($subjectId);
        if (!DB::value('SELECT 1 FROM subject_grade_levels WHERE subject_id = ? AND grade_level_id = ? AND school_id = ?', [$subjectId, $s['grade_level_id'], Tenant::require()])) { throw new HttpException(422, 'هذه المادة غير مرتبطة بصف الشعبة. اربطها بالصف من «الصفوف والمواد» أولًا.', ['subject_id' => 'غير مرتبطة بالصف']); }
        try { $id = (new SectionSubjectRepository())->insert(['section_id' => $sectionId, 'subject_id' => $subjectId, 'weekly_periods' => max(1, $periods), 'max_score' => $max]); }
        catch (\PDOException $e) { throw self::dbError($e, 'المادة مضافة للشعبة مسبقًا'); }
        AuditService::log('section_subject.add', 'section_subject', $id, null, ['section' => $sectionId, 'subject' => $subjectId]);
        return $id;
    }
    public static function removeSectionSubject(int $id): void
    {
        Auth::authorize('academic.manage');
        (new SectionSubjectRepository())->findOrFail($id);
        try { DB::transaction(function () use ($id) { DB::query('DELETE FROM teacher_assignments WHERE section_subject_id = ? AND school_id = ?', [$id, Tenant::require()]); (new SectionSubjectRepository())->delete($id); }); }
        catch (\PDOException $e) { throw self::dbError($e, 'لا يمكن الحذف: توجد درجات أو حصص أو محتوى مرتبط بالمادة'); }
        AuditService::log('section_subject.remove', 'section_subject', $id);
    }
    public static function assignTeacher(int $sectionSubjectId, int $staffId): void
    {
        Auth::authorize('academic.manage');
        (new SectionSubjectRepository())->findOrFail($sectionSubjectId);
        $st = (new StaffRepository())->findOrFail($staffId);
        if (!(int)$st['is_teacher']) { throw new HttpException(422, 'الموظف المحدد ليس معلمًا'); }
        try { (new TeacherAssignmentRepository())->insert(['section_subject_id' => $sectionSubjectId, 'staff_id' => $staffId, 'is_primary' => 1]); }
        catch (\PDOException $e) { throw self::dbError($e, 'المعلم مُسند مسبقًا'); }
        AuditService::log('teacher.assign', 'section_subject', $sectionSubjectId, null, ['staff' => $staffId]);
    }
    public static function unassignTeacher(int $sectionSubjectId, int $staffId): void
    {
        Auth::authorize('academic.manage');
        DB::query('DELETE FROM teacher_assignments WHERE section_subject_id = ? AND staff_id = ? AND school_id = ?', [$sectionSubjectId, $staffId, Tenant::require()]);
        AuditService::log('teacher.unassign', 'section_subject', $sectionSubjectId, ['staff' => $staffId]);
    }

    /** ينسخ شعب ومواد (وإسناد المعلمين اختياريًا) من سنة إلى أخرى مفتوحة. */
    public static function cloneStructure(int $fromYear, int $toYear, bool $withTeachers = true): int
    {
        Auth::authorize('academic.manage');
        $yr = new AcademicYearRepository(); $yr->findOrFail($fromYear); self::assertYearOpen($toYear);
        $sid = Tenant::require(); $n = 0;
        DB::transaction(function () use ($fromYear, $toYear, $withTeachers, $sid, &$n) {
            foreach (DB::all('SELECT * FROM sections WHERE school_id = ? AND academic_year_id = ?', [$sid, $fromYear]) as $s) {
                if (DB::value('SELECT 1 FROM sections WHERE academic_year_id = ? AND grade_level_id = ? AND name = ?', [$toYear, $s['grade_level_id'], $s['name']])) { continue; }
                DB::query('INSERT INTO sections (school_id, academic_year_id, grade_level_id, name, gender, capacity, homeroom_staff_id, room_id) VALUES (?,?,?,?,?,?,?,?)',
                    [$sid, $toYear, $s['grade_level_id'], $s['name'], $s['gender'], $s['capacity'], $withTeachers ? $s['homeroom_staff_id'] : null, $s['room_id']]);
                $newId = DB::lastId(); $n++;
                foreach (DB::all('SELECT * FROM section_subjects WHERE section_id = ?', [$s['id']]) as $ss) {
                    $tt = $ss['term_id'] ? DB::value('SELECT b.id FROM terms a JOIN terms b ON b.term_no = a.term_no AND b.academic_year_id = ? WHERE a.id = ?', [$toYear, $ss['term_id']]) : null;   // الفصل يُطابَق برقمه
                    DB::query('INSERT INTO section_subjects (school_id, section_id, subject_id, term_id, weekly_periods, max_score, credit_hours, is_elective) VALUES (?,?,?,?,?,?,?,?)', [$sid, $newId, $ss['subject_id'], $tt ?: null, $ss['weekly_periods'], $ss['max_score'], $ss['credit_hours'], $ss['is_elective']]);
                    if ($withTeachers) { DB::query('INSERT INTO teacher_assignments (school_id, section_subject_id, staff_id, is_primary) SELECT school_id, ?, staff_id, is_primary FROM teacher_assignments WHERE section_subject_id = ?', [DB::lastId(), $ss['id']]); }
                }
            }
        });
        AuditService::log('year.clone_structure', 'academic_year', $toYear, null, ['from' => $fromYear, 'sections' => $n]);
        return $n;
    }

    public static function dbError(\PDOException $e, string $msg): HttpException
    {
        if (($e->errorInfo[1] ?? 0) === 1062 || ($e->errorInfo[1] ?? 0) === 1451 || ($e->errorInfo[1] ?? 0) === 1452) { return new HttpException(409, $msg); }
        throw $e;
    }
}
