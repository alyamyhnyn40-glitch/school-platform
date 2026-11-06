<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\{Auth, DB, HttpException, Tenant};

/** بنك الأسئلة والاختبارات الإلكترونية: ترتيب عشوائي، وقت محدد، تصحيح آلي للموضوعي، وضوابط نزاهة من جهة الخادم. */
final class QuizService
{
    public const GRACE_SECONDS = 15;
    public const KINDS = ['mcq' => 'اختيار من متعدد', 'true_false' => 'صح/خطأ', 'short' => 'إجابة قصيرة', 'essay' => 'مقالي'];

    // ---------- بنك الأسئلة ----------
    private static function assertSubject(int $subjectId): void
    {
        Auth::authorize('lms.manage'); if (LmsService::isAdminLevel()) { return; }
        $st = Scope::staffId();
        if (!$st || !DB::value('SELECT 1 FROM teacher_assignments ta JOIN section_subjects ss ON ss.id = ta.section_subject_id WHERE ta.staff_id = ? AND ss.subject_id = ? AND ta.school_id = ?', [$st, $subjectId, Tenant::require()])) { throw new HttpException(403, 'لا تدرّس هذه المادة'); }
    }
    public static function bank(int $subjectId): array
    {
        self::assertSubject($subjectId);
        $rows = DB::all('SELECT * FROM question_bank WHERE school_id = ? AND subject_id = ? AND deleted_at IS NULL ORDER BY id DESC', [Tenant::require(), $subjectId]);
        foreach ($rows as &$r) { $r['options'] = $r['options'] ? json_decode($r['options'], true) : null; $r['answer'] = $r['answer'] !== null ? json_decode($r['answer'], true) : null; $r['option_images'] = $r['option_images'] ? json_decode($r['option_images'], true) : []; } return $rows;
    }
    /**
     * @param array $media ['question' => ?file, 'remove_question' => bool, 'options' => [rowIndex => ['file' => ?file, 'keep' => ?int, 'remove' => bool]]]
     *   صور اختيارية للسؤال ولكل خيار. الخيار قد يكون نصًّا أو صورة أو كليهما.
     */
    public static function saveQuestion(?int $id, int $subjectId, string $kind, string $body, array $options, mixed $answer, string $points, array $media = []): int
    {
        self::assertSubject($subjectId); $sid = Tenant::require();
        DB::value('SELECT 1 FROM subjects WHERE id = ? AND school_id = ?', [$subjectId, $sid]) ?? throw new HttpException(422, 'مادة غير صالحة');
        if (!isset(self::KINDS[$kind])) { throw new HttpException(422, 'نوع سؤال غير صالح'); }
        $hasQImg = !empty($media['question']) || (!empty($media['keep_question']) && empty($media['remove_question']));
        if (mb_strlen(trim($body)) < 3 && !$hasQImg) { throw new HttpException(422, 'نص السؤال مطلوب (أو أرفق صورة)', ['body' => 'مطلوب']); }
        if (!is_numeric($points) || (float)$points <= 0 || (float)$points > 100) { throw new HttpException(422, 'الدرجة من 0 إلى 100', ['points' => 'غير صالحة']); }
        $old = null;
        if ($id) {
            $old = DB::one('SELECT * FROM question_bank WHERE id = ? AND school_id = ? AND deleted_at IS NULL', [$id, $sid]) ?? throw new HttpException(404, 'السؤال غير موجود');
            if (DB::value('SELECT 1 FROM quiz_questions qq JOIN quiz_attempts a ON a.quiz_id = qq.quiz_id WHERE qq.question_id = ? LIMIT 1', [$id])) { throw new HttpException(409, 'السؤال مستخدم في اختبار بدأ طلاب حله؛ أنشئ سؤالًا جديدًا'); }
        }
        $oldOptImgs = $old && $old['option_images'] ? (json_decode($old['option_images'], true) ?: []) : [];
        $opts = null; $ans = null; $rows = [];   // rows: [text, newFile|null, keepId|null]
        if ($kind === 'mcq') {
            $map = [];
            foreach (array_values($options) as $i => $o) {
                $text = trim((string)$o); $m = $media['options'][$i] ?? []; $file = $m['file'] ?? null; $keep = empty($m['remove']) && !empty($m['keep']) ? (int)$m['keep'] : null;
                if ($keep !== null && !in_array($keep, array_map('intval', array_filter($oldOptImgs)), true)) { $keep = null; }   // لا يُقبل معرّف ملف لا يخص هذا السؤال
                if ($text === '' && !$file && $keep === null) { continue; }
                $map[$i] = count($rows); $rows[] = [$text, $file, $keep];
            }
            if (count($rows) < 2 || count($rows) > 8) { throw new HttpException(422, 'أدخل من 2 إلى 8 خيارات (نص أو صورة لكل خيار)', ['options' => 'غير صالحة']); }
            if (!is_numeric($answer) || (int)$answer < 0 || !isset($map[(int)$answer])) { throw new HttpException(422, 'حدّد الخيار الصحيح', ['answer' => 'مطلوب']); }
            $opts = array_column($rows, 0); $ans = $map[(int)$answer];
        } elseif ($kind === 'true_false') {
            if (!in_array($answer, [true, false, 'true', 'false', '1', '0', 1, 0], true)) { throw new HttpException(422, 'حدّد صح أو خطأ', ['answer' => 'مطلوب']); }
            $ans = in_array($answer, [true, 'true', '1', 1], true);
        } elseif ($kind === 'short') {
            $ans = array_values(array_filter(array_map(fn($a) => trim((string)$a), is_array($answer) ? $answer : preg_split('/[\n،;]+/u', (string)$answer)), fn($a) => $a !== ''));
        }
        $stored = []; $paths = [];   // ملفات جديدة (لحذفها عند الفشل: الفيزيائية لأن صفوف files تُلغى بالـ rollback)
        try {
            $qid = DB::transaction(function () use ($id, $sid, $subjectId, $kind, $body, $opts, $ans, $points, $rows, $media, $old, $oldOptImgs, &$stored, &$paths) {
                $oj = $opts !== null ? json_encode($opts, JSON_UNESCAPED_UNICODE) : null; $aj = $ans !== null ? json_encode($ans, JSON_UNESCAPED_UNICODE) : null;
                if ($id) { DB::query('UPDATE question_bank SET kind=?, body=?, options=?, answer=?, points=? WHERE id=?', [$kind, trim($body), $oj, $aj, round((float)$points, 2), $id]); $qid = $id; }
                else { DB::query('INSERT INTO question_bank (school_id, subject_id, kind, body, options, answer, points, created_by) VALUES (?,?,?,?,?,?,?,?)', [$sid, $subjectId, $kind, trim($body), $oj, $aj, round((float)$points, 2), Auth::id()]); $qid = DB::lastId(); }
                $keepQ = $old ? (int)$old['image_file_id'] : 0; $imgQ = $keepQ ?: null; $drop = [];
                if (!empty($media['question'])) { $imgQ = FileService::storeImage($media['question'], 'question', $qid); $stored[] = $imgQ; $paths[] = FileService::path(FileService::find($imgQ)); if ($keepQ) { $drop[] = $keepQ; } }
                elseif ($keepQ && !empty($media['remove_question'])) { $imgQ = null; $drop[] = $keepQ; }
                $imgs = [];
                foreach ($rows as [$text, $file, $keep]) { if ($file) { $f = FileService::storeImage($file, 'question', $qid); $stored[] = $f; $paths[] = FileService::path(FileService::find($f)); $imgs[] = $f; } else { $imgs[] = $keep; } }
                foreach ($oldOptImgs as $oid) { if ($oid && !in_array((int)$oid, array_map('intval', array_filter($imgs)), true)) { $drop[] = (int)$oid; } }
                DB::query('UPDATE question_bank SET image_file_id = ?, option_images = ? WHERE id = ?', [$imgQ, $kind === 'mcq' && array_filter($imgs) ? json_encode($imgs) : null, $qid]);
                foreach (array_unique($drop) as $d) { FileService::remove($d); }
                AuditService::log('question.save', 'question', $qid, null, ['kind' => $kind, 'images' => ($imgQ ? 1 : 0) + count(array_filter($imgs))]);
                return $qid;
            });
        } catch (\Throwable $e) { foreach ($paths as $pth) { @unlink($pth); } throw $e; }
        return $qid;
    }
    public static function deleteQuestion(int $id): void
    {
        $q = DB::one('SELECT * FROM question_bank WHERE id = ? AND school_id = ? AND deleted_at IS NULL', [$id, Tenant::require()]) ?? throw new HttpException(404, 'السؤال غير موجود'); self::assertSubject((int)$q['subject_id']);
        if (DB::value('SELECT 1 FROM quiz_questions qq JOIN quiz_attempts a ON a.quiz_id = qq.quiz_id WHERE qq.question_id = ? LIMIT 1', [$id])) { throw new HttpException(409, 'السؤال مستخدم في اختبار بدأ طلاب حله'); }
        DB::query('DELETE qq FROM quiz_questions qq JOIN quizzes z ON z.id = qq.quiz_id WHERE qq.question_id = ? AND z.school_id = ?', [$id, Tenant::require()]);
        DB::query('UPDATE question_bank SET deleted_at = NOW() WHERE id = ?', [$id]);
        foreach (array_merge([$q['image_file_id']], $q['option_images'] ? (json_decode($q['option_images'], true) ?: []) : []) as $fid) { if ($fid) { FileService::remove((int)$fid); } }
    }

