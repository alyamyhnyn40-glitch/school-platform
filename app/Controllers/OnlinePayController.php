<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\{Auth, HttpException, Request, Response, View};
use App\Services\OnlinePaymentService as O;

final class OnlinePayController
{
    // ----- ولي الأمر -----
    public function start(Request $r): Response
    {
        $x = O::start((int)$r->input('student_fee_id', 0), $r->input('amount') !== null ? (string)$r->input('amount') : null);
        return Response::ok(['message' => 'جارٍ تحويلك إلى صفحة الدفع…', 'redirect' => $x['checkout_url']]);
    }
    public function returnPage(Request $r): Response
    {
        $op = O::ownStatus((string)$r->params['ref']);
        return Response::html(View::render('portal/pay_return', ['title' => 'نتيجة الدفع', 'op' => $op, 'scripts' => ['pay-return.js']]));
    }
    public function status(Request $r): Response
    {
        $op = O::ownStatus((string)$r->params['ref']);
        return Response::ok(['status' => $op['status'], 'label' => O::STATUSES[$op['status']] ?? $op['status'], 'receipt_no' => $op['receipt_no'], 'reason' => $op['status'] === 'review' ? 'دفعتك مستلمة لدى المزود وتجري مراجعتها في المدرسة' : $op['failure_reason']]);
    }

    // ----- إشعار المزود (عام، بلا جلسة) -----
    public function callback(Request $r): Response
    {
        $h = []; foreach ($_SERVER as $k => $v) { if (str_starts_with($k, 'HTTP_') && is_string($v)) { $h[strtolower(str_replace('_', '-', substr($k, 5)))] = $v; } }
        [$code, $msg] = O::handleCallback((string)$r->params['token'], (string)file_get_contents('php://input'), $h, $r->ip());
        return new Response((string)json_encode(['status' => $msg]), $code, ['Content-Type' => 'application/json']);
    }

    // ----- الصفحة التجريبية (غير الإنتاج فقط) -----
    public function sandbox(Request $r): Response
    {
        return Response::html(View::render('pay/sandbox', ['title' => 'دفع تجريبي', 'op' => O::sandboxInfo((string)$r->params['ref'])], 'bare'));
    }
    public function sandboxFinish(Request $r): Response
    {
        $res = O::sandboxFinish((string)$r->params['ref'], $r->input('outcome') === 'pay');
        return Response::redirect(url('portal/pay/return/' . $r->params['ref']));
    }

    // ----- إدارة المدرسة -----
    public function admin(Request $r): Response
    {
        $canCfg = Auth::can('fees.gateway');
        return Response::html(View::render('school/fees/online', ['title' => 'الدفع الإلكتروني', 'cfg' => $canCfg ? O::config() : null, 'rows' => O::list((string)$r->query('status', '')), 'status' => (string)$r->query('status', ''), 'scripts' => ['online-pay.js', 'pay-presets.js']]));
    }
    public function save(Request $r): Response { O::saveConfig($r->all()); return Response::ok(['message' => 'حُفظت الإعدادات']); }
    public function reconcile(Request $r): Response { return Response::ok(['message' => O::reconcile((int)$r->params['id'])]); }
    public function events(Request $r): Response { return Response::ok(O::events((int)$r->params['id'])); }
}
