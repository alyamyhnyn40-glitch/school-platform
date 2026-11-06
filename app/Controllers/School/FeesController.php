<?php
declare(strict_types=1);
namespace App\Controllers\School;

use App\Core\{Auth, HttpException, Request, Response, View};
use App\Services\{AcademicService, FeeService as F, FinanceReportService as R, InvoiceService as I, Money, PaymentService as P, SettingsService};

final class FeesController
{
    private function ok(string $m, array $x = []): Response { return Response::ok(['message' => $m] + $x); }
    private function year(): ?array { return AcademicService::activeYear(); }

    // ---------- صفحات ----------
    public function index(Request $r): Response
    {
        $from = date('Y-m-01'); $to = date('Y-m-d'); $c = R::collections($from, $to); $arr = R::arrears();
        return Response::html(View::render('school/fees/index', ['title' => 'الرسوم الدراسية', 'out' => R::outstanding(), 'month' => $c['by_currency'], 'arrears' => R::arrearsTotals($arr), 'arrearsCount' => count($arr), 'rates' => P::latestRate('USD') !== null, 'base' => P::baseCurrency(), 'structures' => count(F::structures())]));
    }
    public function structures(Request $r): Response
    {
        $y = $this->year(); $sections = $y ? AcademicService::sections((int)$y['id']) : [];
        return Response::html(View::render('school/fees/structures', ['title' => 'هياكل الرسوم', 'years' => AcademicService::years(), 'activeYear' => $y, 'grades' => AcademicService::grades(), 'sections' => $sections, 'structures' => F::structures(), 'discounts' => F::discounts(), 'scripts' => ['fees-structure.js']]));
    }
    public function students(Request $r): Response
    {
        $y = $this->year();
        return Response::html(View::render('school/fees/students', ['title' => 'رسوم الطلاب', 'sections' => $y ? AcademicService::sections((int)$y['id']) : [], 'structures' => F::structures(), 'scripts' => ['datatable.js', 'fees-students.js']]));
    }
    public function student(Request $r): Response
    {
        $d = F::detail((int)$r->params['id']);
        $rates = ['YER' => 1]; foreach (['YER', 'USD', 'SAR'] as $c) { $rates[$c] = P::latestRate($c); }
        return Response::html(View::render('school/fees/student', ['title' => 'رسوم ' . $d['full_name'], 'f' => $d, 'methods' => P::methods(true), 'rates' => $rates, 'base' => P::baseCurrency(), 'scripts' => ['fees-detail.js']]));
    }
    public function arrears(Request $r): Response
    {
        $y = $this->year(); $sec = $r->query('section_id') ? (int)$r->query('section_id') : null; $rows = R::arrears(null, $sec);
        return Response::html(View::render('school/fees/arrears', ['title' => 'المتأخرات', 'rows' => $rows, 'totals' => R::arrearsTotals($rows), 'sections' => $y ? AcademicService::sections((int)$y['id']) : [], 'sectionId' => $sec, 'wa' => \App\Services\NotificationService::whatsappEnabled(), 'scripts' => ['fees-arrears.js']]));
    }
    public function arrearsXlsx(Request $r): Response
    {
        Auth::authorize('reports.export'); $rows = [['الطالب', 'رقم القيد', 'الصف', 'ولي الأمر', 'هاتف ولي الأمر', 'المتأخر', 'العملة', 'أقدم استحقاق', 'أيام التأخر']];
        foreach (R::arrears(null, $r->query('section_id') ? (int)$r->query('section_id') : null) as $x) { $rows[] = [$x['full_name'], $x['student_no'], $x['grade_name'] . ' / ' . $x['section_name'], $x['guardian'], $x['guardian_phone'], (float)$x['overdue'], $x['currency'], $x['oldest'], $x['days']]; }
        return AttendanceController::xlsxResponse('arrears-' . date('Ymd') . '.xlsx', $rows, 'المتأخرات');
    }
    public function collections(Request $r): Response
    {
        [$from, $to] = $this->range($r);
        return Response::html(View::render('school/fees/collections', ['title' => 'تقرير التحصيل', 'c' => R::collections($from, $to), 'from' => $from, 'to' => $to]));
    }
    public function collectionsXlsx(Request $r): Response
    {
        Auth::authorize('reports.export'); [$from, $to] = $this->range($r); $c = R::collections($from, $to);
        $rows = [['التاريخ', 'رقم السند', 'الطالب', 'طريقة الدفع', 'المرجع', 'المبلغ', 'العملة', 'الأمين', 'الحالة']];
        foreach ($c['rows'] as $x) { $rows[] = [$x['paid_on'], $x['receipt_no'], $x['student'], $x['method'], $x['reference_no'], (float)$x['amount'], $x['currency'], $x['cashier'], $x['voided_at'] ? 'ملغى' : 'سليم']; }
        return AttendanceController::xlsxResponse("collections-$from-$to.xlsx", $rows, 'التحصيل');
    }
    private function range(Request $r): array
    {
        $from = (string)$r->query('from', date('Y-m-01')); $to = (string)$r->query('to', date('Y-m-d'));
        foreach ([$from, $to] as $d) { if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) { throw new HttpException(422, 'تاريخ غير صالح'); } }
        return [$from, $to];
    }
    public function settings(Request $r): Response
    {
        return Response::html(View::render('school/fees/settings', ['title' => 'إعدادات الرسوم', 'base' => P::baseCurrency(), 'rates' => P::rates(), 'latest' => ['USD' => P::latestRate('USD'), 'SAR' => P::latestRate('SAR'), 'YER' => P::latestRate('YER')], 'tax' => F::tax(), 'methods' => P::methods(), 'discounts' => F::discounts(),
            'prefix' => ['receipt' => SettingsService::get('fees.receipt_prefix', 'RC'), 'invoice' => SettingsService::get('fees.invoice_prefix', 'INV')]]));
    }
    public function receipt(Request $r): Response
    {
        $x = P::receipt((int)$r->params['id']); return Response::html(View::render('school/fees/receipt', ['title' => 'سند قبض ' . $x['number'], 'p' => $x], 'print'));
    }
    public function invoice(Request $r): Response
    {
        $x = I::find((int)$r->params['id']); return Response::html(View::render('school/fees/invoice', ['title' => 'فاتورة ' . $x['number'], 'i' => $x], 'print'));
    }
    public function statement(Request $r): Response
    {
        $s = R::statement((int)$r->params['id']); return Response::html(View::render('school/fees/statement', ['title' => 'كشف حساب', 's' => $s], 'print'));
    }

    // ---------- API ----------
    public function apiStudents(Request $r): Response
    {
        $res = F::search((int)$r->query('page', 1), (int)$r->query('per_page', 20), trim((string)$r->query('q', '')), $r->query('section_id') ? (int)$r->query('section_id') : null, $r->query('structure_id') ? (int)$r->query('structure_id') : null, (string)$r->query('status', ''));
        return Response::ok($res['rows'], $res['meta']);
    }
    public function saveStructure(Request $r): Response { $id = F::saveStructure(isset($r->params['id']) ? (int)$r->params['id'] : null, $r->all()); return $this->ok('تم حفظ هيكل الرسوم', ['id' => $id]); }
    public function deleteStructure(Request $r): Response { F::deleteStructure((int)$r->params['id']); return $this->ok('تم الحذف'); }
    public function bulkAssign(Request $r): Response
    {
        $x = F::bulkAssign((int)$r->params['id'], $r->input('section_id') ? (int)$r->input('section_id') : null, $r->input('grade_id') ? (int)$r->input('grade_id') : null, (bool)$r->input('apply_sibling', true));
        return $this->ok("عُيّن لـ {$x['assigned']} طالب" . ($x['siblings'] ? " ({$x['siblings']} بخصم إخوة)" : '') . ($x['skipped'] ? "، تخطّي {$x['skipped']} معيَّنين" : '') . ($x['errors'] ? '، أخطاء: ' . count($x['errors']) . ' — ' . $x['errors'][0] : ''), $x);
    }
    public function assign(Request $r): Response
    {
        $id = F::assign((int)$r->input('structure_id'), (int)$r->input('enrollment_id'), $r->input('discount_id') ? (int)$r->input('discount_id') : null, $r->input('custom_discount') !== null ? (string)$r->input('custom_discount') : null, $r->input('reason'));
        return $this->ok('تم التعيين', ['redirect' => url("school/fees/students/$id")]);
    }
    public function pay(Request $r): Response
    {
        $x = P::receive((int)$r->params['id'], (string)$r->input('amount', ''), (string)$r->input('currency', ''), (int)$r->input('method_id'), $r->input('reference_no'), $r->input('paid_on') ?: null, $r->input('rate') !== null ? (string)$r->input('rate') : null);
        return $this->ok('تم التحصيل — سند ' . $x['receipt_no'], $x + ['print' => url('school/fees/receipts/' . $x['receipt_id'] . '/print')]);
    }
    public function voidPayment(Request $r): Response { P::void((int)$r->params['id'], (string)$r->input('reason', '')); return $this->ok('أُلغيت الدفعة'); }
    public function issueInvoice(Request $r): Response { $id = I::issue((int)$r->params['id']); return $this->ok('أُصدرت الفاتورة', ['redirect' => url("school/fees/invoices/$id/print")]); }
    public function cancelInvoice(Request $r): Response { I::cancel((int)$r->params['id'], (string)$r->input('reason', '')); return $this->ok('أُلغيت الفاتورة'); }
    public function cancelFee(Request $r): Response { F::cancel((int)$r->params['id'], (string)$r->input('reason', '')); return $this->ok('أُلغيت الرسوم', ['redirect' => url('school/fees/students')]); }
    public function saveDiscount(Request $r): Response
    {
        F::saveDiscount(isset($r->params['id']) ? (int)$r->params['id'] : null, (string)$r->input('name', ''), (string)$r->input('kind', ''), (string)$r->input('value', ''), (string)$r->input('reason', 'other'));
        return $this->ok('تم الحفظ');
    }
    public function deleteDiscount(Request $r): Response { F::deleteDiscount((int)$r->params['id']); return $this->ok('تم الحذف'); }
    public function setRate(Request $r): Response { P::setRate((string)$r->input('currency', ''), (string)$r->input('rate', '')); return $this->ok('تم تسجيل سعر الصرف'); }
    public function saveMethod(Request $r): Response { P::saveMethod((string)$r->input('kind', ''), (string)$r->input('name', ''), $r->input('account_info')); return $this->ok('تمت الإضافة'); }
    public function toggleMethod(Request $r): Response { P::toggleMethod((int)$r->params['id'], (bool)$r->input('active')); return $this->ok('تم'); }
    public function saveTax(Request $r): Response
    {
        $en = (bool)$r->input('tax_enabled') && $r->input('tax_enabled') !== '0';
        F::saveTax($en, $en ? (string)$r->input('rate_percent', '') : null, $r->input('tax_label'), $r->input('tax_number'), $r->input('exempt_note'), (bool)$r->input('prices_include_tax') && $r->input('prices_include_tax') !== '0');
        return $this->ok('تم حفظ الإعدادات الضريبية');
    }
    public function savePrefixes(Request $r): Response
    {
        Auth::authorize('fees.manage');
        foreach (['receipt' => 'fees.receipt_prefix', 'invoice' => 'fees.invoice_prefix'] as $k => $key) {
            $v = strtoupper(trim((string)$r->input($k, ''))); if (!preg_match('/^[A-Z0-9]{1,8}$/', $v)) { throw new HttpException(422, 'البادئة: 1–8 أحرف إنجليزية/أرقام'); } SettingsService::set($key, $v);
        }
        return $this->ok('تم الحفظ');
    }
    public function remind(Request $r): Response { $n = R::remind((array)$r->input('fee_ids', []), (string)$r->input('channel', 'sms')); return $this->ok("أُضيفت $n رسالة تذكير إلى طابور الإرسال"); }
}