    // ---------- الاختبارات (المعلم) ----------
    private static function quizRow(int $id): array { return DB::one('SELECT * FROM quizzes WHERE id = ? AND school_id = ? AND deleted_at IS NULL', [$id, Tenant::require()]) ?? throw new HttpException(404, 'الاختبار غير موجود'); }
    public static function quizzes(int $ssId, bool $manage): array
    {
        $sid = Tenant::require();
        if ($manage) { LmsService::assertManage($ssId); return DB::all('SELECT z.*, (SELECT COUNT(*) FROM quiz_questions q WHERE q.quiz_id = z.id) AS questions, (SELECT COUNT(*) FROM quiz_attempts a WHERE a.quiz_id = z.id) AS attempts FROM quizzes z WHERE z.school_id = ? AND z.section_subject_id = ? AND z.deleted_at IS NULL ORDER BY z.id DESC', [$sid, $ssId]); }
        $l = LmsService::learner($ssId);
        return DB::all('SELECT z.*, (SELECT COUNT(*) FROM quiz_attempts a WHERE a.quiz_id = z.id AND a.enrollment_id = ?) AS my_attempts FROM quizzes z WHERE z.school_id = ? AND z.section_subject_id = ? AND z.deleted_at IS NULL AND z.is_published = 1 ORDER BY z.id DESC', [$l['enrollment_id'], $sid, $ssId]);
    }
    public static function createQuiz(int $ssId, array $d): int
    {
        $ss = LmsService::assertManage($ssId);
        if (mb_strlen(trim((string)($d['title'] ?? ''))) < 2) { throw new HttpException(422, 'عنوان الاختبار مطلوب', ['title' => 'مطلوب']); }
        $o = ($d['opens_at'] ?? '') !== '' ? date('Y-m-d H:i:s', strtotime(str_replace('T', ' ', $d['opens_at']))) : null; $c = ($d['closes_at'] ?? '') !== '' ? date('Y-m-d H:i:s', strtotime(str_replace('T', ' ', $d['closes_at']))) : null;
        if (($d['opens_at'] ?? '') !== '' && !strtotime(str_replace('T', ' ', $d['opens_at'])) || ($d['closes_at'] ?? '') !== '' && !strtotime(str_replace('T', ' ', $d['closes_at']))) { throw new HttpException(422, 'تاريخ غير صالح'); }
        if ($o && $c && $c <= $o) { throw new HttpException(422, 'وقت الإغلاق يجب أن يلي الفتح', ['closes_at' => 'غير صالح']); }
        $dur = ($d['duration_min'] ?? '') === '' ? null : (int)$d['duration_min']; if ($dur !== null && ($dur < 1 || $dur > 600)) { throw new HttpException(422, 'المدة من 1 إلى 600 دقيقة', ['duration_min' => 'غير صالحة']); }
        $att = max(1, min(10, (int)($d['max_attempts'] ?? 1))); $show = in_array($d['show_answers'] ?? 'after_submit', ['after_submit', 'after_close', 'never'], true) ? ($d['show_answers'] ?? 'after_submit') : 'after_submit';
        DB::query('INSERT INTO quizzes (school_id, section_subject_id, title, description, opens_at, closes_at, duration_min, shuffle_questions, max_attempts, show_answers, created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?)',
            [Tenant::require(), $ssId, trim($d['title']), ($d['description'] ?? '') ?: null, $o, $c, $dur, (int)!empty($d['shuffle_questions']), $att, $show, Auth::id()]);
        $id = DB::lastId(); AuditService::log('quiz.create', 'quiz', $id, null, ['title' => $d['title']]); return $id;
    }
    public static function quizForTeacher(int $id): array
    {
        $z = self::quizRow($id); $ss = LmsService::assertManage((int)$z['section_subject_id']);
        $z['ss'] = $ss; $z['questions'] = DB::all('SELECT q.*, qq.sort_order, COALESCE(qq.points, q.points) AS pts FROM quiz_questions qq JOIN question_bank q ON q.id = qq.question_id WHERE qq.quiz_id = ? ORDER BY qq.sort_order, qq.question_id', [$id]);
        foreach ($z['questions'] as &$q) { $q['options'] = $q['options'] ? json_decode($q['options'], true) : null; $q['answer'] = $q['answer'] !== null ? json_decode($q['answer'], true) : null; $q['option_images'] = $q['option_images'] ? json_decode($q['option_images'], true) : []; }
        $z['total_points'] = round(array_sum(array_map(fn($q) => (float)$q['pts'], $z['questions'])), 2);
        $z['has_attempts'] = (bool)DB::value('SELECT 1 FROM quiz_attempts WHERE quiz_id = ? LIMIT 1', [$id]); return $z;
    }
    private static function editable(array $z): void
    {
        if (DB::value('SELECT 1 FROM quiz_attempts WHERE quiz_id = ? LIMIT 1', [$z['id']])) { throw new HttpException(409, 'بدأ طلاب حل الاختبار؛ لا يمكن تعديل أسئلته'); }
    }
    public static function addQuestions(int $quizId, array $questionIds): int
    {
        $z = self::quizRow($quizId); $ss = LmsService::assertManage((int)$z['section_subject_id']); self::editable($z); $n = 0;
        foreach (array_unique(array_map('intval', $questionIds)) as $qid) {
            if (!DB::value('SELECT 1 FROM question_bank WHERE id = ? AND school_id = ? AND subject_id = ? AND deleted_at IS NULL', [$qid, Tenant::require(), $ss['subject_id']])) { throw new HttpException(422, 'سؤال غير صالح لهذه المادة'); }
            $next = (int)DB::value('SELECT COALESCE(MAX(sort_order),0)+1 FROM quiz_questions WHERE quiz_id = ?', [$quizId]);
            $n += DB::query('INSERT IGNORE INTO quiz_questions (quiz_id, question_id, sort_order) VALUES (?,?,?)', [$quizId, $qid, $next])->rowCount();
        }
        return $n;
    }
    public static function removeQuestion(int $quizId, int $qid): void
    {
        $z = self::quizRow($quizId); LmsService::assertManage((int)$z['section_subject_id']); self::editable($z); DB::query('DELETE FROM quiz_questions WHERE quiz_id = ? AND question_id = ?', [$quizId, $qid]);
    }
    public static function publish(int $quizId, bool $publish): void
    {
        $z = self::quizRow($quizId); $ss = LmsService::assertManage((int)$z['section_subject_id']);
        if ($publish && !DB::value('SELECT 1 FROM quiz_questions WHERE quiz_id = ?', [$quizId])) { throw new HttpException(422, 'أضف سؤالًا واحدًا على الأقل قبل النشر'); }
        if (!$publish) { self::editable($z); }
        DB::query('UPDATE quizzes SET is_published = ? WHERE id = ?', [(int)$publish, $quizId]);
        if ($publish && !$z['is_published']) { NotificationService::notify(NotificationService::sectionUsers((int)$ss['section_id'], true, false), 'اختبار جديد: ' . $z['title'], $ss['subject_name'] . ($z['opens_at'] ? ' — يفتح ' . $z['opens_at'] : ''), url('learn/quizzes/' . $quizId)); }
        AuditService::log('quiz.publish', 'quiz', $quizId, null, ['published' => $publish]);
    }
    public static function deleteQuiz(int $quizId): void
    {
        $z = self::quizRow($quizId); LmsService::assertManage((int)$z['section_subject_id']); self::editable($z); DB::query('UPDATE quizzes SET deleted_at = NOW() WHERE id = ?', [$quizId]);
    }

