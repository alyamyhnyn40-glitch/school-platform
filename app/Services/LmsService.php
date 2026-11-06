<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\{Auth, DB, HttpException, Tenant};

/** الدروس والمواد والواجبات والتسليم. الوصول: معلم المادة/الإدارة (إدارة) أو طالب الشعبة (قراءة وتسليم). */
final class LmsService
{
    // ---------- الوصول ----------
    public static function isAdminLevel(): bool { return Auth::can('academic.manage') || Auth::can('grades.approve'); }

    public static function ss(int $id): array
    {
        return DB::one('SELECT ss.*, sub.name AS subject_name, sc.name AS section_name, sc.academic_year_id, g.name AS grade_name FROM section_subjects ss JOIN subjects sub ON sub.id = ss.subject_id JOIN sections sc ON sc.id = ss.section_id JOIN grade_levels g ON g.id = sc.grade_level_id
            WHERE ss.id = ? AND ss.school_id = ?', [$id, Tenant::require()]) ?? throw new HttpException(404, 'المادة غير موجودة');
    }
    /** صلاحية إدارة محتوى مادة: lms.manage + (إدارة أو معلم المادة). */
    public static function assertManage(int $ssId): array
    {
        Auth::authorize('lms.manage'); $ss = self::ss($ssId);
        if (self::isAdminLevel()) { return $ss; }
        $st = Scope::staffId();
        if (!$st || !DB::value('SELECT 1 FROM teacher_assignments WHERE section_subject_id = ? AND staff_id = ? AND school_id = ?', [$ssId, $st, Tenant::require()])) { throw new HttpException(403, 'لست معلّم هذه المادة'); }
        return $ss;
    }
    /** الطالب الحالي (حساب طالب) وتسجيله النشط في شعبة المادة. */
    public static function learner(int $ssId): array
    {
        Auth::authorize('portal.view');
        if (Auth::type() !== 'student') { throw new HttpException(403, 'هذه الصفحة للطلاب'); }
        $ss = self::ss($ssId);
        $e = DB::one("SELECT e.id AS enrollment_id, e.student_id FROM student_enrollments e JOIN students s ON s.id = e.student_id WHERE s.user_id = ? AND e.school_id = ? AND e.section_id = ? AND e.status = 'active' AND s.deleted_at IS NULL", [Auth::id(), Tenant::require(), $ss['section_id']]);
        if (!$e) { throw new HttpException(403, 'لست مسجّلًا في هذه المادة'); }
        return $e + ['ss' => $ss];
    }
    /** مواد الطالب الحالي في السنة الفعّالة. */
    public static function mySubjects(): array
    {
        Auth::authorize('portal.view'); if (Auth::type() !== 'student') { throw new HttpException(403, 'هذه الصفحة للطلاب'); }
        $y = AcademicService::activeYear(); if (!$y) { return []; }
        return DB::all("SELECT ss.id, sub.name AS subject_name, sc.name AS section_name, g.name AS grade_name, (SELECT GROUP_CONCAT(st.full_name SEPARATOR '، ') FROM teacher_assignments ta JOIN staff st ON st.id = ta.staff_id WHERE ta.section_subject_id = ss.id) AS teachers,
              (SELECT COUNT(*) FROM assignments a WHERE a.section_subject_id = ss.id AND a.deleted_at IS NULL AND a.due_at > ? AND NOT EXISTS (SELECT 1 FROM submissions su WHERE su.assignment_id = a.id AND su.enrollment_id = e.id)) AS pending
            FROM student_enrollments e JOIN students s ON s.id = e.student_id JOIN section_subjects ss ON ss.section_id = e.section_id JOIN subjects sub ON sub.id = ss.subject_id JOIN sections sc ON sc.id = e.section_id JOIN grade_levels g ON g.id = sc.grade_level_id
            WHERE s.user_id = ? AND e.school_id = ? AND e.status = 'active' AND e.academic_year_id = ? ORDER BY sub.name", [now(), Auth::id(), Tenant::require(), $y['id']]);
    }
    /** المواد التي يديرها المستخدم (للمنتقي). */
    public static function manageable(): array
    {
        Auth::authorize('lms.manage'); $sid = Tenant::require(); $y = AcademicService::activeYear(); if (!$y) { return []; }
        $sql = "SELECT ss.id, sub.name AS subject, sc.name AS section, g.name AS grade FROM section_subjects ss JOIN subjects sub ON sub.id = ss.subject_id JOIN sections sc ON sc.id = ss.section_id JOIN grade_levels g ON g.id = sc.grade_level_id WHERE ss.school_id = ? AND sc.academic_year_id = ?"; $p = [$sid, $y['id']];
        if (!self::isAdminLevel()) { $st = Scope::staffId(); if (!$st) { return []; } $sql .= ' AND ss.id IN (SELECT section_subject_id FROM teacher_assignments WHERE staff_id = ? AND school_id = ?)'; array_push($p, $st, $sid); }
        return DB::all($sql . ' ORDER BY g.sort_order, sc.name, sub.name', $p);
    }
    private static function ssUsers(array $ss): array { return NotificationService::sectionUsers((int)$ss['section_id']); }
    private static function dt(string $v, string $label): string
    {
        $t = strtotime(str_replace('T', ' ', $v)); if (!$t) { throw new HttpException(422, "تاريخ غير صالح: $label"); } return date('Y-m-d H:i:s', $t);
    }

    // ---------- الدروس والمواد ----------
    /** للمدير: كل الدروس. للطالب: المنشور فقط. */
    public static function lessons(int $ssId, bool $manage): array
    {
        $manage ? self::assertManage($ssId) : self::learner($ssId);
        return DB::all('SELECT l.*, (SELECT COUNT(*) FROM materials m WHERE m.lesson_id = l.id AND m.deleted_at IS NULL) AS materials FROM lessons l WHERE l.school_id = ? AND l.section_subject_id = ? AND l.deleted_at IS NULL' . ($manage ? '' : ' AND l.is_published = 1') . ' ORDER BY l.sort_order, l.id', [Tenant::require(), $ssId]);
    }
    public static function lesson(int $id): array
    {
        $l = DB::one('SELECT * FROM lessons WHERE id = ? AND school_id = ? AND deleted_at IS NULL', [$id, Tenant::require()]) ?? throw new HttpException(404, 'الدرس غير موجود');
        $manage = Auth::can('lms.manage') && Auth::type() === 'staff';
        $manage ? self::assertManage((int)$l['section_subject_id']) : self::learner((int)$l['section_subject_id']);
        if (!$manage && !$l['is_published']) { throw new HttpException(404, 'الدرس غير موجود'); }
        $l['materials'] = DB::all('SELECT m.*, f.original_name, f.size_bytes FROM materials m LEFT JOIN files f ON f.id = m.file_id AND f.deleted_at IS NULL WHERE m.lesson_id = ? AND m.deleted_at IS NULL ORDER BY m.id', [$id]);
        $l['manage'] = $manage; $l['ss'] = self::ss((int)$l['section_subject_id']); return $l;
    }
    public static function saveLesson(int $ssId, ?int $id, string $title, ?string $body, int $order, bool $published): int
    {
        $ss = self::assertManage($ssId); $sid = Tenant::require();
        if (mb_strlen(trim($title)) < 2) { throw new HttpException(422, 'عنوان الدرس مطلوب', ['title' => 'مطلوب']); }
        if ($id) {
            $old = DB::one('SELECT * FROM lessons WHERE id = ? AND school_id = ? AND section_subject_id = ? AND deleted_at IS NULL', [$id, $sid, $ssId]) ?? throw new HttpException(404, 'الدرس غير موجود');
            DB::query('UPDATE lessons SET title = ?, body = ?, sort_order = ?, is_published = ? WHERE id = ?', [trim($title), $body, $order, (int)$published, $id]);
            $first = !$old['is_published'] && $published;
        } else {
            DB::query('INSERT INTO lessons (school_id, section_subject_id, title, body, sort_order, is_published, created_by) VALUES (?,?,?,?,?,?,?)', [$sid, $ssId, trim($title), $body, $order, (int)$published, Auth::id()]); $id = DB::lastId(); $first = $published;
        }
        if ($first) { NotificationService::notify(self::ssUsers($ss), 'درس جديد: ' . trim($title), $ss['subject_name'], url('learn/lessons/' . $id)); }
        AuditService::log('lesson.save', 'lesson', $id, null, ['title' => $title, 'published' => $published]);
        return $id;
    }
    public static function deleteLesson(int $id): void
    {
        $l = DB::one('SELECT * FROM lessons WHERE id = ? AND school_id = ? AND deleted_at IS NULL', [$id, Tenant::require()]) ?? throw new HttpException(404, 'الدرس غير موجود'); self::assertManage((int)$l['section_subject_id']);
        DB::query('UPDATE lessons SET deleted_at = NOW() WHERE id = ?', [$id]); DB::query('UPDATE materials SET deleted_at = NOW() WHERE lesson_id = ?', [$id]);
        AuditService::log('lesson.delete', 'lesson', $id, ['title' => $l['title']]);
    }
    /** $file: عنصر $_FILES عند kind=file. */
    public static function addMaterial(int $lessonId, string $kind, string $title, ?string $url, ?array $file): int
    {
        $l = DB::one('SELECT * FROM lessons WHERE id = ? AND school_id = ? AND deleted_at IS NULL', [$lessonId, Tenant::require()]) ?? throw new HttpException(404, 'الدرس غير موجود'); self::assertManage((int)$l['section_subject_id']);
        if (!in_array($kind, ['file', 'link', 'video'], true) || trim($title) === '') { throw new HttpException(422, 'بيانات المادة غير صالحة'); }
        $fid = null;
        if ($kind === 'file') { if (!$file) { throw new HttpException(422, 'اختر ملفًا', ['file' => 'مطلوب']); } $fid = FileService::store($file, 'material', $lessonId); $url = null; }
        else { $url = trim((string)$url); if (!preg_match('#^https?://[^\s<>"\']+$#i', $url)) { throw new HttpException(422, 'الرابط يجب أن يبدأ بـ http:// أو https://', ['url' => 'غير صالح']); } }
        DB::query('INSERT INTO materials (school_id, lesson_id, section_subject_id, kind, title, file_id, url) VALUES (?,?,?,?,?,?,?)', [Tenant::require(), $lessonId, $l['section_subject_id'], $kind, trim($title), $fid, $url]);
        $mid = DB::lastId(); if ($fid) { FileService::attach($fid, $mid); }
        AuditService::log('material.add', 'lesson', $lessonId, null, ['kind' => $kind, 'title' => $title]); return $mid;
    }
    public static function deleteMaterial(int $id): void
    {
        $m = DB::one('SELECT * FROM materials WHERE id = ? AND school_id = ? AND deleted_at IS NULL', [$id, Tenant::require()]) ?? throw new HttpException(404, 'غير موجود'); self::assertManage((int)$m['section_subject_id']);
        DB::query('UPDATE materials SET deleted_at = NOW() WHERE id = ?', [$id]); if ($m['file_id']) { FileService::remove((int)$m['file_id']); }
    }

    // ---------- الواجبات ----------
    public static function assignments(int $ssId, bool $manage): array
    {
        $sid = Tenant::require();
        if ($manage) {
            self::assertManage($ssId);
            return DB::all('SELECT a.*, (SELECT COUNT(*) FROM submissions s WHERE s.assignment_id = a.id) AS submitted, (SELECT COUNT(*) FROM submissions s WHERE s.assignment_id = a.id AND s.graded_at IS NOT NULL) AS graded,
                (SELECT COUNT(*) FROM student_enrollments e JOIN section_subjects ss ON ss.section_id = e.section_id WHERE ss.id = a.section_subject_id AND e.status = "active") AS total FROM assignments a WHERE a.school_id = ? AND a.section_subject_id = ? AND a.deleted_at IS NULL ORDER BY a.due_at DESC', [$sid, $ssId]);
        }
        $l = self::learner($ssId);
        return DB::all('SELECT a.*, su.id AS submission_id, su.submitted_at, su.is_late, su.score, su.graded_at FROM assignments a LEFT JOIN submissions su ON su.assignment_id = a.id AND su.enrollment_id = ? WHERE a.school_id = ? AND a.section_subject_id = ? AND a.deleted_at IS NULL ORDER BY a.due_at DESC', [$l['enrollment_id'], $sid, $ssId]);
    }
    public static function createAssignment(int $ssId, string $title, ?string $instructions, string $dueAt, ?string $max, bool $allowLate): int
    {
        $ss = self::assertManage($ssId); $due = self::dt($dueAt, 'موعد التسليم');
        if (mb_strlen(trim($title)) < 2) { throw new HttpException(422, 'عنوان الواجب مطلوب', ['title' => 'مطلوب']); }
        if ($max !== null && $max !== '' && (!is_numeric($max) || (float)$max <= 0)) { throw new HttpException(422, 'الدرجة العظمى غير صالحة', ['max_score' => 'غير صالحة']); }
        DB::query('INSERT INTO assignments (school_id, section_subject_id, title, instructions, due_at, max_score, allow_late, created_by) VALUES (?,?,?,?,?,?,?,?)', [Tenant::require(), $ssId, trim($title), $instructions, $due, $max === '' ? null : $max, (int)$allowLate, Auth::id()]);
        $id = DB::lastId(); NotificationService::notify(self::ssUsers($ss), 'واجب جديد: ' . trim($title), $ss['subject_name'] . ' — التسليم ' . $due, url('learn/assignments/' . $id));
        AuditService::log('assignment.create', 'assignment', $id, null, ['title' => $title, 'due' => $due]); return $id;
    }
    private static function assignmentRow(int $id): array
    {
        return DB::one('SELECT * FROM assignments WHERE id = ? AND school_id = ? AND deleted_at IS NULL', [$id, Tenant::require()]) ?? throw new HttpException(404, 'الواجب غير موجود');
    }
    public static function deleteAssignment(int $id): void
    {
        $a = self::assignmentRow($id); self::assertManage((int)$a['section_subject_id']);
        DB::query('UPDATE assignments SET deleted_at = NOW() WHERE id = ?', [$id]); AuditService::log('assignment.delete', 'assignment', $id, ['title' => $a['title']]);
    }
    /** صفحة المعلم: الواجب + قائمة الطلاب وحالة تسليمهم. */
    public static function assignmentForTeacher(int $id): array
    {
        $a = self::assignmentRow($id); $ss = self::assertManage((int)$a['section_subject_id']);
        $a['ss'] = $ss; $a['rows'] = DB::all("SELECT e.id AS enrollment_id, s.full_name, s.student_no, su.id AS submission_id, su.body, su.file_id, su.submitted_at, su.is_late, su.score, su.feedback, su.graded_at, f.original_name
            FROM student_enrollments e JOIN students s ON s.id = e.student_id LEFT JOIN submissions su ON su.assignment_id = ? AND su.enrollment_id = e.id LEFT JOIN files f ON f.id = su.file_id AND f.deleted_at IS NULL
            WHERE e.school_id = ? AND e.section_id = ? AND e.status = 'active' AND s.deleted_at IS NULL ORDER BY s.full_name", [$id, Tenant::require(), $ss['section_id']]);
        return $a;
    }
    public static function assignmentForLearner(int $id): array
    {
        $a = self::assignmentRow($id); $l = self::learner((int)$a['section_subject_id']);
        $a['ss'] = $l['ss']; $a['mine'] = DB::one('SELECT su.*, f.original_name FROM submissions su LEFT JOIN files f ON f.id = su.file_id AND f.deleted_at IS NULL WHERE su.assignment_id = ? AND su.enrollment_id = ?', [$id, $l['enrollment_id']]);
        $a['open'] = $a['allow_late'] || strtotime($a['due_at']) > time(); return $a;
    }
    public static function submit(int $assignmentId, ?string $body, ?array $file): int
    {
        $a = self::assignmentRow($assignmentId); $l = self::learner((int)$a['section_subject_id']); $sid = Tenant::require();
        $late = strtotime($a['due_at']) < time();
        if ($late && !$a['allow_late']) { throw new HttpException(422, 'انتهى موعد التسليم'); }
        $body = $body !== null ? trim($body) : null; if (($body === null || $body === '') && !$file) { throw new HttpException(422, 'اكتب إجابتك أو أرفق ملفًا', ['body' => 'مطلوب']); }
        return DB::transaction(function () use ($a, $l, $body, $file, $late, $sid, $assignmentId) {
            $old = DB::one('SELECT * FROM submissions WHERE assignment_id = ? AND enrollment_id = ? FOR UPDATE', [$assignmentId, $l['enrollment_id']]);
            if ($old && $old['graded_at']) { throw new HttpException(422, 'تم تصحيح تسليمك ولا يمكن تعديله'); }
            $fid = $old['file_id'] ?? null;
            if ($file) { $new = FileService::store($file, 'submission', null); if ($fid) { FileService::remove((int)$fid); } $fid = $new; }
            if ($old) { DB::query('UPDATE submissions SET body = ?, file_id = ?, submitted_at = NOW(), is_late = ?, updated_at = NOW() WHERE id = ?', [$body ?: null, $fid, (int)$late, $old['id']]); $id = (int)$old['id']; }
            else { DB::query('INSERT INTO submissions (school_id, assignment_id, enrollment_id, body, file_id, is_late) VALUES (?,?,?,?,?,?)', [$sid, $assignmentId, $l['enrollment_id'], $body ?: null, $fid, (int)$late]); $id = DB::lastId(); }
            if ($fid) { FileService::attach((int)$fid, $id); }
            AuditService::log('submission.save', 'submission', $id, null, ['assignment' => $assignmentId, 'late' => $late]); return $id;
        });
    }
    public static function grade(int $submissionId, string $score, ?string $feedback): void
    {
        $su = DB::one('SELECT su.*, a.max_score, a.section_subject_id, a.title FROM submissions su JOIN assignments a ON a.id = su.assignment_id WHERE su.id = ? AND su.school_id = ?', [$submissionId, Tenant::require()]) ?? throw new HttpException(404, 'التسليم غير موجود');
        $ss = self::assertManage((int)$su['section_subject_id']);
        if (!is_numeric($score) || (float)$score < 0) { throw new HttpException(422, 'الدرجة غير صالحة', ['score' => 'غير صالحة']); }
        if ($su['max_score'] !== null && (float)$score > (float)$su['max_score']) { throw new HttpException(422, 'الدرجة تتجاوز العظمى (' . (float)$su['max_score'] . ')', ['score' => 'خارج المدى']); }
        DB::query('UPDATE submissions SET score = ?, feedback = ?, graded_by = ?, graded_at = NOW() WHERE id = ?', [round((float)$score, 2), $feedback ? mb_substr($feedback, 0, 2000) : null, Auth::id(), $submissionId]);
        $uid = DB::value('SELECT s.user_id FROM student_enrollments e JOIN students s ON s.id = e.student_id WHERE e.id = ?', [$su['enrollment_id']]);
        if ($uid) { NotificationService::notify([(int)$uid], 'تم تصحيح واجبك: ' . $su['title'], 'الدرجة ' . round((float)$score, 2) . ($su['max_score'] !== null ? ' من ' . (float)$su['max_score'] : ''), url('learn/assignments/' . $su['assignment_id'])); }
        AuditService::log('submission.grade', 'submission', $submissionId, ['score' => $su['score']], ['score' => $score]);
    }
}
