<?php
declare(strict_types=1);
namespace App\Controllers\School;

use App\Core\{HttpException, Request, Response, View};
use App\Services\{ImportService as I, XlsxWriter};

final class ImportController
{
    public function index(Request $r): Response
    {
        $perm = ['students' => 'students.manage', 'staff' => 'staff.manage', 'guardians' => 'guardians.manage', 'grades' => 'academic.manage', 'subjects' => 'academic.manage', 'sections' => 'academic.manage', 'scores' => 'grades.edit'];
        $kinds = array_filter(I::KINDS, fn($k) => \App\Core\Auth::can($perm[$k]), ARRAY_FILTER_USE_KEY);
        return Response::html(View::render('school/import/index', ['title' => 'الاستيراد من Excel', 'jobs' => I::jobs(), 'kinds' => $kinds, 'allKinds' => I::KINDS, 'hints' => I::HINTS, 'scripts' => ['import.js']]));
    }
    public function upload(Request $r): Response
    {
        if (empty($_FILES['file'])) { throw new HttpException(422, 'اختر ملفًا'); }
        $id = I::upload((string)$r->input('kind'), $_FILES['file']);
        return Response::ok(['id' => $id, 'redirect' => url("school/import/$id")]);
    }
    public function show(Request $r): Response
    {
        $id = (int)$r->params['id'];
        return Response::html(View::render('school/import/preview', ['title' => 'معاينة الاستيراد', 'job' => I::job($id), 'rows' => I::rows($id, (bool)$r->query('errors'), 200), 'kinds' => I::KINDS, 'onlyErrors' => (bool)$r->query('errors')]));
    }
    public function commit(Request $r): Response
    {
        $res = I::commit((int)$r->params['id'], (bool)$r->input('accounts', true));
        return Response::ok(['message' => "تم استيراد {$res['imported']} صف" . ($res['errors'] ? " — {$res['errors']} صف بأخطاء" : ''), 'redirect' => url('school/import/' . (int)$r->params['id'] . ($res['errors'] ? '?errors=1' : ''))]);
    }
    public function discard(Request $r): Response { I::discard((int)$r->params['id']); return Response::ok(['message' => 'تم الحذف', 'redirect' => url('school/import')]); }

    public function template(Request $r): Response
    {
        [$head, $ex, $lists] = I::template((string)$r->params['kind']);
        return new Response(XlsxWriter::build([$head, $ex], 'قالب', $lists), 200, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'Content-Disposition' => 'attachment; filename="template-' . preg_replace('/[^a-z]/', '', (string)$r->params['kind']) . '.xlsx"']);
    }
    /** تقرير الأخطاء: كل صف فيه خطأ مع سبب الرفض. */
    public function errors(Request $r): Response
    {
        $id = (int)$r->params['id']; $job = I::job($id);
        $rows = [['رقم الصف في الملف', 'الأخطاء', 'البيانات']];
        foreach (I::rows($id, true, 5000) as $x) { $rows[] = [(int)$x['row_no'], implode(' | ', $x['errors']), implode(' ; ', array_filter(array_map(fn($v) => is_scalar($v) ? (string)$v : '', $x['data'])))]; }
        return AttendanceController::xlsxResponse("import-$id-errors.xlsx", $rows, 'الأخطاء');
    }
}
