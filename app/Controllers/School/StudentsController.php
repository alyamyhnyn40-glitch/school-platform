<?php
declare(strict_types=1);
namespace App\Controllers\School;

use App\Core\{HttpException, Request, Response, Validator, View};
use App\Services\{AcademicService, StudentService as S};

final class StudentsController
{
    private function validate(Request $r, array $rules, array $labels = []): void
    {
        $v = Validator::make($r->all(), $rules, $labels);
        if ($v->fails()) { throw new HttpException(422, 'تحقق من الحقول المطلوبة', $v->firstErrors()); }
    }
    private function activeSections(): array { $y = AcademicService::activeYear(); return $y ? AcademicService::sections((int)$y['id']) : []; }

    public function index(Request $r): Response
    {
        return Response::html(View::render('school/students/index', ['title' => 'الطلاب', 'sections' => $this->activeSections(), 'grades' => AcademicService::grades(), 'scripts' => ['datatable.js', 'school-students.js']]));
    }
    public function create(Request $r): Response
    {
        return Response::html(View::render('school/students/form', ['title' => 'طالب جديد', 'sections' => $this->activeSections(), 'year' => AcademicService::activeYear(), 'scripts' => ['wizard.js']]));
    }
    public function show(Request $r): Response
    {
        $p = S::profile((int)$r->params['id']); $ov = S::overview((int)$r->params['id']);
        return Response::html(View::render('school/students/show', $p + ['ov' => $ov, 'title' => $p['student']['full_name'], 'sections' => $this->activeSections(), 'scripts' => ['school-students-show.js']]));
    }

    public function apiList(Request $r): Response
    {
        $res = S::search((int)$r->query('page', 1), (int)$r->query('per_page', 20), trim((string)$r->query('q', '')), $r->query('section_id') ? (int)$r->query('section_id') : null, $r->query('grade_id') ? (int)$r->query('grade_id') : null, (string)$r->query('status', ''));
        return Response::ok($res['rows'], $res['meta']);
    }
    public function apiCreate(Request $r): Response
    {
        $this->validate($r, ['full_name' => 'required|max:200', 'gender' => 'required|in:male,female', 'birth_date' => 'date', 'phone' => 'max:30', 'section_id' => 'numeric', 'student_no' => 'max:30'], ['full_name' => 'اسم الطالب', 'gender' => 'الجنس']);
        $id = S::create($r->all());
        $st = \App\Core\DB::one("SELECT s.full_name, s.student_no, CONCAT(g.name, ' / ', sc.name) AS sec FROM students s LEFT JOIN student_enrollments e ON e.student_id = s.id AND e.status = 'active' LEFT JOIN sections sc ON sc.id = e.section_id LEFT JOIN grade_levels g ON g.id = sc.grade_level_id WHERE s.id = ?", [$id]);
        $msg = 'تم تسجيل الطالب «' . ($st['full_name'] ?? '') . '» برقم القيد ' . ($st['student_no'] ?? '') . (!empty($st['sec']) ? ' في ' . $st['sec'] : ' (بدون شعبة)') . '. الخطوة التالية: أضف ولي الأمر من تبويب «أولياء الأمور».';
        return Response::ok(['id' => $id, 'message' => $msg, 'redirect' => url("school/students/$id")]);
    }
    public function apiUpdate(Request $r): Response
    {
        $this->validate($r, ['full_name' => 'required|max:200', 'gender' => 'in:male,female', 'birth_date' => 'date', 'phone' => 'max:30']);
        S::update((int)$r->params['id'], $r->all());
        return Response::ok(['message' => 'تم الحفظ']);
    }
    public function apiDelete(Request $r): Response { S::delete((int)$r->params['id']); return Response::ok(['message' => 'تم الحذف', 'redirect' => url('school/students')]); }
    public function apiEnroll(Request $r): Response
    {
        $this->validate($r, ['section_id' => 'required|numeric']);
        S::enroll((int)$r->params['id'], (int)$r->input('section_id'));
        return Response::ok(['message' => 'تم تحديث الشعبة']);
    }
    public function apiStatus(Request $r): Response
    {
        S::changeStatus((int)$r->params['id'], (string)$r->input('status'), (string)$r->input('reason', ''));
        return Response::ok(['message' => 'تم تحديث الحالة']);
    }
    public function apiAddGuardian(Request $r): Response
    {
        $this->validate($r, ['full_name' => 'required|max:200', 'phone' => 'max:30', 'email' => 'email', 'relation' => 'in:father,mother,brother,sister,uncle,aunt,grandfather,grandmother,other']);
        S::addGuardian((int)$r->params['id'], $r->all());
        return Response::ok(['message' => 'تم ربط ولي الأمر']);
    }
    public function apiUpdateGuardian(Request $r): Response { S::updateGuardianLink((int)$r->params['link'], $r->all()); return Response::ok(['message' => 'تم الحفظ']); }
    public function apiRemoveGuardian(Request $r): Response { S::removeGuardianLink((int)$r->params['link']); return Response::ok(['message' => 'تم فك الربط']); }
    public function apiDiscipline(Request $r): Response
    {
        $this->validate($r, ['kind' => 'required|in:violation,warning,commendation', 'incident_date' => 'required|date', 'description' => 'required|max:2000']);
        S::addDiscipline((int)$r->params['id'], (string)$r->input('kind'), (string)$r->input('incident_date'), (string)$r->input('description'), $r->input('action_taken'));
        return Response::ok(['message' => 'تم التسجيل']);
    }
    public function apiCreateAccount(Request $r): Response
    {
        [$u, $t] = S::createAccount((int)$r->params['id']);
        return Response::ok(['message' => 'تم إنشاء الحساب', 'email' => "اسم المستخدم: $u", 'temp_password' => $t]);
    }
    public function apiResetAccount(Request $r): Response
    {
        [$u, $t] = S::resetAccount((int)$r->params['id']);
        return Response::ok(['message' => 'تمت إعادة التعيين', 'email' => "اسم المستخدم: $u", 'temp_password' => $t]);
    }

    /** بطاقة بيانات الطالب للطباعة (A4). */
    public function printSheet(Request $r): Response
    {
        $p = S::profile((int)$r->params['id']); $ov = S::overview((int)$r->params['id']);
        return Response::html(View::render('school/students/print', $p + ['ov' => $ov, 'title' => 'بطاقة بيانات الطالب', 'school' => \App\Core\DB::one('SELECT name, city, phone FROM schools WHERE id = ?', [\App\Core\Tenant::require()])], 'print'));
    }
}
