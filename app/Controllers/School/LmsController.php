<?php
declare(strict_types=1);
namespace App\Controllers\School;

use App\Core\{HttpException, Request, Response, View};
use App\Services\{AcademicService, LiveService, LmsService as L, QuizService as Q};

final class LmsController
{
    private function ok(string $m, array $x = []): Response { return Response::ok(['message' => $m] + $x); }
    private function lines(mixed $v): array { return array_values(array_filter(array_map('trim', preg_split('/\R/u', (string)$v) ?: []), fn($x) => $x !== '')); }

    // ---------- صفحات ----------
    public function index(Request $r): Response
    {
        $list = L::manageable(); $ss = (int)$r->query('ss'); $d = ['title' => 'المحتوى التعليمي', 'list' => $list, 'ssId' => $ss, 'ss' => null];
        if ($ss) { $d = array_merge($d, ['ss' => L::ss($ss), 'lessons' => L::lessons($ss, true), 'assignments' => L::assignments($ss, true), 'quizzes' => Q::quizzes($ss, true), 'live' => LiveService::sessions($ss, true), 'providers' => LiveService::PROVIDERS]); }
        $d['scripts'] = ['../vendor/katex/katex.min.js', '../vendor/katex/auto-render.min.js', 'mathfield.js', 'live-form.js']; $d['styles'] = ['../vendor/katex/katex.min.css', 'mathfield.css'];
        return Response::html(View::render('school/lms/index', $d));
    }
    public function lesson(Request $r): Response
    {
        $l = L::lesson((int)$r->params['id']); return Response::html(View::render('school/lms/lesson', ['title' => $l['title'], 'l' => $l, 'scripts' => ['../vendor/katex/katex.min.js', '../vendor/katex/auto-render.min.js', 'mathfield.js', 'lms.js'], 'styles' => ['../vendor/katex/katex.min.css', 'mathfield.css']]));
    }
    public function assignment(Request $r): Response
    {
        $a = L::assignmentForTeacher((int)$r->params['id']); return Response::html(View::render('school/lms/assignment', ['title' => $a['title'], 'a' => $a, 'scripts' => ['../vendor/katex/katex.min.js', '../vendor/katex/auto-render.min.js', 'mathfield.js'], 'styles' => ['../vendor/katex/katex.min.css', 'mathfield.css']]));
    }
    public function bank(Request $r): Response
    {
        $subjects = array_values(array_unique(array_map(fn($x) => $x['subject_id'] . '|' . $x['subject'], array_map(fn($m) => ['subject_id' => L::ss((int)$m['id'])['subject_id'], 'subject' => $m['subject']], L::manageable()))));
        $opts = []; foreach ($subjects as $s) { [$id, $n] = explode('|', $s, 2); $opts[(int)$id] = $n; }
        $sub = (int)($r->query('subject') ?: (array_key_first($opts) ?? 0));
        return Response::html(View::render('school/lms/bank', ['title' => 'بنك الأسئلة', 'subjects' => $opts, 'subject' => $sub, 'questions' => $sub ? Q::bank($sub) : [], 'kinds' => Q::KINDS, 'scripts' => ['../vendor/katex/katex.min.js', '../vendor/katex/auto-render.min.js', 'mathfield.js', 'image-preview.js', 'question-form.js', 'lms.js'], 'styles' => ['../vendor/katex/katex.min.css', 'mathfield.css']]));
    }
    public function quiz(Request $r): Response
    {
        $z = Q::quizForTeacher((int)$r->params['id']);
        return Response::html(View::render('school/lms/quiz', ['title' => $z['title'], 'z' => $z, 'bank' => Q::bank((int)$z['ss']['subject_id']), 'attempts' => Q::attempts((int)$z['id']), 'kinds' => Q::KINDS, 'scripts' => ['../vendor/katex/katex.min.js', '../vendor/katex/auto-render.min.js', 'mathfield.js'], 'styles' => ['../vendor/katex/katex.min.css', 'mathfield.css']]));
    }
    public function attempt(Request $r): Response
    {
        $a = Q::attemptForTeacher((int)$r->params['id']); return Response::html(View::render('school/lms/attempt', ['title' => 'تصحيح محاولة', 'a' => $a, 'scripts' => ['../vendor/katex/katex.min.js', '../vendor/katex/auto-render.min.js', 'mathfield.js'], 'styles' => ['../vendor/katex/katex.min.css', 'mathfield.css']]));
    }

