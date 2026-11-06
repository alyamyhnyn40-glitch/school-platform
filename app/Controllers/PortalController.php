<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\{Request, Response, View};
use App\Services\{PortalService as P, ReportCardService};

final class PortalController
{
    public function home(Request $r): Response
    {
        $kids = P::children();
        if (count($kids) === 1) { return Response::redirect('/portal/student/' . (int)$kids[0]['id']); }
        return Response::html(View::render('portal/children', ['title' => 'أبنائي', 'kids' => $kids]));
    }
    /** تبديل المدرسة الحالية لولي أمر له أبناء في أكثر من مدرسة. */
    public function switchSchool(Request $r): Response
    {
        \App\Services\AuthService::switchSchool((int)$r->input('school_id', 0));
        return Response::ok(['message' => 'تم التبديل', 'redirect' => url('portal')]);
    }
    public function student(Request $r): Response
    {
        $o = P::overview((int)$r->params['id']);
        return Response::html(View::render('portal/student', $o + ['title' => $o['student']['full_name'], 'multi' => count(P::children()) > 1]));
    }
    public function card(Request $r): Response
    {
        $c = ReportCardService::find((int)$r->params['id']);
        return Response::html(View::render('school/grades/card', ['title' => 'شهادة', 'cards' => [$c['data']]], 'print'));
    }
    /** كشف حساب الرسوم للابن (قراءة فقط، لولي الأمر/الطالب المصرَّح له). */
    public function fees(Request $r): Response
    {
        $id = (int)$r->params['id']; \App\Services\PortalService::assertStudent($id);
        $st = \App\Services\FinanceReportService::statement($id); $pay = []; $on = \App\Core\Auth::type() === 'guardian' && \App\Services\CheckoutService::visible() !== [];
        if ($on) { foreach ($st['fees'] as $f) { if (\App\Services\Money::cents($f['balance']) > 0) { $pay[(int)$f['id']] = true; } } }
        return Response::html(View::render('portal/fees', ['title' => 'الرسوم', 's' => $st, 'studentId' => $id, 'pay' => $pay]));
    }
}