    // ---------- حل الطالب ----------
    private static function mine(int $attemptId): array
    {
        $a = DB::one('SELECT a.*, z.section_subject_id, z.title, z.duration_min, z.closes_at, z.opens_at, z.show_answers, z.max_attempts FROM quiz_attempts a JOIN quizzes z ON z.id = a.quiz_id WHERE a.id = ? AND a.school_id = ?', [$attemptId, Tenant::require()]) ?? throw new HttpException(404, 'المحاولة غير موجودة');
        $l = LmsService::learner((int)$a['section_subject_id']);
        if ((int)$a['enrollment_id'] !== (int)$l['enrollment_id']) { throw new HttpException(403, 'هذه ليست محاولتك'); }
        return $a;
    }
    public static function quizForLearner(int $id): array
    {
        $z = self::quizRow($id); $l = LmsService::learner((int)$z['section_subject_id']); if (!$z['is_published']) { throw new HttpException(404, 'الاختبار غير موجود'); }
        $z['ss'] = $l['ss']; $z['questions'] = (int)DB::value('SELECT COUNT(*) FROM quiz_questions WHERE quiz_id = ?', [$id]);
        $z['points'] = round((float)DB::value('SELECT COALESCE(SUM(COALESCE(qq.points, q.points)),0) FROM quiz_questions qq JOIN question_bank q ON q.id = qq.question_id WHERE qq.quiz_id = ?', [$id]), 2);
        $z['attempts'] = DB::all('SELECT id, started_at, submitted_at, score, status FROM quiz_attempts WHERE quiz_id = ? AND enrollment_id = ? ORDER BY id', [$id, $l['enrollment_id']]);
        foreach ($z['attempts'] as $a) { if ($a['status'] === 'in_progress') { self::finalizeIfExpired((int)$a['id']); } }
        $z['attempts'] = DB::all('SELECT id, started_at, submitted_at, score, status FROM quiz_attempts WHERE quiz_id = ? AND enrollment_id = ? ORDER BY id', [$id, $l['enrollment_id']]);
        $now = time(); $z['state'] = !$z['opens_at'] || strtotime($z['opens_at']) <= $now ? (!$z['closes_at'] || strtotime($z['closes_at']) >= $now ? 'open' : 'closed') : 'not_yet';
        $z['can_start'] = $z['state'] === 'open' && count($z['attempts']) < (int)$z['max_attempts'] && $z['questions'] > 0; $z['resume'] = null;
        foreach ($z['attempts'] as $a) { if ($a['status'] === 'in_progress') { $z['resume'] = (int)$a['id']; } }
        return $z;
    }
    /** يبدأ محاولة (أو يستأنف الجارية). */
    public static function start(int $quizId): int
    {
        $z = self::quizRow($quizId); $l = LmsService::learner((int)$z['section_subject_id']); $sid = Tenant::require();
        if (!$z['is_published']) { throw new HttpException(404, 'الاختبار غير موجود'); }
        return DB::transaction(function () use ($z, $l, $sid, $quizId) {
            DB::query('SELECT id FROM quiz_attempts WHERE quiz_id = ? AND enrollment_id = ? FOR UPDATE', [$quizId, $l['enrollment_id']]);
            foreach (DB::all("SELECT id FROM quiz_attempts WHERE quiz_id = ? AND enrollment_id = ? AND status = 'in_progress'", [$quizId, $l['enrollment_id']]) as $a) { self::finalizeIfExpired((int)$a['id']); }
            $open = DB::value("SELECT id FROM quiz_attempts WHERE quiz_id = ? AND enrollment_id = ? AND status = 'in_progress'", [$quizId, $l['enrollment_id']]); if ($open) { return (int)$open; }
            $now = time();
            if ($z['opens_at'] && strtotime($z['opens_at']) > $now) { throw new HttpException(422, 'لم يُفتح الاختبار بعد'); }
            if ($z['closes_at'] && strtotime($z['closes_at']) < $now) { throw new HttpException(422, 'انتهى وقت الاختبار'); }
            if ((int)DB::value('SELECT COUNT(*) FROM quiz_attempts WHERE quiz_id = ? AND enrollment_id = ?', [$quizId, $l['enrollment_id']]) >= (int)$z['max_attempts']) { throw new HttpException(422, 'استنفدت عدد المحاولات المسموحة'); }
            $qids = array_map('intval', array_column(DB::all('SELECT question_id FROM quiz_questions WHERE quiz_id = ? ORDER BY sort_order, question_id', [$quizId]), 'question_id'));
            if (!$qids) { throw new HttpException(422, 'الاختبار بلا أسئلة'); }
            if ($z['shuffle_questions']) { for ($i = count($qids) - 1; $i > 0; $i--) { $j = random_int(0, $i); [$qids[$i], $qids[$j]] = [$qids[$j], $qids[$i]]; } }   // خلط آمن (Fisher–Yates بـ random_int)
            $deadline = null; if ($z['duration_min']) { $deadline = $now + (int)$z['duration_min'] * 60; } if ($z['closes_at']) { $deadline = $deadline ? min($deadline, strtotime($z['closes_at'])) : strtotime($z['closes_at']); }
            DB::query("INSERT INTO quiz_attempts (school_id, quiz_id, enrollment_id, started_at, question_order, status, deadline_at) VALUES (?,?,?,?,?,'in_progress',?)", [$sid, $quizId, $l['enrollment_id'], date('Y-m-d H:i:s', $now), json_encode($qids), $deadline ? date('Y-m-d H:i:s', $deadline) : null]);
            return DB::lastId();
        });
    }
    private static function expired(array $a): bool { return $a['deadline_at'] && time() > strtotime($a['deadline_at']) + self::GRACE_SECONDS; }
    public static function finalizeIfExpired(int $attemptId): void
    {
        $a = DB::one('SELECT * FROM quiz_attempts WHERE id = ? AND school_id = ?', [$attemptId, Tenant::require()]);
        if ($a && $a['status'] === 'in_progress' && self::expired($a)) { self::finalize($attemptId); }
    }
    /** صفحة الحل: الأسئلة بلا إجابات صحيحة. */
    public static function attemptForLearner(int $attemptId): array
    {
        $a = self::mine($attemptId); self::finalizeIfExpired($attemptId); $a = self::mine($attemptId);
        $a['questions'] = []; $a['remaining'] = $a['deadline_at'] ? max(0, strtotime($a['deadline_at']) - time()) : null;
        if ($a['status'] === 'in_progress') {
            $order = json_decode($a['question_order'], true) ?: [];
            $q = array_column(DB::all('SELECT q.id, q.kind, q.body, q.options, q.image_file_id, q.option_images, COALESCE(qq.points, q.points) AS points FROM quiz_questions qq JOIN question_bank q ON q.id = qq.question_id WHERE qq.quiz_id = ?', [$a['quiz_id']]), null, 'id');
            $ans = array_column(DB::all('SELECT question_id, answer FROM quiz_answers WHERE attempt_id = ?', [$attemptId]), 'answer', 'question_id');
            foreach ($order as $qid) { if (!isset($q[$qid])) { continue; } $r = $q[$qid]; $r['options'] = $r['options'] ? json_decode($r['options'], true) : null; $r['option_images'] = $r['option_images'] ? json_decode($r['option_images'], true) : []; $r['saved'] = isset($ans[$qid]) ? json_decode($ans[$qid], true) : null; $a['questions'][] = $r; }
        }
        return $a;
    }
    public static function saveAnswer(int $attemptId, int $qid, mixed $answer): void
    {
        $a = self::mine($attemptId);
        if ($a['status'] !== 'in_progress') { throw new HttpException(422, 'انتهت المحاولة'); }
        if (self::expired($a)) { self::finalize($attemptId); throw new HttpException(422, 'انتهى وقت الاختبار'); }
        if (!in_array($qid, json_decode($a['question_order'], true) ?: [], true)) { throw new HttpException(422, 'سؤال ليس ضمن اختبارك'); }
        $q = DB::one('SELECT kind, options FROM question_bank WHERE id = ?', [$qid]);
        $v = match ($q['kind']) {
            'mcq' => (is_numeric($answer) && (int)$answer >= 0 && (int)$answer < count(json_decode($q['options'], true))) ? (int)$answer : ($answer === null || $answer === '' ? null : throw new HttpException(422, 'خيار غير صالح')),
            'true_false' => in_array($answer, [true, false, 'true', 'false', '1', '0', 1, 0], true) ? in_array($answer, [true, 'true', '1', 1], true) : ($answer === null || $answer === '' ? null : throw new HttpException(422, 'إجابة غير صالحة')),
            'short' => $answer === null ? null : mb_substr(trim((string)$answer), 0, 500), default => $answer === null ? null : mb_substr((string)$answer, 0, 5000),
        };
        DB::query('INSERT INTO quiz_answers (attempt_id, question_id, answer) VALUES (?,?,?) ON DUPLICATE KEY UPDATE answer = VALUES(answer), score = NULL', [$attemptId, $qid, json_encode($v, JSON_UNESCAPED_UNICODE)]);
    }
    public static function submit(int $attemptId): array { $a = self::mine($attemptId); if ($a['status'] === 'in_progress') { self::finalize($attemptId); } return self::resultFor($attemptId); }

