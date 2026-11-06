<?php
declare(strict_types=1);
namespace App\Controllers\Platform;

use App\Core\{HttpException, Request, Response, View};
use App\Services\{PlanService, SchoolRequestService as S};

final class RequestsController
{
    public function index(Request $r): Response
    {
        $st = (string)$r->query('status', ''); return Response::html(View::render('platform/requests', ['title' => 'طلبات تسجيل المدارس', 'rows' => S::list($st), 'status' => $st, 'plans' => PlanService::all(), 'scripts' => ['platform-requests.js']]));
    }
    public function approve(Request $r): Response
    {
        $x = S::approve((int)$r->params['id'], (int)$r->input('plan_id', 0), ['school_name' => (string)$r->input('school_name', '')]);
        return Response::ok(['message' => 'قُبل الطلب وأُنشئت المدرسة', 'school_id' => $x['school_id'], 'temp_password' => $x['temp_password'], 'redirect' => null]);
    }
    public function reject(Request $r): Response { S::reject((int)$r->params['id'], (string)$r->input('note', '')); return Response::ok(['message' => 'رُفض الطلب وأُبلغ المتقدّم']); }
    public function logo(Request $r): Response
    {
        $p = S::logoPath((int)$r->params['id']); if (!$p) { throw new HttpException(404, 'لا شعار'); }
        $mime = ['png' => 'image/png', 'jpg' => 'image/jpeg', 'webp' => 'image/webp'][pathinfo($p, PATHINFO_EXTENSION)] ?? 'application/octet-stream';
        return new Response((string)file_get_contents($p), 200, ['Content-Type' => $mime, 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, max-age=300']);
    }
}
