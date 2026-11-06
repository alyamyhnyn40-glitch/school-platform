<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\{Auth, DB, HttpException, Tenant};

/** الحصص المباشرة: تكامل برابط مع Zoom/Meet/Teams/BBB/Jitsi (لا نبني خادم فيديو). الانضمام يسجّل حضورًا آليًا. */
final class LiveService
{
    public const PROVIDERS = ['jitsi' => 'Jitsi Meet', 'zoom' => 'Zoom', 'meet' => 'Google Meet', 'teams' => 'Microsoft Teams', 'bbb' => 'BigBlueButton', 'other' => 'أخرى'];

    public static function sessions(int $ssId, bool $manage): array
    {
        $manage ? LmsService::assertManage($ssId) : LmsService::learner($ssId);
        return DB::all('SELECT l.*, (SELECT COUNT(*) FROM live_attendance a WHERE a.live_session_id = l.id) AS joined FROM live_sessions l WHERE l.school_id = ? AND l.section_subject_id = ? ORDER BY l.starts_at DESC LIMIT 50', [Tenant::require(), $ssId]);
    }
    public static function create(int $ssId, string $title, string $provider, ?string $url, string $startsAt, ?string $endsAt, ?string $desc): int
    {
        $ss = LmsService::assertManage($ssId); $sid = Tenant::require();
        if (!isset(self::PROVIDERS[$provider])) { throw new HttpException(422, 'مزوّد غير معروف'); }
        if (mb_strlen(trim($title)) < 2) { throw new HttpException(422, 'عنوان الحصة مطلوب', ['title' => 'مطلوب']); }
        $s = strtotime(str_replace('T', ' ', $startsAt)); $e = $endsAt ? strtotime(str_replace('T', ' ', $endsAt)) : null; if (!$s) { throw new HttpException(422, 'وقت البدء غير صالح', ['starts_at' => 'غير صالح']); } if ($e && $e <= $s) { throw new HttpException(422, 'وقت الانتهاء يجب أن يلي البدء'); }
        $url = trim((string)$url);
        if ($url === '' && $provider === 'jitsi') { $url = 'https://meet.jit.si/madrasati-' . $sid . '-' . bin2hex(random_bytes(6)); }   // غرفة Jitsi فريدة غير قابلة للتخمين
        if (!preg_match('#^https://[^\s<>"\']+$#i', $url)) { throw new HttpException(422, 'رابط الحصة يجب أن يبدأ بـ https://', ['join_url' => 'غير صالح']); }
        DB::query('INSERT INTO live_sessions (school_id, section_subject_id, title, provider, join_url, starts_at, ends_at, created_by, description) VALUES (?,?,?,?,?,?,?,?,?)', [$sid, $ssId, trim($title), $provider, $url, date('Y-m-d H:i:s', $s), $e ? date('Y-m-d H:i:s', $e) : null, Auth::id(), $desc ?: null]);
        $id = DB::lastId(); NotificationService::notify(NotificationService::sectionUsers((int)$ss['section_id']), 'حصة مباشرة: ' . trim($title), $ss['subject_name'] . ' — ' . date('Y-m-d H:i', $s), url('learn/live/' . $id));
        AuditService::log('live.create', 'live_session', $id, null, ['title' => $title, 'provider' => $provider]); return $id;
    }
    // ---------- تبسيط التجربة ----------
    /** حالة الحصة بالنسبة للوقت الحالي: live (جارية) | soon (تبدأ خلال 15 دقيقة) | upcoming | ended */
    public static function state(array $l, ?int $now = null): string
    {
        $now ??= time(); $st = strtotime($l['starts_at']); $en = $l['ends_at'] ? strtotime($l['ends_at']) : $st + 3600;
        if ($now > $en + 1800) { return 'ended'; } if ($now >= $st) { return 'live'; } if ($now >= $st - 900) { return 'soon'; } return 'upcoming';
    }
    /** إنشاء مبسّط: العنوان اختياري، البدء اختياري (الآن)، المدة بالدقائق، Jitsi افتراضيًا برابط يُنشأ تلقائيًا. */
    public static function quick(int $ssId, ?string $title, ?string $startsAt, int $minutes = 60, string $provider = 'jitsi', ?string $url = null, ?string $desc = null): int
    {
        $ss = LmsService::assertManage($ssId); $minutes = max(10, min(240, $minutes));
        $s = ($startsAt === null || trim($startsAt) === '') ? time() : (strtotime(str_replace('T', ' ', $startsAt)) ?: throw new HttpException(422, 'وقت البدء غير صالح', ['starts_at' => 'غير صالح']));
        $t = trim((string)$title) !== '' ? trim((string)$title) : 'حصة مباشرة — ' . $ss['subject_name'] . ' — ' . date('Y-m-d', $s);
        return self::create($ssId, $t, $provider, $url, date('Y-m-d H:i:s', $s), date('Y-m-d H:i:s', $s + $minutes * 60), $desc);
    }
    /** «ابدأ الآن»: يعيد حصة جارية للمعلم نفسه لهذه المادة إن وُجدت (لا يكرر الغرف والإشعارات) وإلا ينشئ واحدة. @return array{id:int, url:string, reused:bool} */
    public static function startNow(int $ssId, ?string $title = null, int $minutes = 60): array
    {
        LmsService::assertManage($ssId);
        $cur = DB::one("SELECT * FROM live_sessions WHERE school_id = ? AND section_subject_id = ? AND created_by = ? AND starts_at <= NOW() AND COALESCE(ends_at, starts_at + INTERVAL 1 HOUR) >= NOW() ORDER BY id DESC LIMIT 1", [Tenant::require(), $ssId, Auth::id()]);
        if ($cur) { return ['id' => (int)$cur['id'], 'url' => $cur['join_url'], 'reused' => true]; }
        $id = self::quick($ssId, $title, null, $minutes); return ['id' => $id, 'url' => (string)DB::value('SELECT join_url FROM live_sessions WHERE id = ?', [$id]), 'reused' => false];
    }
    /** إنهاء حصة جارية الآن (يُغلق الدخول بعد 30 دقيقة سماح كما في السياسة). */
    public static function end(int $id): void
    {
        $l = DB::one('SELECT * FROM live_sessions WHERE id = ? AND school_id = ?', [$id, Tenant::require()]) ?? throw new HttpException(404, 'الحصة غير موجودة'); LmsService::assertManage((int)$l['section_subject_id']);
        if (self::state($l) === 'ended') { return; } if (strtotime($l['starts_at']) > time()) { throw new HttpException(422, 'الحصة لم تبدأ بعد؛ احذفها إن لم تعد لازمة'); }
        DB::query('UPDATE live_sessions SET ends_at = ? WHERE id = ?', [now(), $id]); AuditService::log('live.end', 'live_session', $id);
    }
    /** حصة واحدة للصفحة الخاصة بها (للطالب المسجّل أو المعلم المسؤول). */
    public static function find(int $id): array
    {
        $l = DB::one('SELECT l.*, sub.name AS subject_name FROM live_sessions l JOIN section_subjects ss ON ss.id = l.section_subject_id JOIN subjects sub ON sub.id = ss.subject_id WHERE l.id = ? AND l.school_id = ?', [$id, Tenant::require()]) ?? throw new HttpException(404, 'الحصة غير موجودة');
        if (Auth::type() === 'student') { LmsService::learner((int)$l['section_subject_id']); } else { LmsService::assertManage((int)$l['section_subject_id']); }
        $l['state'] = self::state($l); return $l;
    }
    /** الحصص الجارية أو القريبة الآن: للطالب في مواده، وللمعلم في مواده المُسندة. */
    public static function liveNow(): array
    {
        $sid = Tenant::require(); $ids = [];
        if (Auth::type() === 'student') { $ids = array_map(fn($x) => (int)$x['id'], LmsService::mySubjects()); }
        elseif (Auth::can('lms.manage')) { $ids = array_map(fn($x) => (int)$x['id'], LmsService::manageable()); }
        if (!$ids) { return []; }
        $rows = DB::all('SELECT l.*, sub.name AS subject_name FROM live_sessions l JOIN section_subjects ss ON ss.id = l.section_subject_id JOIN subjects sub ON sub.id = ss.subject_id WHERE l.school_id = ? AND l.section_subject_id IN (' . implode(',', $ids) . ') AND l.starts_at <= NOW() + INTERVAL 15 MINUTE AND COALESCE(l.ends_at, l.starts_at + INTERVAL 1 HOUR) >= NOW() - INTERVAL 30 MINUTE ORDER BY l.starts_at', [$sid]);
        foreach ($rows as &$r) { $r['state'] = self::state($r); } return $rows;
    }
    public static function delete(int $id): void
    {
        $l = DB::one('SELECT * FROM live_sessions WHERE id = ? AND school_id = ?', [$id, Tenant::require()]) ?? throw new HttpException(404, 'غير موجودة'); LmsService::assertManage((int)$l['section_subject_id']);
        DB::query('DELETE FROM live_attendance WHERE live_session_id = ?', [$id]); DB::query('DELETE FROM live_sessions WHERE id = ?', [$id]);
    }
    /** الانضمام: الطالب يُسجَّل حضوره في الحصة وفي وحدة الحضور (جلسة live لليوم)، والمعلم يدخل بلا تسجيل. يعيد الرابط. */
    public static function join(int $id): string
    {
        $l = DB::one('SELECT * FROM live_sessions WHERE id = ? AND school_id = ?', [$id, Tenant::require()]) ?? throw new HttpException(404, 'الحصة غير موجودة'); $sid = Tenant::require();
        if (Auth::type() !== 'student') { LmsService::assertManage((int)$l['section_subject_id']); return $l['join_url']; }
        $le = LmsService::learner((int)$l['section_subject_id']);
        $start = strtotime($l['starts_at']); $end = $l['ends_at'] ? strtotime($l['ends_at']) : $start + 3 * 3600;
        if (time() < $start - 15 * 60) { throw new HttpException(422, 'لم يحن موعد الحصة بعد (يُفتح الدخول قبل الموعد بـ 15 دقيقة)'); }
        if (time() > $end + 30 * 60) { throw new HttpException(422, 'انتهت هذه الحصة'); }
        DB::transaction(function () use ($id, $le, $sid, $l) {
            DB::query('INSERT IGNORE INTO live_attendance (live_session_id, enrollment_id, joined_at) VALUES (?,?,?)', [$id, $le['enrollment_id'], now()]);
            $sec = (int)$le['ss']['section_id']; $date = date('Y-m-d');
            DB::query("INSERT IGNORE INTO attendance_sessions (school_id, section_id, section_subject_id, session_date, period_no, taken_by, kind) VALUES (?,?,?,?,0,?, 'live')", [$sid, $sec, $l['section_subject_id'], $date, $l['created_by']]);
            $sess = (int)DB::value("SELECT id FROM attendance_sessions WHERE school_id = ? AND section_id = ? AND session_date = ? AND period_no = 0 AND kind = 'live'", [$sid, $sec, $date]);
            DB::query("INSERT IGNORE INTO attendance_records (school_id, session_id, enrollment_id, status, note, updated_by) VALUES (?,?,?, 'present', 'حصة مباشرة', ?)", [$sid, $sess, $le['enrollment_id'], Auth::id()]);
        });
        return $l['join_url'];
    }
    public static function attendees(int $id): array
    {
        $l = DB::one('SELECT * FROM live_sessions WHERE id = ? AND school_id = ?', [$id, Tenant::require()]) ?? throw new HttpException(404, 'غير موجودة'); LmsService::assertManage((int)$l['section_subject_id']);
        return DB::all('SELECT s.full_name, s.student_no, a.joined_at FROM live_attendance a JOIN student_enrollments e ON e.id = a.enrollment_id JOIN students s ON s.id = e.student_id WHERE a.live_session_id = ? ORDER BY a.joined_at', [$id]);
    }
}
