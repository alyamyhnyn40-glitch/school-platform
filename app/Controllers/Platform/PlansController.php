<?php
declare(strict_types=1);
namespace App\Controllers\Platform;

use App\Core\{DB, HttpException, Request, Response, Validator, View};
use App\Services\PlanService;

final class PlansController
{
    public function index(Request $r): Response
    {
        return Response::html(View::render('platform/plans', ['title' => __t('nav.plans'), 'plans' => PlanService::all(), 'scripts' => ['platform-plans.js']]));
    }
    public function apiSave(Request $r): Response
    {
        $v = Validator::make($r->all(), ['name' => 'required|max:100', 'max_students' => 'numeric', 'max_storage_mb' => 'numeric', 'price' => 'numeric']);
        if ($v->fails()) { throw new HttpException(422, 'تحقق من الحقول', $v->firstErrors()); }
        $id = isset($r->params['id']) ? (int)$r->params['id'] : null;
        $d = $r->all();
        // نماذج HTML ترسل checkbox كنص؛ نطبّع
        $d['modules'] = (array)($d['modules'] ?? []);
        PlanService::save($id, $d);
        return Response::ok(['message' => 'تم الحفظ', 'redirect' => url('platform/plans')]);
    }
}
