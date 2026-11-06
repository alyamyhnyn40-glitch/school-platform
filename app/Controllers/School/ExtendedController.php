<?php
declare(strict_types=1);
namespace App\Controllers\School;

use App\Core\{Auth, DB, HttpException, Request, Response, Tenant, View};
use App\Services\{AcademicService, AdmissionService as Adm, AnalyticsService as An, CounselingService as Co, CredentialService as Cr, FacilityService as Fa, PayrollService as Pay, RiskService as Rk};

/** الوحدات الموسعة (المرحلة 5): قبول، إنذار مبكر، إرشاد، رواتب، مرافق، شهادات، تحليلات. */
final class ExtendedController
{
    private function sections(): array { $y = AcademicService::activeYear(); return $y ? AcademicService::sections((int)$y['id']) : []; }
    private function studentByNo(string $no): int
    {
        $id = DB::value('SELECT id FROM students WHERE school_id = ? AND student_no = ? AND deleted_at IS NULL', [Tenant::require(), trim($no)]);
        if (!$id) { throw new HttpException(422, 'رقم الطالب غير موجود', ['student_no' => 'غير موجود']); } return (int)$id;
    }
    private function page(string $view, string $title, array $d = [], array $scripts = []): Response { return Response::html(View::render($view, ['title' => $title, 'scripts' => $scripts] + $d)); }

    // ---- القبول ----
    public function admissions(Request $r): Response
    {
        $res = Adm::search((int)$r->query('page', 1), 25, (string)$r->query('q', ''), (string)$r->query('status', ''), $r->query('grade') ? (int)$r->query('grade') : null);
        return $this->page('school/ext/admissions', 'القبول والتسجيل', ['stats' => Adm::stats(), 'res' => $res, 'grades' => AcademicService::grades(), 'q' => (string)$r->query('q', ''), 'status' => (string)$r->query('status', '')]);
    }
    public function admissionNew(Request $r): Response { return $this->page('school/ext/admission_form', 'طلب التحاق جديد', ['grades' => AcademicService::grades(), 'a' => null]); }
    public function admissionEdit(Request $r): Response { return $this->page('school/ext/admission_form', 'تعديل الطلب', ['grades' => AcademicService::grades(), 'a' => Adm::find((int)$r->params['id'])]); }
    public function admissionShow(Request $r): Response { return $this->page('school/ext/admission', 'طلب التحاق', ['a' => Adm::find((int)$r->params['id']), 'sections' => $this->sections()]); }
    public function admissionSave(Request $r): Response
    {
        $d = (array)$r->all(); if (!empty($r->params['id'])) { Adm::update((int)$r->params['id'], $d); return Response::ok(['message' => 'تم الحفظ', 'redirect' => url('school/admissions/' . (int)$r->params['id'])]); }
        $id = Adm::create($d); return Response::ok(['message' => 'سُجّل الطلب', 'redirect' => url("school/admissions/$id")]);
    }
    public function admissionStatus(Request $r): Response { Adm::setStatus((int)$r->params['id'], (string)$r->input('status', ''), $r->input('note') !== null ? (string)$r->input('note') : null); return Response::ok(['message' => 'تم تحديث الحالة']); }
    public function admissionEnroll(Request $r): Response { $sid = Adm::enroll((int)$r->params['id'], (int)$r->input('section_id', 0)); return Response::ok(['message' => 'سُجّل الطالب', 'redirect' => url("school/students/$sid")]); }
    public function admissionDoc(Request $r): Response
    {
        $f = !empty($_FILES['file']) && ($_FILES['file']['error'] ?? 4) !== UPLOAD_ERR_NO_FILE ? $_FILES['file'] : throw new HttpException(422, 'اختر ملفًا', ['file' => 'مطلوب']);
        Adm::addDocument((int)$r->params['id'], (string)$r->input('title', ''), $f); return Response::ok(['message' => 'أُرفقت الوثيقة']);
    }
    public function admissionDocDelete(Request $r): Response { Adm::removeDocument((int)$r->params['doc']); return Response::ok(['message' => 'حُذفت الوثيقة']); }

