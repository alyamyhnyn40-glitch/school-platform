<?php
declare(strict_types=1);
namespace App\Controllers\School;

use App\Core\{HttpException, Request, Response, Validator, View};
use App\Services\{AcademicService as A, PromotionService, StaffService};

final class AcademicController
{
    private function ok(string $msg, array $extra = []): Response { return Response::ok(['message' => $msg] + $extra); }
    private function validate(Request $r, array $rules): void
    {
        $v = Validator::make($r->all(), $rules);
        if ($v->fails()) { throw new HttpException(422, 'تحقق من الحقول المطلوبة', $v->firstErrors()); }
    }

    // ---------- صفحات ----------
    public function years(Request $r): Response
    {
        $years = A::years();
        foreach ($years as &$y) { $y['terms'] = A::terms((int)$y['id']); } unset($y);
        return Response::html(View::render('school/academic/years', ['title' => 'السنوات والفصول', 'years' => $years, 'scripts' => ['datatable.js', 'school-years.js']]));
    }
    public function structure(Request $r): Response
    {
        return Response::html(View::render('school/academic/structure', ['title' => 'المراحل والصفوف والمواد', 'stages' => A::stages(), 'grades' => A::grades(), 'subjects' => A::subjects($r->query('grade') ? (int)$r->query('grade') : null), 'gradeFilter' => $r->query('grade') ? (int)$r->query('grade') : 0]));
    }
    public function sections(Request $r): Response
    {
        $yid = $r->query('year') ? (int)$r->query('year') : (A::activeYear()['id'] ?? null);
        return Response::html(View::render('school/academic/sections', ['title' => 'الشعب', 'years' => A::years(), 'yearId' => $yid, 'sections' => A::sections($yid ? (int)$yid : null), 'grades' => A::grades(), 'teachers' => StaffService::teachers()]));
    }
    public function section(Request $r): Response
    {
        $id = (int)$r->params['id'];
        return Response::html(View::render('school/academic/section', ['title' => 'الشعبة', 'section' => A::section($id), 'electives' => \App\Services\StudyPlanService::electiveMap($id), 'subjects' => A::subjectsForSection($id), 'items' => A::sectionSubjects($id), 'teachers' => StaffService::teachers(),
            'students' => \App\Core\DB::all("SELECT s.id, e.id AS enrollment_id, s.full_name, s.student_no FROM student_enrollments e JOIN students s ON s.id = e.student_id WHERE e.section_id = ? AND e.school_id = ? AND e.status = 'active' AND s.deleted_at IS NULL ORDER BY s.full_name", [$id, \App\Core\Tenant::require()])]));
    }

    public function newYear(Request $r): Response { return Response::html(View::render('school/academic/new_year', ['title' => 'سنة دراسية جديدة', 's' => \App\Services\YearWizardService::suggest(), 'years' => A::years(), 'scripts' => ['wizard.js']])); }
    public function wizard(Request $r): Response { $x = \App\Services\YearWizardService::create($r->all()); return Response::ok(['message' => $x['summary'], 'redirect' => url('school/years')]); }

    // ---------- API ----------
    public function createYear(Request $r): Response
    {
        $this->validate($r, ['name' => 'required|max:60', 'starts_on' => 'required|date', 'ends_on' => 'required|date']);
        A::createYear((string)$r->input('name'), (string)$r->input('starts_on'), (string)$r->input('ends_on'));
        return $this->ok('تمت إضافة السنة');
    }
    public function activateYear(Request $r): Response { A::activateYear((int)$r->params['id']); return $this->ok('تم تفعيل السنة'); }
    public function closeYear(Request $r): Response { A::closeYear((int)$r->params['id']); return $this->ok('تم إغلاق السنة'); }
    public function cloneStructure(Request $r): Response
    {
        $n = A::cloneStructure((int)$r->input('from'), (int)$r->params['id'], (bool)$r->input('with_teachers', true));
        return $this->ok("تم نسخ $n شعبة");
    }
    public function createTerm(Request $r): Response
    {
        $this->validate($r, ['name' => 'required|max:60', 'term_no' => 'required|numeric', 'starts_on' => 'required|date', 'ends_on' => 'required|date']);
        A::createTerm((int)$r->params['id'], (string)$r->input('name'), (int)$r->input('term_no'), (string)$r->input('starts_on'), (string)$r->input('ends_on'), !empty($r->input('is_final')) && $r->input('is_final') !== '0');
        return $this->ok('تمت إضافة الفصل');
    }
    public function deleteTerm(Request $r): Response { A::deleteSimple('term', (int)$r->params['id']); return $this->ok('تم الحذف'); }