    /** تصحيح آلي للموضوعي؛ المقالي ينتظر المعلم (score = NULL). */
    private static function finalize(int $attemptId): void
    {
        DB::transaction(function () use ($attemptId) {
            $a = DB::one('SELECT * FROM quiz_attempts WHERE id = ? FOR UPDATE', [$attemptId]); if (!$a || $a['status'] !== 'in_progress') { return; }
            $pending = 0; $total = 0.0;
            foreach (DB::all('SELECT q.id, q.kind, q.answer, COALESCE(qq.points, q.points) AS pts FROM quiz_questions qq JOIN question_bank q ON q.id = qq.question_id WHERE qq.quiz_id = ?', [$a['quiz_id']]) as $q) {
                $given = DB::one('SELECT answer FROM quiz_answers WHERE attempt_id = ? AND question_id = ?', [$attemptId, $q['id']]); $val = $given ? json_decode($given['answer'], true) : null; $score = null;
                if ($val === null || $val === '') { $score = 0.0; }
                elseif ($q['kind'] === 'mcq' || $q['kind'] === 'true_false') { $score = $val === json_decode($q['answer'], true) ? (float)$q['pts'] : 0.0; }
                elseif ($q['kind'] === 'short') { $acc = json_decode($q['answer'], true) ?: []; $score = $acc ? (in_array(TabularFile::norm((string)$val), array_map([TabularFile::class, 'norm'], $acc), true) ? (float)$q['pts'] : 0.0) : null; }
                if ($score === null) { $pending++; } else { $total += $score; }
                DB::query('INSERT INTO quiz_answers (attempt_id, question_id, answer, score) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE score = VALUES(score)', [$attemptId, $q['id'], json_encode($val, JSON_UNESCAPED_UNICODE), $score]);
            }
            DB::query('UPDATE quiz_attempts SET status = ?, submitted_at = ?, score = ?, graded_at = ? WHERE id = ?', [$pending ? 'submitted' : 'graded', now(), round($total, 2), $pending ? null : now(), $attemptId]);
        });
    }
    private static function visible(array $a): bool
    {
        return match ($a['show_answers']) { 'after_submit' => true, 'after_close' => !$a['closes_at'] || time() > strtotime($a['closes_at']), default => false };
    }
    public static function resultFor(int $attemptId): array
    {
        $a = self::mine($attemptId); self::finalizeIfExpired($attemptId); $a = self::mine($attemptId);
        if ($a['status'] === 'in_progress') { throw new HttpException(422, 'المحاولة لم تنتهِ بعد'); }
        $a['max'] = round((float)DB::value('SELECT COALESCE(SUM(COALESCE(qq.points, q.points)),0) FROM quiz_questions qq JOIN question_bank q ON q.id = qq.question_id WHERE qq.quiz_id = ?', [$a['quiz_id']]), 2);
        $a['pending'] = (int)DB::value('SELECT COUNT(*) FROM quiz_answers WHERE attempt_id = ? AND score IS NULL', [$attemptId]); $a['reveal'] = self::visible($a); $a['rows'] = [];
        if ($a['reveal']) {
            foreach (DB::all('SELECT q.id, q.kind, q.body, q.options, q.answer, q.image_file_id, q.option_images, COALESCE(qq.points, q.points) AS pts, an.answer AS given, an.score FROM quiz_questions qq JOIN question_bank q ON q.id = qq.question_id LEFT JOIN quiz_answers an ON an.attempt_id = ? AND an.question_id = q.id WHERE qq.quiz_id = ? ORDER BY qq.sort_order', [$attemptId, $a['quiz_id']]) as $r) {
                $r['options'] = $r['options'] ? json_decode($r['options'], true) : null; $r['option_images'] = $r['option_images'] ? json_decode($r['option_images'], true) : []; $r['answer'] = $r['answer'] !== null ? json_decode($r['answer'], true) : null; $r['given'] = $r['given'] !== null ? json_decode($r['given'], true) : null; $a['rows'][] = $r;
            }
        }
        return $a;
    }

