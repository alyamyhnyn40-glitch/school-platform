<?php
declare(strict_types=1);
namespace App\Controllers\School;

use App\Core\{HttpException, Request, Response, View};
use App\Services\{AcademicService as A, SettingsService, StudyPlanService as P, AuditService};

final class StudyPlanController
{
    public function index(Request $r): Response
    {
        $years = A::years(); $yid = (int)($r->query('year') ?: (A::activeYear()['id'] ?? ($years[0]['id'] ?? 0)));
        $grades = A::grades(); $gid = (int)($r->query('grade') ?: 0);
        return Response::html(View::render('school/academic/study_plan', ['title' => 'الخطة الدراسية', 'years' => $years, 'yearId' => $yid, 'grades' => $grades, 'gradeId' => $gid,
            'terms' => $yid ? A::terms($yid) : [], 'subjects' => A::subjects(), 'summary' => $yid ? P::summary($yid) : [], 'plan' => $yid && $gid ? P::plan($yid, $gid) : [],
            'weighted' => SettingsService::get('grades.credit_weighted', '0') === '1', 'scripts' => ['study-plan.js']]));
    }
    public function save(Request $r): Response
    {
        $items = $r->input('items'); $n = P::save((int)$r->input('year_id'), (int)$r->input('grade_id'), is_array($items) ? $items : []);
        $msg = "حُفظت الخطة ($n مادة).";
        if (!empty($r->input('apply')) && $r->input('apply') !== '0') { $a = P::apply((int)$r->input('year_id'), (int)$r->input('grade_id')); $msg .= ' ' . self::applyMsg($a); }
        return Response::ok(['message' => $msg]);
    }
    public function apply(Request $r): Response { return Response::ok(['message' => self::applyMsg(P::apply((int)$r->input('year_id'), $r->input('grade_id') ? (int)$r->input('grade_id') : null))]); }
    public function copy(Request $r): Response { $n = P::copy((int)$r->input('from_year'), (int)$r->input('year_id')); return Response::ok(['message' => $n ? "نُسخت $n مادة من السنة السابقة. راجعها ثم اضغط «تطبيق على الشعب»." : 'لم يُنسخ شيء: الخطة في السنة المختارة موجودة مسبقًا أو السنة المصدر بلا خطة.']); }
    public function settings(Request $r): Response
    {
        \App\Core\Auth::authorize('grades.config'); $w = !empty($r->input('credit_weighted')) && $r->input('credit_weighted') !== '0';
        SettingsService::set('grades.credit_weighted', $w ? '1' : '0'); AuditService::log('grades.credit_weighted', 'school', null, null, ['on' => $w]);
        return Response::ok(['message' => $w ? 'سيُحتسب المعدل موزونًا بالساعات المعتمدة.' : 'سيُحتسب المعدل متوسطًا حسابيًا للمواد.']);
    }
    public function electives(Request $r): Response { return Response::ok(P::electiveStudents((int)$r->params['id'])); }
    public function saveElectives(Request $r): Response { $ids = $r->input('enrollment_ids'); $n = P::setElectiveStudents((int)$r->params['id'], is_array($ids) ? $ids : []); return Response::ok(['message' => "سُجّل $n طالب في المادة الاختيارية."]); }
    private static function applyMsg(array $a): string
    {
        if (!$a['sections']) { return 'لا توجد شعب لهذا الصف في السنة المختارة (أو لا خطة لها).'; }
        $m = "طُبّقت الخطة على {$a['sections']} شعبة: أُضيفت {$a['added']} مادة، وحُدّثت {$a['updated']}" . ($a['removed'] ? "، وأُزيلت {$a['removed']}" : '') . '.';
        if ($a['kept']) { $m .= ' لم تُحذف (حفاظًا على الدرجات): ' . implode('، ', array_slice($a['kept'], 0, 5)) . (count($a['kept']) > 5 ? '…' : ''); }
        return $m;
    }
}
