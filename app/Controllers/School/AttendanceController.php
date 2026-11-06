<?php
declare(strict_types=1);
namespace App\Controllers\School;

use App\Core\{Auth, HttpException, Request, Response, View};
use App\Services\{AcademicService, AttendanceService as A, Scope, XlsxWriter};

final class AttendanceController
{
    private function sectionsForUser(): array
    {
        $y = AcademicService::activeYear(); if (!$y) { return []; }
        $ids = Scope::sectionIds();
        return array_values(array_filter(AcademicService::sections((int)$y['id']), fn($s) => $ids === null || in_array((int)$s['id'], $ids, true)));
    }
    public function take(Request $r): Response
    {
        return Response::html(View::render('school/attendance/take', ['title' => 'تسجيل الحضور', 'sections' => $this->sectionsForUser(), 'year' => AcademicService::activeYear(), 'scripts' => ['offline.js', 'attendance.js']]));
    }
    public function roster(Request $r): Response
    {
        return Response::ok(A::roster((int)$r->query('section_id'), (string)$r->query('date', date('Y-m-d')), (int)$r->query('period', 0)));
    }
    public function save(Request $r): Response
    {
        $c = A::save((int)$r->input('section_id'), (string)$r->input('date'), (int)$r->input('period', 0), (array)$r->input('records', []));
        return Response::ok($c + ['message' => 'تم حفظ الحضور']);
    }
    public function sync(Request $r): Response { return Response::ok(A::sync((array)$r->input('ops', []))); }

    public function report(Request $r): Response
    {
        [$from, $to, $sec] = $this->range($r);
        $rows = A::summary($from, $to, $sec);
        $min = max(1, (int)$r->query('min', 3));
        return Response::html(View::render('school/attendance/report', ['title' => 'تقرير الحضور', 'rows' => $rows, 'absentees' => A::frequentAbsentees($from, $to, $min, (float)$r->query('pct', 20), $sec), 'trend' => A::dailyTrend($from, $to, $sec),
            'from' => $from, 'to' => $to, 'sectionId' => $sec, 'min' => $min, 'sections' => $this->sectionsForUser(), 'scripts' => ['../vendor/chartjs/chart.umd.js', 'attendance-report.js']]));
    }
    public function export(Request $r): Response
    {
        Auth::authorize('reports.export');
        [$from, $to, $sec] = $this->range($r);
        $rows = [['الطالب', 'الصف', 'الشعبة', 'حاضر', 'غائب', 'متأخر', 'بعذر', 'الإجمالي', 'نسبة الحضور %']];
        foreach (A::summary($from, $to, $sec) as $x) { $rows[] = [$x['full_name'], $x['grade_name'], $x['section_name'], $x['present'], $x['absent'], $x['late'], $x['excused'], $x['total'], $x['attendance_pct']]; }
        return $this->xlsx("attendance-$from-$to.xlsx", $rows, 'الحضور');
    }
    private function range(Request $r): array
    {
        $from = (string)$r->query('from', date('Y-m-d', strtotime('-30 day'))); $to = (string)$r->query('to', date('Y-m-d'));
        foreach ([$from, $to] as $d) { if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) { throw new HttpException(422, 'تاريخ غير صالح'); } }
        return [$from, $to, $r->query('section_id') ? (int)$r->query('section_id') : null];
    }
    public static function xlsxResponse(string $name, array $rows, string $sheet): Response
    {
        return new Response(XlsxWriter::build($rows, $sheet), 200, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'Content-Disposition' => 'attachment; filename="' . $name . '"', 'Cache-Control' => 'no-store']);
    }
    private function xlsx(string $name, array $rows, string $sheet): Response { return self::xlsxResponse($name, $rows, $sheet); }
}