    // ---------- تصحيح المعلم ----------
    public static function attempts(int $quizId): array
    {
        $z = self::quizRow($quizId); LmsService::assertManage((int)$z['section_subject_id']);
        foreach (DB::all("SELECT id FROM quiz_attempts WHERE quiz_id = ? AND status = 'in_progress'", [$quizId]) as $a) { self::finalizeIfExpired((int)$a['id']); }
        return DB::all("SELECT a.id, a.status, a.score, a.started_at, a.submitted_at, s.full_name, s.student_no, (SELECT COUNT(*) FROM quiz_answers an WHERE an.attempt_id = a.id AND an.score IS NULL) AS pending
            FROM quiz_attempts a JOIN student_enrollments e ON e.id = a.enrollment_id JOIN students s ON s.id = e.student_id WHERE a.quiz_id = ? AND a.school_id = ? ORDER BY s.full_name, a.id", [$quizId, Tenant::require()]);
    }
    public static function attemptForTeacher(int $attemptId): array
    {
        $a = DB::one('SELECT a.*, z.title, z.section_subject_id, s.full_name FROM quiz_attempts a JOIN quizzes z ON z.id = a.quiz_id JOIN student_enrollments e ON e.id = a.enrollment_id JOIN students s ON s.id = e.student_id WHERE a.id = ? AND a.school_id = ?', [$attemptId, Tenant::require()]) ?? throw new HttpException(404, 'المحاولة غير موجودة');
        LmsService::assertManage((int)$a['section_subject_id']); self::finalizeIfExpired($attemptId);
        $a['rows'] = DB::all('SELECT q.id, q.kind, q.body, q.options, q.answer, q.image_file_id, q.option_images, COALESCE(qq.points, q.points) AS pts, an.answer AS given, an.score FROM quiz_questions qq JOIN question_bank q ON q.id = qq.question_id LEFT JOIN quiz_answers an ON an.attempt_id = ? AND an.question_id = q.id WHERE qq.quiz_id = ? ORDER BY qq.sort_order', [$attemptId, $a['quiz_id']]);
        foreach ($a['rows'] as &$r) { $r['options'] = $r['options'] ? json_decode($r['options'], true) : null; $r['option_images'] = $r['option_images'] ? json_decode($r['option_images'], true) : []; $r['answer'] = $r['answer'] !== null ? json_decode($r['answer'], true) : null; $r['given'] = $r['given'] !== null ? json_decode($r['given'], true) : null; } return $a;
    }
    public static function gradeAnswer(int $attemptId, int $qid, string $score): void
    {
        $a = self::attemptForTeacher($attemptId); if ($a['status'] === 'in_progress') { throw new HttpException(422, 'المحاولة لم تنتهِ بعد'); }
        $row = null; foreach ($a['rows'] as $r) { if ((int)$r['id'] === $qid) { $row = $r; } } if (!$row) { throw new HttpException(404, 'السؤال غير موجود'); }
        if (!is_numeric($score) || (float)$score < 0 || (float)$score > (float)$row['pts']) { throw new HttpException(422, 'الدرجة من 0 إلى ' . (float)$row['pts'], ['score' => 'خارج المدى']); }
        DB::transaction(function () use ($attemptId, $qid, $score, $a) {
            DB::query('INSERT INTO quiz_answers (attempt_id, question_id, answer, score) VALUES (?,?,NULL,?) ON DUPLICATE KEY UPDATE score = VALUES(score)', [$attemptId, $qid, round((float)$score, 2)]);
            $sum = (float)DB::value('SELECT COALESCE(SUM(score),0) FROM quiz_answers WHERE attempt_id = ?', [$attemptId]); $pending = (int)DB::value('SELECT COUNT(*) FROM quiz_answers WHERE attempt_id = ? AND score IS NULL', [$attemptId]);
            DB::query('UPDATE quiz_attempts SET score = ?, status = ?, graded_at = ? WHERE id = ?', [round($sum, 2), $pending ? 'submitted' : 'graded', $pending ? null : now(), $attemptId]);
        });
        AuditService::log('quiz.grade', 'quiz_attempt', $attemptId, null, ['question' => $qid, 'score' => $score]);
        if ($a['status'] !== 'graded') { $left = (int)DB::value('SELECT COUNT(*) FROM quiz_answers WHERE attempt_id = ? AND score IS NULL', [$attemptId]); if (!$left) { $uid = DB::value('SELECT s.user_id FROM student_enrollments e JOIN students s ON s.id = e.student_id WHERE e.id = ?', [$a['enrollment_id']]); if ($uid) { NotificationService::notify([(int)$uid], 'تم تصحيح اختبارك: ' . $a['title'], null, url('learn/attempts/' . $attemptId . '/result')); } } }
    }
}
