<?php
declare(strict_types=1);
namespace App\Controllers\School;

use App\Core\{Auth, HttpException, Request, Response, View};
use App\Services\{AcademicService, Scope, StaffService, TimetableService as T};

final class TimetableController
{
    public function index(Request $r): Response
    {
        if (!Auth::can('timetable.manage')) { return Response::redirect('/school/timetable/mine'); }
        $y = AcademicService::activeYear(); $sections = $y ? AcademicService::sections((int)$y['id']) : [];
        $secId = (int)($r->query('section') ?: ($sections[0]['id'] ?? 0));
        return Response::html(View::render('school/timetable/index', ['title' => 'الجدول الدراسي', 'sections' => $sections, 'secId' => $secId, 'year' => $y, 'cfg' => T::config(), 'slots' => $secId ? T::forSection($secId) : [],
            'items' => $secId ? AcademicService::sectionSubjects($secId) : [], 'rooms' => T::rooms(), 'teachers' => StaffService::teachers(), 'canManage' => true, 'scripts' => ['timetable.js']]));
    }
    public function mine(Request $r): Response
    {
        $staff = Scope::staffId();
        if (!$staff) { return Response::html(View::render('school/timetable/teacher', ['title' => 'جدولي', 'name' => '', 'cfg' => T::config(), 'slots' => [], 'none' => true])); }
        return $this->teacherPage($staff);
    }
    public function teacher(Request $r): Response { return $this->teacherPage((int)$r->params['id']); }
    private function teacherPage(int $id): Response
    {
        $slots = T::forTeacher($id);
        $name = \App\Core\DB::value('SELECT full_name FROM staff WHERE id = ? AND school_id = ?', [$id, \App\Core\Tenant::require()]) ?? throw new HttpException(404, 'غير موجود');
        return Response::html(View::render('school/timetable/teacher', ['title' => 'جدول ' . $name, 'name' => $name, 'cfg' => T::config(), 'slots' => $slots, 'none' => false]));
    }
    public function config(Request $r): Response
    {
        \App\Core\Auth::authorize('timetable.manage');
        return Response::html(View::render('school/timetable/config', ['title' => 'إعدادات الجدول', 'cfg' => T::config(), 'rooms' => T::rooms(), 'dayNames' => T::DAY_NAMES, 'scripts' => ['timetable-config.js']]));
    }
    public function place(Request $r): Response
    {
        $id = T::place((int)$r->input('section_subject_id'), (int)$r->input('day'), (int)$r->input('period'), $r->input('room_id') ? (int)$r->input('room_id') : null, $r->input('staff_id') ? (int)$r->input('staff_id') : null);
        return Response::ok(['id' => $id, 'message' => 'تمت إضافة الحصة']);
    }
    public function remove(Request $r): Response { T::remove((int)$r->params['id']); return Response::ok(['message' => 'تم حذف الحصة']); }
    public function saveConfig(Request $r): Response { T::saveConfig((array)$r->input('days', []), (array)$r->input('periods', [])); return Response::ok(['message' => 'تم حفظ الإعدادات']); }
    public function addRoom(Request $r): Response { T::addRoom((string)$r->input('name', ''), $r->input('capacity') ? (int)$r->input('capacity') : null); return Response::ok(['message' => 'تمت إضافة القاعة']); }
    public function deleteRoom(Request $r): Response { T::deleteRoom((int)$r->params['id']); return Response::ok(['message' => 'تم حذف القاعة']); }
}
