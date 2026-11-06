<?php
declare(strict_types=1);
namespace App\Controllers\Platform;

use App\Core\{DB, HttpException, Request, Response, Validator, View};
use App\Services\{SchoolService, PlanService};

final class SchoolsController
{
    // ---------- صفحات ----------
    public function index(Request $r): Response
    {
        return Response::html(View::render('platform/schools/index', ['title' => __t('nav.schools'), 'scripts' => ['datatable.js', 'platform-schools.js']]));
    }
    public function create(Request $r): Response
    {
        return Response::html(View::render('platform/schools/form', ['title' => 'مدرسة جديدة', 'school' => null, 'plans' => PlanService::all()]));
    }
    public function show(Request $r): Response
    {
        $id = (int)$r->params['id'];
        $school = SchoolService::find($id);
        return Response::html(View::render('platform/schools/show', [
            'title' => $school['name'], 'school' => $school, 'stats' => SchoolService::stats($id), 'plans' => PlanService::all(),
            'subs' => DB::all('SELECT s.*, p.name AS plan_name FROM school_subscriptions s JOIN subscription_plans p ON p.id = s.plan_id WHERE s.school_id = ? ORDER BY s.id DESC', [$id]),
            'support' => DB::all('SELECT l.*, u.full_name FROM support_access_logs l JOIN users u ON u.id = l.admin_user_id WHERE l.school_id = ? ORDER BY l.id DESC LIMIT 10', [$id]),
            'scripts' => ['platform-school.js'],
        ]));
    }

    // ---------- API ----------
    public function apiList(Request $r): Response
    {
        $res = SchoolService::list((int)$r->query('page', 1), (int)$r->query('per_page', 15), trim((string)$r->query('q', '')), (string)$r->query('status', ''));
        return Response::ok($res['rows'], $res['meta']);
    }

    public function apiCreate(Request $r): Response
    {
        $v = Validator::make($r->all(), [
            'name' => 'required|max:200', 'city' => 'max:100', 'phone' => 'max:30', 'email' => 'email|max:190', 'plan_id' => 'required|numeric',
            'admin_name' => 'required|max:200', 'admin_email' => 'required|email|max:190', 'admin_phone' => 'max:30',
            'starts_on' => 'date', 'ends_on' => 'date', 'gender_policy' => 'in:mixed,boys,girls', 'base_currency' => 'in:YER,USD,SAR',
        ], ['name' => 'اسم المدرسة', 'admin_name' => 'اسم المدير', 'admin_email' => 'بريد المدير', 'plan_id' => 'الخطة']);
        if ($v->fails()) { throw new HttpException(422, 'تحقق من الحقول', $v->firstErrors()); }
        [$id, $temp] = SchoolService::create($r->all());
        return Response::ok(['id' => $id, 'temp_password' => $temp, 'message' => 'تم إنشاء المدرسة', 'redirect' => url("platform/schools/$id?created=1")]);
    }

    public function apiUpdate(Request $r): Response
    {
        $v = Validator::make($r->all(), ['name' => 'required|max:200', 'email' => 'email|max:190', 'gender_policy' => 'in:mixed,boys,girls', 'base_currency' => 'in:YER,USD,SAR']);
        if ($v->fails()) { throw new HttpException(422, 'تحقق من الحقول', $v->firstErrors()); }
        SchoolService::update((int)$r->params['id'], $r->all());
        return Response::ok(['message' => 'تم الحفظ']);
    }

    public function apiStatus(Request $r): Response
    {
        SchoolService::setStatus((int)$r->params['id'], (string)$r->input('status'));
        return Response::ok(['message' => 'تم تحديث الحالة', 'redirect' => url('platform/schools/' . (int)$r->params['id'])]);
    }

    public function apiSubscription(Request $r): Response
    {
        $v = Validator::make($r->all(), ['plan_id' => 'required|numeric', 'starts_on' => 'required|date', 'ends_on' => 'date', 'amount' => 'numeric']);
        if ($v->fails()) { throw new HttpException(422, 'تحقق من الحقول', $v->firstErrors()); }
        SchoolService::changeSubscription((int)$r->params['id'], (int)$r->input('plan_id'), (string)$r->input('starts_on'), $r->input('ends_on') ?: null, (float)$r->input('amount', 0));
        return Response::ok(['message' => 'تم تحديث الاشتراك', 'redirect' => url('platform/schools/' . (int)$r->params['id'])]);
    }

    public function apiResetAdmin(Request $r): Response
    {
        [$u, $temp] = SchoolService::resetAdminPassword((int)$r->params['id']);
        return Response::ok(['message' => 'تمت إعادة التعيين', 'admin' => $u['full_name'], 'email' => $u['email'], 'temp_password' => $temp]);
    }

    public function apiSupportEnter(Request $r): Response
    {
        SchoolService::enterSupport((int)$r->params['id'], (string)$r->input('reason', ''));
        return Response::ok(['redirect' => url('dashboard')]);
    }
    public function supportExit(Request $r): Response
    {
        $sid = \App\Core\Session::get('support_mode')['school_id'] ?? null;
        SchoolService::exitSupport();
        return Response::redirect($sid ? "/platform/schools/$sid" : '/platform');
    }
}