    public function promotePreview(Request $r): Response
    {
        $pv = PromotionService::preview((int)$r->input('from'), (int)$r->input('to'), (array)$r->input('decisions', []));
        return Response::ok($pv);
    }
    public function promote(Request $r): Response
    {
        $c = PromotionService::promote((int)$r->input('from'), (int)$r->input('to'), (array)$r->input('decisions', []));
        return $this->ok('تم الترفيع: ' . $c['promote'] . ' ترفيع، ' . $c['repeat'] . ' إعادة، ' . $c['graduate'] . ' تخريج، ' . $c['withdraw'] . ' انسحاب', ['redirect' => url('school/years')]);
    }

    public function saveStage(Request $r): Response
    {
        $this->validate($r, ['name' => 'required|max:100']);
        A::saveStage(isset($r->params['id']) ? (int)$r->params['id'] : null, (string)$r->input('name'), (int)$r->input('sort_order', 0));
        return $this->ok('تم الحفظ');
    }
    public function saveGrade(Request $r): Response
    {
        $this->validate($r, ['name' => 'required|max:100', 'stage_id' => 'required|numeric']);
        A::saveGrade(isset($r->params['id']) ? (int)$r->params['id'] : null, (int)$r->input('stage_id'), (string)$r->input('name'), (int)$r->input('sort_order', 0), $r->input('next_grade_level_id') ? (int)$r->input('next_grade_level_id') : null);
        return $this->ok('تم الحفظ');
    }
    public function saveSubject(Request $r): Response
    {
        $this->validate($r, ['name' => 'required|max:100', 'code' => 'max:30']);
        A::saveSubject(isset($r->params['id']) ? (int)$r->params['id'] : null, (string)$r->input('name'), $r->input('code'), (array)$r->input('grade_ids', []));
        return $this->ok('تم الحفظ');
    }
    public function deleteSimple(Request $r): Response { A::deleteSimple((string)$r->params['kind'], (int)$r->params['id']); return $this->ok('تم الحذف'); }

    public function saveSection(Request $r): Response
    {
        $this->validate($r, ['name' => 'required|max:60', 'grade_level_id' => 'required|numeric', 'academic_year_id' => 'required|numeric', 'capacity' => 'numeric', 'gender' => 'in:mixed,boys,girls']);
        A::saveSection(isset($r->params['id']) ? (int)$r->params['id'] : null, $r->all());
        return $this->ok('تم الحفظ');
    }
    public function deleteSection(Request $r): Response { A::deleteSection((int)$r->params['id']); return $this->ok('تم الحذف', ['redirect' => url('school/sections')]); }
    public function addSectionSubject(Request $r): Response
    {
        $this->validate($r, ['subject_id' => 'required|numeric', 'weekly_periods' => 'numeric', 'max_score' => 'numeric']);
        A::addSectionSubject((int)$r->params['id'], (int)$r->input('subject_id'), (int)$r->input('weekly_periods', 1), $r->input('max_score') !== null && $r->input('max_score') !== '' ? (float)$r->input('max_score') : null);
        return $this->ok('تمت إضافة المادة');
    }
    public function removeSectionSubject(Request $r): Response { A::removeSectionSubject((int)$r->params['id']); return $this->ok('تمت الإزالة'); }
    public function assignTeacher(Request $r): Response
    {
        $this->validate($r, ['staff_id' => 'required|numeric']);
        A::assignTeacher((int)$r->params['id'], (int)$r->input('staff_id'));
        return $this->ok('تم إسناد المعلم');
    }
    public function unassignTeacher(Request $r): Response { A::unassignTeacher((int)$r->params['id'], (int)$r->params['staff']); return $this->ok('تمت إزالة الإسناد'); }
}