    // ---------- الدروس والمواد ----------
    public function saveLesson(Request $r): Response
    {
        $ss = isset($r->params['ss']) ? (int)$r->params['ss'] : (int)$r->input('section_subject_id'); $id = isset($r->params['id']) ? (int)$r->params['id'] : null;
        if ($id) { $ss = (int)L::lesson($id)['section_subject_id']; }
        $id = L::saveLesson($ss, $id, (string)$r->input('title', ''), $r->input('body'), (int)$r->input('sort_order', 0), $r->input('is_published') && $r->input('is_published') !== '0');
        return $this->ok('تم حفظ الدرس', ['redirect' => url("school/lms/lessons/$id")]);
    }
    public function deleteLesson(Request $r): Response { $ss = L::lesson((int)$r->params['id'])['section_subject_id']; L::deleteLesson((int)$r->params['id']); return $this->ok('تم الحذف', ['redirect' => url("school/lms?ss=$ss")]); }
    public function addMaterial(Request $r): Response
    {
        L::addMaterial((int)$r->params['id'], (string)$r->input('kind', ''), (string)$r->input('title', ''), $r->input('url'), !empty($_FILES['file']) && ($_FILES['file']['error'] ?? 4) !== UPLOAD_ERR_NO_FILE ? $_FILES['file'] : null);
        return $this->ok('تمت إضافة المادة');
    }
    public function deleteMaterial(Request $r): Response { L::deleteMaterial((int)$r->params['id']); return $this->ok('تم الحذف'); }

    // ---------- الواجبات ----------
    public function createAssignment(Request $r): Response
    {
        $id = L::createAssignment((int)$r->params['ss'], (string)$r->input('title', ''), $r->input('instructions'), (string)$r->input('due_at', ''), $r->input('max_score') !== null ? (string)$r->input('max_score') : null, $r->input('allow_late') && $r->input('allow_late') !== '0');
        return $this->ok('تم إنشاء الواجب', ['redirect' => url("school/lms/assignments/$id")]);
    }
    public function deleteAssignment(Request $r): Response { $a = L::assignmentForTeacher((int)$r->params['id']); L::deleteAssignment((int)$r->params['id']); return $this->ok('تم الحذف', ['redirect' => url('school/lms?ss=' . (int)$a['section_subject_id'])]); }
    public function grade(Request $r): Response { L::grade((int)$r->params['id'], (string)$r->input('score', ''), $r->input('feedback')); return $this->ok('تم حفظ الدرجة'); }

