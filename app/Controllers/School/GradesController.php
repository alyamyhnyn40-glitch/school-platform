<?php
declare(strict_types=1);
namespace App\Controllers\School;

use App\Core\{Auth, DB, HttpException, Request, Response, Tenant, Validator, View};
use App\Services\{AcademicService, GradingService as G, ReportCardService, ResultsService};

final class GradesController
{
    private function validate(Request $r, array $rules): void { $v = Validator::make($r->all(), $rules); if ($v->fails()) { throw new HttpException(422, 'تحقق من الحقول', $v->firstErrors()); } }
    private function terms(): array { $y = AcademicService::activeYear(); return $y ? AcademicService::terms((int)$y['id']) : []; }

    // ---------- إدخال الدرجات ----------
    public function index(Request $r): Response
    {
        $terms = $this->terms(); $termId = (int)($r->query('term') ?: ($terms[0]['id'] ?? 0)); $ssId = (int)$r->query('ss');
        $subjects = G::mySubjects(); $assessments = ($termId && $ssId) ? G::listAssessments($ssId, $termId) : [];
        $scheme = $ssId ? G::schemeForSection((int)(DB::value('SELECT section_id FROM section_subjects WHERE id = ? AND school_id = ?', [$ssId, Tenant::require()]) ?: 0)) : null;
        return Response::html(View::render('school/grades/index', ['title' => 'إدخال الدرجات', 'terms' => $terms, 'termId' => $termId, 'subjects' => $subjects, 'ssId' => $ssId, 'assessments' => $assessments, 'scheme' => $scheme]));
    }
    public function grid(Request $r): Response
    {
        $g = G::grid((int)$r->params['id']);
        return Response::html(View::render('school/grades/grid', ['title' => 'جدول الدرجات'] + $g + ['scripts' => ['offline.js', 'grades.js']]));
    }
    public function createAssessment(Request $r): Response
    {
        $this->validate($r, ['term_id' => 'required|numeric', 'section_subject_id' => 'required|numeric', 'type_id' => 'required|numeric', 'title' => 'required|max:150', 'max_score' => 'required|numeric', 'held_on' => 'date']);
        $id = G::createAssessment((int)$r->input('term_id'), (int)$r->input('section_subject_id'), (int)$r->input('type_id'), (string)$r->input('title'), (float)$r->input('max_score'), $r->input('held_on') ?: null);
        return Response::ok(['id' => $id, 'message' => 'تم إنشاء التقييم', 'redirect' => url("school/grades/$id")]);
    }
    public function deleteAssessment(Request $r): Response { G::deleteAssessment((int)$r->params['id']); return Response::ok(['message' => 'تم الحذف']); }
    public function saveScores(Request $r): Response
    {
        $c = G::saveScores((int)$r->params['id'], (array)$r->input('scores', []));
        return Response::ok($c + ['message' => 'تم الحفظ']);
    }
    public function sync(Request $r): Response { return Response::ok(G::sync((array)$r->input('ops', []))); }
    public function approve(Request $r): Response { G::approve((int)$r->params['id']); return Response::ok(['message' => 'تم اعتماد الدرجات']); }
    public function reopen(Request $r): Response { G::reopen((int)$r->params['id'], (string)$r->input('reason', '')); return Response::ok(['message' => 'أُعيد فتح التقييم']); }
    public function lockTerm(Request $r): Response { G::lockTerm((int)$r->params['id']); return Response::ok(['message' => 'تم قفل درجات الفصل']); }
    public function unlockTerm(Request $r): Response { G::unlockTerm((int)$r->params['id'], (string)$r->input('reason', '')); return Response::ok(['message' => 'تم فتح القفل']); }

    // ---------- تهيئة النظام ----------
    public function config(Request $r): Response
    {
        return Response::html(View::render('school/grades/config', ['title' => 'نظام الدرجات', 'schemes' => G::schemes(), 'stages' => AcademicService::stages(), 'scripts' => ['grading-config.js']]));
    }
    public function saveScheme(Request $r): Response
    {
        $id = G::saveScheme(isset($r->params['id']) ? (int)$r->params['id'] : null, $r->all());
        return Response::ok(['id' => $id, 'message' => 'تم حفظ نظام الدرجات']);
    }

