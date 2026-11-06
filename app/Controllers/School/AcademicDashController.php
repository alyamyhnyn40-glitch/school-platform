<?php
declare(strict_types=1);
namespace App\Controllers\School;

use App\Core\{Request, Response, View};
use App\Services\{AcademicService as A, AcademicReportService as R, AuditService, XlsxWriter};

final class AcademicDashController
{
    private function params(Request $r): array
    {
        $years = A::years(); $yid = (int)($r->query('year') ?: (A::activeYear()['id'] ?? ($years[0]['id'] ?? 0)));
        $term = $r->query('term', ''); $termId = $term === '' || $term === 'year' ? null : (int)$term;
        if ($term === '' && $yid) { $ts = A::terms($yid); $cur = null; foreach ($ts as $t) { if ($t['starts_on'] <= date('Y-m-d')) { $cur = (int)$t['id']; } } $termId = $cur ?? ($ts ? (int)$ts[0]['id'] : null); }
        return [$years, $yid, $termId, $r->query('grade') ? (int)$r->query('grade') : null];
    }
    public function index(Request $r): Response
    {
        [$years, $yid, $termId, $gid] = $this->params($r);
        $d = $yid ? R::build($yid, $termId, $gid) : null;
        return Response::html(View::render('school/academic/dashboard', ['title' => 'لوحة الأداء الأكاديمي', 'years' => $years, 'yearId' => $yid, 'termId' => $termId, 'gradeId' => $gid, 'terms' => $yid ? A::terms($yid) : [], 'grades' => A::grades(), 'd' => $d, 'scripts' => ['study-plan.js']]));
    }
    public function export(Request $r): Response
    {
        [, $yid, $termId, $gid] = $this->params($r); $kind = in_array($k = (string)$r->query('kind', 'students'), ['students', 'top', 'sections', 'subjects', 'failing'], true) ? $k : 'students';
        $d = R::build($yid, $termId, $gid, 50); AuditService::log('report.academic_export', 'academic_year', $yid, null, ['kind' => $kind, 'term' => $termId]);
        $names = ['students' => 'النتائج', 'top' => 'الأوائل', 'sections' => 'الشعب', 'subjects' => 'المواد', 'failing' => 'الراسبون'];
        return new Response(XlsxWriter::build(R::exportRows($d, $kind), $names[$kind]), 200, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'Content-Disposition' => 'attachment; filename="academic-' . $kind . '.xlsx"']);
    }
}