    // ---- الإنذار المبكر ----
    public function risk(Request $r): Response
    {
        $st = (string)$r->query('status', 'open'); return $this->page('school/ext/risk', 'الإنذار المبكر', ['groups' => Rk::flags($st, $r->query('section') ? (int)$r->query('section') : null), 'status' => $st, 'sections' => $this->sections(), 'can_act' => Auth::can('counseling.manage')]);
    }
    public function riskScan(Request $r): Response { $x = Rk::scan(); return Response::ok(['message' => "فُحص {$x['students']} طالبًا: {$x['flags']} تنبيه جديد، أُزيل {$x['cleared']}"]); }
    public function riskSettings(Request $r): Response { return $this->page('school/ext/risk_settings', 'مؤشرات الإنذار', ['ind' => Rk::indicators()]); }
    public function riskSet(Request $r): Response { Rk::setIndicator((string)$r->params['code'], (string)$r->input('threshold', ''), (bool)$r->input('is_active')); return Response::ok(['message' => 'تم الحفظ']); }
    public function riskReview(Request $r): Response { Rk::review((int)$r->params['id'], (string)$r->input('note', '')); return Response::ok(['message' => 'تمت المراجعة']); }
    public function riskDismiss(Request $r): Response { Rk::dismiss((int)$r->params['id'], (string)$r->input('note', '')); return Response::ok(['message' => 'استُبعد التنبيه']); }
    public function riskCase(Request $r): Response { $c = Rk::openCase((int)$r->params['id'], $r->input('note') !== null ? (string)$r->input('note') : null); return Response::ok(['message' => 'فُتحت حالة إرشاد', 'redirect' => url("school/counseling/$c")]); }

    // ---- الإرشاد ----
    public function counseling(Request $r): Response { return $this->page('school/ext/counseling', 'الإرشاد الطلابي', ['cases' => Co::cases((string)$r->query('status', ''), (string)$r->query('q', '')), 'status' => (string)$r->query('status', '')]); }
    public function counselingShow(Request $r): Response { return $this->page('school/ext/counseling_case', 'حالة إرشاد', ['c' => Co::detail((int)$r->params['id'])]); }
    public function counselingCreate(Request $r): Response
    {
        $id = Co::create($this->studentByNo((string)$r->input('student_no', '')), (string)$r->input('kind', ''), (string)$r->input('title', ''), (string)$r->input('note', ''));
        return Response::ok(['message' => 'فُتحت الحالة', 'redirect' => url("school/counseling/$id")]);
    }
    public function counselingNote(Request $r): Response { Co::addNote((int)$r->params['id'], (string)$r->input('note', '')); return Response::ok(['message' => 'أُضيفت الملاحظة']); }
    public function counselingStatus(Request $r): Response { Co::setStatus((int)$r->params['id'], (string)$r->input('status', '')); return Response::ok(['message' => 'تم التحديث']); }

