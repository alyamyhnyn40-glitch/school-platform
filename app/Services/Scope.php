<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\{Auth, DB, Tenant};

/** نطاق السجلات (Row-level): المعلم يرى شعبه المسندة فقط، وولي الأمر أبناءه فقط. */
final class Scope
{
    public static function staffId(): ?int
    {
        $v = DB::value('SELECT id FROM staff WHERE user_id = ? AND school_id = ? AND deleted_at IS NULL', [Auth::id(), Tenant::require()]);
        return $v === null ? null : (int)$v;
    }
    /** null = بلا قيد (يملك صلاحية الإدارة). مصفوفة (قد تكون فارغة) = الشعب المسموحة فقط. */
    public static function sectionIds(string $managePerm = 'students.manage'): ?array
    {
        if (Auth::can($managePerm) || Auth::can('attendance.edit') || Auth::can('grades.approve')) { return null; }
        // أدوار مدرسية عامة (مرشد/محاسب/تقارير): ترى كل الشعب للقراءة. المعلم (يسجّل الحضور/يدخل الدرجات) يبقى مقيَّدًا بشعبه.
        if (Auth::canAny('reports.view', 'risk.view', 'counseling.manage', 'students.discipline') && !Auth::canAny('attendance.take', 'grades.edit')) { return null; }
        if (Auth::can('fees.view') && !Auth::canAny('attendance.take', 'grades.edit')) { return null; }
        $st = self::staffId();
        if (!$st) { return []; }
        $rows = DB::all('SELECT DISTINCT ss.section_id FROM teacher_assignments ta JOIN section_subjects ss ON ss.id = ta.section_subject_id WHERE ta.staff_id = ? AND ta.school_id = ?', [$st, Tenant::require()]);
        $homeroom = DB::all('SELECT id AS section_id FROM sections WHERE homeroom_staff_id = ? AND school_id = ?', [$st, Tenant::require()]);
        return array_values(array_unique(array_map('intval', array_column(array_merge($rows, $homeroom), 'section_id'))));
    }
    public static function canSeeSection(int $sectionId): bool
    {
        $ids = self::sectionIds();
        return $ids === null || in_array($sectionId, $ids, true);
    }
    /** أبناء ولي الأمر الحالي (حسب الربط وصلاحية can_view). */
    public static function guardianStudentIds(): array
    {
        return array_map('intval', array_column(DB::all('SELECT sg.student_id FROM student_guardians sg JOIN guardians g ON g.id = sg.guardian_id
            WHERE g.user_id = ? AND sg.can_view = 1 AND sg.school_id = ?', [Auth::id(), Tenant::require()]), 'student_id'));
    }
}