    // ---------- النتائج والشهادات ----------
    public function results(Request $r): Response
    {
        $terms = $this->terms(); $termId = (int)($r->query('term') ?: ($terms[0]['id'] ?? 0)); $secId = (int)$r->query('section');
        $y = AcademicService::activeYear(); $sections = $y ? AcademicService::sections((int)$y['id']) : [];
        $ids = \App\Services\Scope::sectionIds(); $sections = array_values(array_filter($sections, fn($s) => $ids === null || in_array((int)$s['id'], $ids, true)));
        $final = (bool)$r->query('final'); $yearId = (int)(AcademicService::activeYear()['id'] ?? 0);
        if ($final && $secId) {
            $res = ResultsService::yearResults($yearId, $secId);
            $res['term'] = ['name' => 'النتيجة النهائية للعام', 'grades_locked' => (bool)!array_filter($res['terms'], fn($t) => !$t['grades_locked']), 'academic_year_id' => $yearId]; $res['unassessed'] = [];
        } else { $res = ($termId && $secId) ? ResultsService::termResults($termId, $secId) : null; }
        $cards = ($termId && $secId) ? ReportCardService::listForSection($termId, $secId) : [];
        $cmp = $termId && count($sections) > 1 ? ResultsService::compareSections($termId) : [];
        return Response::html(View::render('school/grades/results', ['title' => 'النتائج والشهادات', 'terms' => $terms, 'termId' => $termId, 'sections' => $sections, 'secId' => $secId, 'res' => $res, 'cards' => $cards, 'cmp' => $cmp, 'final' => $final, 'yearId' => $yearId]));
    }
    public function issue(Request $r): Response
    {
        $res = ReportCardService::issueForSection((int)$r->input('term_id'), (int)$r->input('section_id'), (bool)$r->input('allow_incomplete'));
        return Response::ok(['message' => "أُصدرت {$res['issued']} شهادة" . ($res['incomplete'] ? " ({$res['incomplete']} غير مكتملة)" : '')]);
    }
    public function issueFinal(Request $r): Response
    {
        $res = ReportCardService::issueFinalForSection((int)$r->input('year_id'), (int)$r->input('section_id'), (bool)$r->input('allow_incomplete'));
        return Response::ok(['message' => "أُصدرت {$res['issued']} شهادة نهائية" . ($res['incomplete'] ? " ({$res['incomplete']} غير مكتملة)" : '')]);
    }
    public function exportResults(Request $r): Response
    {
        Auth::authorize('reports.export');
        $res = ResultsService::termResults((int)$r->query('term'), (int)$r->query('section'));
        $head = ['الطالب', 'رقم القيد']; foreach ($res['subjects'] as $s) { $head[] = $s['name']; } array_push($head, 'المعدل %', 'التقدير', 'الترتيب', 'النتيجة');
        $rows = [$head];
        foreach ($res['students'] as $s) { $row = [$s['full_name'], $s['student_no']]; foreach ($res['subjects'] as $sub) { $x = $s['subjects'][$sub['id']]; $row[] = $x['has_assessments'] ? $x['total'] : ''; } array_push($row, $s['average'], $s['letter'], $s['rank'], $s['result']); $rows[] = $row; }
        return AttendanceController::xlsxResponse('results-' . (int)$r->query('section') . '.xlsx', $rows, 'النتائج');
    }
    public function printCard(Request $r): Response
    {
        $c = ReportCardService::find((int)$r->params['id']);
        return Response::html(View::render('school/grades/card', ['title' => 'شهادة', 'cards' => [$c['data']]], 'print'));
    }
    public function printAll(Request $r): Response
    {
        $cards = [];
        foreach (ReportCardService::listForSection((int)$r->query('term'), (int)$r->query('section')) as $row) { $cards[] = ReportCardService::find((int)$row['id'])['data']; }
        if (!$cards) { throw new HttpException(404, 'لا توجد شهادات مُصدرة لهذه الشعبة'); }
        return Response::html(View::render('school/grades/card', ['title' => 'شهادات الشعبة', 'cards' => $cards], 'print'));
    }
}
