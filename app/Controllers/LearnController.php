<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\{Request, Response, View};
use App\Services\{LiveService, LmsService as L, QuizService as Q};

/** واجهة الطالب: موادي، الدروس، الواجبات، الاختبارات، الحصص المباشرة. */
final class LearnController
{
    public function home(Request $r): Response { return Response::html(View::render('learn/home', ['title' => 'موادي', 'subjects' => L::mySubjects(), 'liveNow' => LiveService::liveNow()])); }
    public function subject(Request $r): Response
    {
        $id = (int)$r->params['ss'];
        return Response::html(View::render('learn/subject', ['title' => L::ss($id)['subject_name'], 'ss' => L::learner($id)['ss'], 'lessons' => L::lessons($id, false), 'assignments' => L::assignments($id, false), 'quizzes' => Q::quizzes($id, false), 'live' => LiveService::sessions($id, false)]));
    }
    public function lesson(Request $r): Response { $l = L::lesson((int)$r->params['id']); return Response::html(View::render('learn/lesson', ['title' => $l['title'], 'l' => $l, 'scripts' => ['../vendor/katex/katex.min.js', '../vendor/katex/auto-render.min.js', 'mathfield.js'], 'styles' => ['../vendor/katex/katex.min.css', 'mathfield.css']])); }
    public function assignment(Request $r): Response { $a = L::assignmentForLearner((int)$r->params['id']); return Response::html(View::render('learn/assignment', ['title' => $a['title'], 'a' => $a, 'scripts' => ['../vendor/katex/katex.min.js', '../vendor/katex/auto-render.min.js', 'mathfield.js'], 'styles' => ['../vendor/katex/katex.min.css', 'mathfield.css']])); }
    public function submit(Request $r): Response
    {
        L::submit((int)$r->params['id'], $r->input('body'), !empty($_FILES['file']) && ($_FILES['file']['error'] ?? 4) !== UPLOAD_ERR_NO_FILE ? $_FILES['file'] : null);
        return Response::ok(['message' => 'تم تسليم الواجب', 'redirect' => url('learn/assignments/' . (int)$r->params['id'])]);
    }
    public function quiz(Request $r): Response { $z = Q::quizForLearner((int)$r->params['id']); return Response::html(View::render('learn/quiz', ['title' => $z['title'], 'z' => $z])); }
    public function start(Request $r): Response { $a = Q::start((int)$r->params['id']); return Response::ok(['redirect' => url("learn/attempts/$a")]); }
    public function attempt(Request $r): Response
    {
        $a = Q::attemptForLearner((int)$r->params['id']);
        if ($a['status'] !== 'in_progress') { return Response::redirect('/learn/attempts/' . (int)$a['id'] . '/result'); }
        return Response::html(View::render('learn/take', ['title' => $a['title'], 'a' => $a, 'scripts' => ['../vendor/katex/katex.min.js', '../vendor/katex/auto-render.min.js', 'mathfield.js', 'quiz-take.js'], 'styles' => ['../vendor/katex/katex.min.css', 'mathfield.css']]));
    }
    public function answer(Request $r): Response { Q::saveAnswer((int)$r->params['id'], (int)$r->input('question_id'), $r->input('answer')); return Response::ok(['saved' => true]); }
    public function finish(Request $r): Response { Q::submit((int)$r->params['id']); return Response::ok(['message' => 'تم تسليم الاختبار', 'redirect' => url('learn/attempts/' . (int)$r->params['id'] . '/result')]); }
    public function result(Request $r): Response { $a = Q::resultFor((int)$r->params['id']); return Response::html(View::render('learn/result', ['title' => 'نتيجة الاختبار', 'a' => $a, 'scripts' => ['../vendor/katex/katex.min.js', '../vendor/katex/auto-render.min.js', 'mathfield.js'], 'styles' => ['../vendor/katex/katex.min.css', 'mathfield.css']])); }
    public function live(Request $r): Response { $l = LiveService::find((int)$r->params['id']); return Response::html(View::render('learn/live', ['title' => $l['title'], 'l' => $l])); }
    public function joinLive(Request $r): Response { return Response::ok(['url' => LiveService::join((int)$r->params['id'])]); }
}
