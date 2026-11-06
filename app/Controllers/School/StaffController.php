<?php
declare(strict_types=1);
namespace App\Controllers\School;

use App\Core\{DB, HttpException, Request, Response, Tenant, Validator, View};
use App\Services\StaffService as S;

final class StaffController
{
    private function validate(Request $r, array $rules): void
    {
        $v = Validator::make($r->all(), $rules);
        if ($v->fails()) { throw new HttpException(422, 'تحقق من الحقول المطلوبة', $v->firstErrors()); }
    }
    public function index(Request $r): Response { return Response::html(View::render('school/staff/index', ['title' => 'الموظفون والمعلمون', 'scripts' => ['datatable.js', 'school-staff.js']])); }
    public function create(Request $r): Response
    {
        return Response::html(View::render('school/staff/form', ['title' => 'موظف جديد', 'roles' => DB::all("SELECT id, name FROM roles WHERE school_id = ? AND code NOT IN ('student','guardian') ORDER BY id", [Tenant::require()])]));
    }
    public function show(Request $r): Response { $s = S::find((int)$r->params['id']); return Response::html(View::render('school/staff/show', ['title' => $s['full_name'], 's' => $s])); }
    public function apiList(Request $r): Response
    {
        $res = S::search((int)$r->query('page', 1), (int)$r->query('per_page', 20), trim((string)$r->query('q', '')), (string)$r->query('kind', ''));
        return Response::ok($res['rows'], $res['meta']);
    }
    public function apiCreate(Request $r): Response
    {
        $this->validate($r, ['full_name' => 'required|max:200', 'email' => 'email', 'phone' => 'max:30', 'gender' => 'in:male,female', 'birth_date' => 'date', 'hire_date' => 'date']);
        [$id, $temp] = S::create($r->all());
        $data = ['id' => $id, 'message' => 'تمت الإضافة', 'redirect' => url("school/staff/$id")];
        if ($temp) { $data += ['temp_password' => $temp, 'email' => $r->input('email') ?: $r->input('phone')]; }
        return Response::ok($data);
    }
    public function apiUpdate(Request $r): Response
    {
        $this->validate($r, ['full_name' => 'required|max:200', 'email' => 'email', 'phone' => 'max:30', 'status' => 'in:active,on_leave,terminated']);
        S::update((int)$r->params['id'], $r->all());
        return Response::ok(['message' => 'تم الحفظ']);
    }
    public function apiDelete(Request $r): Response { S::delete((int)$r->params['id']); return Response::ok(['message' => 'تم الحذف', 'redirect' => url('school/staff')]); }
}