    // ---------- بنك الأسئلة والاختبارات ----------
    /** عنصر $_FILES واحد لحقل مفرد (name) أو لموضع $i من حقل مصفوفة (name[]) — null إن لم يُرفع شيء. */
    private function upload(string $field, ?int $i = null): ?array
    {
        $f = $_FILES[$field] ?? null; if (!$f) { return null; }
        if ($i === null) { return ($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE ? null : $f; }
        if (!is_array($f['error'] ?? null) || ($f['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) { return null; }
        return ['name' => $f['name'][$i], 'type' => $f['type'][$i], 'tmp_name' => $f['tmp_name'][$i], 'error' => $f['error'][$i], 'size' => $f['size'][$i]];
    }
    public function saveQuestion(Request $r): Response
    {
        $kind = (string)$r->input('kind', ''); $optIn = $r->input('options'); $options = is_array($optIn) ? array_values($optIn) : $this->lines($optIn);   // النموذج الجديد: صفوف options[] بترتيبها
        $idx = $r->input('answer_idx'); $ans = match ($kind) { 'mcq' => $idx !== null && $idx !== '' ? (int)$idx : (int)$r->input('answer', 1) - 1, 'short' => $this->lines($r->input('accepted')), 'true_false' => $r->input('tf'), default => null };
        $media = ['question' => $this->upload('question_image'), 'remove_question' => (bool)$r->input('remove_question_image') && $r->input('remove_question_image') !== '0', 'options' => []];
        foreach ($options as $i => $_) { $media['options'][$i] = ['file' => $this->upload('option_image', $i)]; }
        $id = Q::saveQuestion(isset($r->params['id']) ? (int)$r->params['id'] : null, (int)$r->input('subject_id'), $kind, (string)$r->input('body', ''), $options, $ans, (string)$r->input('points', '1'), $media);
        return $this->ok('تم حفظ السؤال', ['id' => $id]);
    }
    public function deleteQuestion(Request $r): Response { Q::deleteQuestion((int)$r->params['id']); return $this->ok('تم الحذف'); }
    public function createQuiz(Request $r): Response
    {
        $id = Q::createQuiz((int)$r->params['ss'], $r->all()); return $this->ok('تم إنشاء الاختبار', ['redirect' => url("school/lms/quizzes/$id")]);
    }
    public function addQuestions(Request $r): Response { $n = Q::addQuestions((int)$r->params['id'], (array)$r->input('question_ids', [])); return $this->ok("أُضيف $n سؤال"); }
    public function removeQuestion(Request $r): Response { Q::removeQuestion((int)$r->params['id'], (int)$r->params['qid']); return $this->ok('أُزيل السؤال'); }
    public function publish(Request $r): Response { Q::publish((int)$r->params['id'], (bool)$r->input('publish') && $r->input('publish') !== '0'); return $this->ok('تم'); }
    public function deleteQuiz(Request $r): Response { $z = Q::quizForTeacher((int)$r->params['id']); Q::deleteQuiz((int)$r->params['id']); return $this->ok('تم الحذف', ['redirect' => url('school/lms?ss=' . (int)$z['section_subject_id'])]); }
    public function gradeAnswer(Request $r): Response { Q::gradeAnswer((int)$r->params['id'], (int)$r->input('question_id'), (string)$r->input('score', '')); return $this->ok('تم حفظ الدرجة'); }

    // ---------- الحصص المباشرة ----------
    public function createLive(Request $r): Response
    {
        LiveService::quick((int)$r->params['ss'], (string)$r->input('title', ''), (string)$r->input('starts_at', ''), (int)($r->input('duration') ?: 60), (string)($r->input('provider') ?: 'jitsi'), $r->input('join_url'), $r->input('description'));
        return $this->ok('تمت جدولة الحصة وأُبلغ الطلاب');
    }
    /** «ابدأ حصة الآن»: يُنشئ/يعيد الحصة الجارية ويعيد الرابط لفتحه فورًا. */
    public function startLive(Request $r): Response { $x = LiveService::startNow((int)$r->params['ss'], $r->input('title') ? (string)$r->input('title') : null, (int)($r->input('duration') ?: 60)); return Response::ok(['url' => $x['url'], 'id' => $x['id'], 'message' => $x['reused'] ? 'الحصة جارية بالفعل — جارٍ فتحها' : 'بدأت الحصة وأُبلغ الطلاب']); }
    public function endLive(Request $r): Response { LiveService::end((int)$r->params['id']); return $this->ok('أُنهيت الحصة'); }
    public function deleteLive(Request $r): Response { LiveService::delete((int)$r->params['id']); return $this->ok('تم الحذف'); }
    public function joinLive(Request $r): Response { return Response::ok(['url' => LiveService::join((int)$r->params['id'])]); }
}
