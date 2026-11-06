<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\{Auth, HttpException, Request, Response, View};
use App\Services\{CheckoutService as C, FinanceReportService, OnlinePaymentService as O, PortalService};

final class CheckoutController
{
    // ----- ولي الأمر -----
    public function checkout(Request $r): Response
    {
        $feeId = (int)$r->params['fee']; $s = O::suggest($feeId); PortalService::assertStudent($s['student_id']);
        $st = FinanceReportService::statement($s['student_id']); $fee = null; foreach ($st['fees'] as $f) { if ((int)$f['id'] === $feeId) { $fee = $f; } } if (!$fee) { throw new HttpException(404, 'الرسوم غير موجودة'); }
        return Response::html(View::render('portal/checkout', ['title' => 'السداد', 'fee' => $fee, 's' => $s, 'student' => $st['student'], 'methods' => C::visible(), 'claims' => C::myClaims($feeId), 'scripts' => ['portal-pay.js', 'image-preview.js']]));
    }
    public function claim(Request $r): Response
    {
        $f = !empty($_FILES['receipt']) && ($_FILES['receipt']['error'] ?? 4) !== UPLOAD_ERR_NO_FILE ? $_FILES['receipt'] : null;
        C::submitClaim((int)$r->input('student_fee_id', 0), (int)$r->input('method_id', 0), (string)$r->input('amount', ''), (string)$r->input('reference_no', ''), (string)$r->input('paid_on', ''), $r->input('note') ? (string)$r->input('note') : null, $f);
        return Response::ok(['message' => 'استلمنا إشعارك وسيراجعه المحاسب قريبًا', 'redirect' => url('portal/pay/' . (int)$r->input('student_fee_id', 0))]);
    }

    // ----- المدير -----
    public function methods(Request $r): Response { return Response::html(View::render('school/fees/methods', ['title' => 'طرق الدفع وظهورها', 'rows' => C::manage(), 'gateway' => O::enabled(), 'gatewayDriver' => O::driver(), 'scripts' => ['method-presets.js']])); }
    public function saveMethod(Request $r): Response { C::save(isset($r->params['id']) ? (int)$r->params['id'] : null, (string)$r->input('label', ''), $r->input('instructions') !== null ? (string)$r->input('instructions') : null, (bool)$r->input('is_visible') && $r->input('is_visible') !== '0', (int)$r->input('sort_order', 0)); return Response::ok(['message' => 'تم الحفظ']); }
    public function visibility(Request $r): Response { C::setVisible((int)$r->params['id'], (bool)$r->input('visible') && $r->input('visible') !== '0'); return Response::ok(['message' => 'تم التحديث']); }
    public function deleteMethod(Request $r): Response { C::delete((int)$r->params['id']); return Response::ok(['message' => 'حُذفت الطريقة']); }

    // ----- المحاسب -----
    public function claims(Request $r): Response { return Response::html(View::render('school/fees/claims', ['title' => 'إشعارات الدفع', 'rows' => C::claims((string)$r->query('status', 'pending')), 'status' => (string)$r->query('status', 'pending')])); }
    public function approve(Request $r): Response { $x = C::approve((int)$r->params['id']); return Response::ok(['message' => 'قُيّدت الدفعة — سند ' . $x['receipt_no']]); }
    public function reject(Request $r): Response { C::reject((int)$r->params['id'], (string)$r->input('note', '')); return Response::ok(['message' => 'رُفض الإشعار وأُبلغ ولي الأمر']); }
}