    // ---- الرواتب ----
    public function payroll(Request $r): Response { return $this->page('school/ext/payroll', 'كشوف الرواتب', ['runs' => Pay::runs()]); }
    public function contracts(Request $r): Response { return $this->page('school/ext/contracts', 'العقود', ['rows' => Pay::contracts(), 'staff' => DB::all("SELECT id, full_name FROM staff WHERE school_id = ? AND deleted_at IS NULL AND status <> 'terminated' ORDER BY full_name", [Tenant::require()])]); }
    public function contractSave(Request $r): Response
    {
        Pay::saveContract((int)$r->input('staff_id', 0), (string)$r->input('kind', ''), (string)$r->input('starts_on', ''), $r->input('ends_on') ? (string)$r->input('ends_on') : null, (string)$r->input('base_salary', ''), (string)$r->input('currency', 'YER'), $r->input('notes') ? (string)$r->input('notes') : null);
        return Response::ok(['message' => 'حُفظ العقد']);
    }
    public function contractEnd(Request $r): Response { Pay::endContract((int)$r->params['id'], (string)$r->input('ends_on', '')); return Response::ok(['message' => 'أُنهي العقد']); }
    public function runCreate(Request $r): Response { $id = Pay::createRun((string)$r->input('period', '')); return Response::ok(['message' => 'أُنشئ الكشف', 'redirect' => url("school/payroll/$id")]); }
    public function runShow(Request $r): Response { return $this->page('school/ext/payroll_run', 'كشف رواتب', ['run' => Pay::detail((int)$r->params['id'])]); }
    public function runAdjust(Request $r): Response { Pay::adjust((int)$r->params['item'], (string)$r->input('allowances', ''), (string)$r->input('deductions', ''), $r->input('note') ? (string)$r->input('note') : null); return Response::ok(['message' => 'تم التعديل']); }
    public function runAction(Request $r): Response
    {
        $id = (int)$r->params['id']; $a = (string)$r->params['action'];
        match ($a) { 'approve' => Pay::approve($id), 'paid' => Pay::markPaid($id, (string)$r->input('paid_on', date('Y-m-d'))), 'reopen' => Pay::reopen($id, (string)$r->input('reason', '')), default => throw new HttpException(404, 'إجراء غير معروف') };
        return Response::ok(['message' => 'تم']);
    }
    public function runDelete(Request $r): Response { Pay::deleteRun((int)$r->params['id']); return Response::ok(['message' => 'حُذف الكشف', 'redirect' => url('school/payroll')]); }
    public function payslip(Request $r): Response { return Response::html(View::render('school/ext/payslip', ['title' => 'قسيمة راتب', 'i' => Pay::payslip((int)$r->params['item'])], 'print')); }
    public function runExport(Request $r): Response
    {
        $d = Pay::detail((int)$r->params['id']); $rows = [['الموظف', 'الوظيفة', 'الأساسي', 'البدلات', 'الخصومات', 'الصافي', 'العملة']];
        foreach ($d['items'] as $i) { $rows[] = [$i['full_name'], $i['job_title'] ?? '', $i['base'], $i['allowances'], $i['deductions'], $i['net'], $i['currency']]; }
        $bin = \App\Services\XlsxWriter::build($rows, 'رواتب ' . $d['period']); return new Response($bin, 200, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'Content-Disposition' => 'attachment; filename="payroll-' . $d['period'] . '.xlsx"']);
    }

    // ---- المرافق ----
    public function facilities(Request $r): Response
    {
        $day = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$r->query('day', '')) ? (string)$r->query('day') : date('Y-m-d');
        return $this->page('school/ext/facilities', 'المرافق وحجزها', ['rows' => Fa::facilities(), 'bookings' => Fa::bookings(null, $day, $day), 'day' => $day, 'manage' => Auth::can('facilities.manage'), 'me' => Auth::id()]);
    }
    public function facilitySave(Request $r): Response { Fa::save($r->params['id'] ?? null ? (int)$r->params['id'] : null, (string)$r->input('name', ''), $r->input('kind') ? (string)$r->input('kind') : null, (string)$r->input('capacity', ''), (string)$r->input('status', 'available')); return Response::ok(['message' => 'تم الحفظ']); }
    public function facilityDelete(Request $r): Response { Fa::delete((int)$r->params['id']); return Response::ok(['message' => 'حُذف المرفق']); }
    public function book(Request $r): Response { Fa::book((int)$r->input('facility_id', 0), (string)$r->input('starts_at', ''), (string)$r->input('ends_at', ''), $r->input('purpose') ? (string)$r->input('purpose') : null); return Response::ok(['message' => 'تم الحجز']); }
    public function bookCancel(Request $r): Response { Fa::cancel((int)$r->params['id']); return Response::ok(['message' => 'أُلغي الحجز']); }

    // ---- الشهادات ----
    public function credentials(Request $r): Response { return $this->page('school/ext/credentials', 'الشهادات الرقمية', ['rows' => Cr::list()]); }
    public function credentialIssue(Request $r): Response { Cr::issue($this->studentByNo((string)$r->input('student_no', '')), (string)$r->input('kind', ''), (string)$r->input('title', ''), $r->input('notes') ? (string)$r->input('notes') : null); return Response::ok(['message' => 'أُصدرت الشهادة']); }
    public function credentialRevoke(Request $r): Response { Cr::revoke((int)$r->params['id'], (string)$r->input('reason', '')); return Response::ok(['message' => 'سُحبت الشهادة']); }
    public function credentialPrint(Request $r): Response { return Response::html(View::render('school/ext/certificate', ['title' => 'شهادة', 'c' => Cr::find((int)$r->params['id'])], 'print')); }
        public function verify(Request $r): Response
    {
        $code = (string)($r->params['code'] ?? $r->query('code', '')); return Response::html(View::render('verify', ['title' => 'التحقق من شهادة', 'result' => $code === '' ? null : Cr::verify($code), 'code' => $code, 'checked' => $code !== ''], 'bare'));
    }

    // ---- التحليلات ----
    public function analytics(Request $r): Response
    {
        $y = AcademicService::activeYear(); $terms = $y ? DB::all('SELECT id, name FROM terms WHERE school_id = ? AND academic_year_id = ? ORDER BY term_no', [Tenant::require(), $y['id']]) : [];
        return $this->page('school/ext/analytics', 'التحليلات المتقدمة', ['terms' => $terms, 'overview' => An::overview(), 'movement' => An::movement()], ['analytics.js']);
    }
    public function analyticsData(Request $r): Response
    {
        $t = (int)$r->query('term', 0); if (!$t) { return Response::ok(['trends' => An::sectionTrends()]); }
        return Response::ok(['subjects' => An::subjects($t), 'corr' => An::attendanceVsGrades($t), 'trends' => An::sectionTrends()]);
    }
}
