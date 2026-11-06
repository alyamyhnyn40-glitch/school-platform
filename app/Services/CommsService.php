<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\{Auth, DB, HttpException, Tenant};

/** الإعلانات والرسائل الداخلية. الرسائل مقيّدة بالعلاقات المشروعة (معلم↔طلاب/أولياء شعبه، إدارة↔الجميع). */
final class CommsService
{
    private static function adminLevel(): bool { return Auth::can('comms.notify'); }

    // ---------- الإعلانات ----------
    private static function audienceUsers(string $audience, ?int $sectionId): array
    {
        $sid = Tenant::require();
        $by = fn(string $type) => array_map('intval', array_column(DB::all('SELECT id FROM users WHERE school_id = ? AND user_type = ? AND status = "active" AND deleted_at IS NULL', [$sid, $type]), 'id'));
        return match ($audience) {
            'staff' => $by('staff'), 'students' => $by('student'), 'guardians' => $by('guardian'),
            'section' => NotificationService::sectionUsers((int)$sectionId),
            default => array_merge($by('staff'), $by('student'), $by('guardian')),
        };
    }
    public static function create(string $title, string $body, string $audience, ?int $sectionId, bool $sms, bool $wa = false): int
    {
        Auth::authorize('comms.announce'); $sid = Tenant::require();
        if (mb_strlen(trim($title)) < 2 || mb_strlen(trim($body)) < 2) { throw new HttpException(422, 'العنوان والنص مطلوبان', ['title' => 'مطلوب']); }
        if (!in_array($audience, ['all', 'staff', 'students', 'guardians', 'section'], true)) { throw new HttpException(422, 'جمهور غير صالح'); }
        if ($audience === 'section') {
            if (!$sectionId || !DB::value('SELECT 1 FROM sections WHERE id = ? AND school_id = ?', [$sectionId, $sid])) { throw new HttpException(422, 'اختر شعبة', ['section_id' => 'مطلوبة']); }
            if (!self::adminLevel() && !Scope::canSeeSection($sectionId)) { throw new HttpException(403, 'ليست شعبتك'); }
        } elseif (!self::adminLevel()) { throw new HttpException(403, 'المعلم ينشر لشعبه فقط'); }
        if (($sms || $wa) && !self::adminLevel()) { throw new HttpException(403, 'إرسال SMS أو واتساب يتطلب صلاحية الإشعارات الجماعية'); }
        if ($wa && !NotificationService::whatsappEnabled()) { throw new HttpException(422, 'قناة واتساب غير مفعّلة من إدارة المنصة'); }
        DB::query('INSERT INTO announcements (school_id, title, body, audience, section_id, published_at, created_by) VALUES (?,?,?,?,?,?,?)', [$sid, trim($title), trim($body), $audience, $audience === 'section' ? $sectionId : null, now(), Auth::id()]);
        $id = DB::lastId(); $users = self::audienceUsers($audience, $sectionId);
        NotificationService::notify($users, 'إعلان: ' . trim($title), mb_substr(trim($body), 0, 140), url('announcements'));
        $phones = 0;
        if ($sms) {
            $p = $audience === 'section' ? NotificationService::sectionGuardianPhones((int)$sectionId) : array_column(DB::all('SELECT DISTINCT phone FROM guardians WHERE phone IS NOT NULL AND deleted_at IS NULL AND id IN (SELECT guardian_id FROM student_guardians WHERE school_id = ? AND can_view = 1)', [$sid]), 'phone');
            if (in_array($audience, ['all', 'guardians', 'section'], true)) { $phones = NotificationService::sms($p, DB::value('SELECT name FROM schools WHERE id = ?', [$sid]) . ': ' . trim($title) . ' — ' . mb_substr(trim($body), 0, 200)); }
        }
        $waN = 0;
        if ($wa && in_array($audience, ['all', 'guardians', 'section'], true)) {
            $p = $audience === 'section' ? NotificationService::sectionGuardianPhones((int)$sectionId, true) : array_column(DB::all("SELECT DISTINCT COALESCE(NULLIF(whatsapp, ''), phone) AS phone FROM guardians WHERE phone IS NOT NULL AND deleted_at IS NULL AND id IN (SELECT guardian_id FROM student_guardians WHERE school_id = ? AND can_view = 1)", [$sid]), 'phone');
            $waN = NotificationService::queue('whatsapp', $p, DB::value('SELECT name FROM schools WHERE id = ?', [$sid]) . ': ' . trim($title) . ' — ' . mb_substr(trim($body), 0, 200));
        }
        AuditService::log('announcement.create', 'announcement', $id, null, ['title' => $title, 'audience' => $audience, 'recipients' => count($users), 'sms' => $phones, 'whatsapp' => $waN]); return $id;
    }
    public static function delete(int $id): void
    {
        Auth::authorize('comms.announce'); $a = DB::one('SELECT * FROM announcements WHERE id = ? AND school_id = ? AND deleted_at IS NULL', [$id, Tenant::require()]) ?? throw new HttpException(404, 'الإعلان غير موجود');
        if (!self::adminLevel() && (int)$a['created_by'] !== Auth::id()) { throw new HttpException(403, 'يمكنك حذف إعلاناتك فقط'); }
        DB::query('UPDATE announcements SET deleted_at = NOW() WHERE id = ?', [$id]); AuditService::log('announcement.delete', 'announcement', $id, ['title' => $a['title']]);
    }
    /** إدارة الإعلانات: الإدارة ترى كل شيء، والمعلم إعلاناته. */
    public static function manageList(): array
    {
        Auth::authorize('comms.announce');
        return DB::all('SELECT a.*, u.full_name AS by_name, sc.name AS section_name, g.name AS grade_name FROM announcements a LEFT JOIN users u ON u.id = a.created_by LEFT JOIN sections sc ON sc.id = a.section_id LEFT JOIN grade_levels g ON g.id = sc.grade_level_id
            WHERE a.school_id = ? AND a.deleted_at IS NULL' . (self::adminLevel() ? '' : ' AND a.created_by = ' . (int)Auth::id()) . ' ORDER BY a.id DESC LIMIT 100', [Tenant::require()]);
    }
    /** الإعلانات الظاهرة للمستخدم الحالي. */
    public static function visible(int $limit = 30): array
    {
        Auth::authorize('comms.view'); $sid = Tenant::require(); $type = Auth::type();
        $w = "a.school_id = ? AND a.deleted_at IS NULL AND a.published_at IS NOT NULL AND (a.audience = 'all'"; $p = [$sid];
        $w .= match ($type) { 'staff' => " OR a.audience = 'staff'", 'student' => " OR a.audience = 'students'", 'guardian' => " OR a.audience = 'guardians'", default => '' };
        $sections = [];
        if ($type === 'student') { $sections = array_column(DB::all("SELECT e.section_id FROM student_enrollments e JOIN students s ON s.id = e.student_id WHERE s.user_id = ? AND e.school_id = ? AND e.status = 'active'", [Auth::id(), $sid]), 'section_id'); }
        elseif ($type === 'guardian') { $sections = array_column(DB::all("SELECT DISTINCT e.section_id FROM student_enrollments e JOIN student_guardians sg ON sg.student_id = e.student_id AND sg.school_id = e.school_id AND sg.can_view = 1 JOIN guardians g ON g.id = sg.guardian_id WHERE g.user_id = ? AND e.school_id = ? AND e.status = 'active'", [Auth::id(), $sid]), 'section_id'); }
        elseif ($type === 'staff') { $ids = Scope::sectionIds(); $sections = $ids === null ? array_column(DB::all('SELECT id FROM sections WHERE school_id = ?', [$sid]), 'id') : $ids; }
        $sections = array_map('intval', $sections); if ($sections) { $w .= " OR (a.audience = 'section' AND a.section_id IN (" . implode(',', $sections) . '))'; }
        return DB::all("SELECT a.id, a.title, a.body, a.audience, a.published_at, u.full_name AS by_name FROM announcements a LEFT JOIN users u ON u.id = a.created_by WHERE $w) ORDER BY a.id DESC LIMIT " . (int)$limit, $p);
    }

    // ---------- الرسائل ----------
    /** معرّفات المستخدمين الذين يحق للمستخدم الحالي مراسلتهم. */
    public static function allowedIds(): array
    {
        Auth::authorize('comms.view'); $sid = Tenant::require(); $me = (int)Auth::id(); $ids = [];
        $q = fn(string $sql, array $p = []) => array_map('intval', array_column(DB::all($sql, $p), 'id'));
        $adminIds = $q("SELECT DISTINCT u.id FROM users u JOIN user_roles ur ON ur.user_id = u.id JOIN role_permissions rp ON rp.role_id = ur.role_id JOIN permissions p ON p.id = rp.permission_id WHERE u.school_id = ? AND p.code = 'comms.notify' AND u.status = 'active' AND u.deleted_at IS NULL", [$sid]);
        $staffIds = $q("SELECT id FROM users WHERE school_id = ? AND user_type = 'staff' AND status = 'active' AND deleted_at IS NULL", [$sid]);
        $type = Auth::type();
        if ($type === 'staff') {
            if (self::adminLevel()) { $ids = $q("SELECT id FROM users WHERE school_id = ? AND status = 'active' AND deleted_at IS NULL", [$sid]); }
            else {
                $ids = $staffIds; $secs = Scope::sectionIds();
                foreach ($secs ?? [] as $sec) { $ids = array_merge($ids, NotificationService::sectionUsers((int)$sec)); }
            }
        } elseif ($type === 'student') {
            $ids = $q("SELECT DISTINCT st.user_id AS id FROM student_enrollments e JOIN students s ON s.id = e.student_id JOIN section_subjects ss ON ss.section_id = e.section_id JOIN teacher_assignments ta ON ta.section_subject_id = ss.id JOIN staff st ON st.id = ta.staff_id WHERE s.user_id = ? AND e.school_id = ? AND e.status = 'active' AND st.user_id IS NOT NULL", [$me, $sid]);
            $ids = array_merge($ids, $adminIds);
        } elseif ($type === 'guardian') {
            $ids = $q("SELECT DISTINCT st.user_id AS id FROM guardians g JOIN student_guardians sg ON sg.guardian_id = g.id AND sg.can_view = 1 JOIN student_enrollments e ON e.student_id = sg.student_id AND e.status = 'active' AND e.school_id = sg.school_id JOIN section_subjects ss ON ss.section_id = e.section_id JOIN teacher_assignments ta ON ta.section_subject_id = ss.id JOIN staff st ON st.id = ta.staff_id WHERE g.user_id = ? AND sg.school_id = ? AND st.user_id IS NOT NULL", [$me, $sid]);
            $ids = array_merge($ids, $adminIds);
        }
        return array_values(array_diff(array_unique($ids), [$me]));
    }
    public static function recipients(string $q = ''): array
    {
        $ids = self::allowedIds(); if (!$ids) { return []; }
        $sql = 'SELECT u.id, u.full_name, u.user_type FROM users u WHERE u.id IN (' . implode(',', $ids) . ')'; $p = [];
        if (trim($q) !== '') { $sql .= ' AND u.full_name LIKE ?'; $p[] = '%' . addcslashes(trim($q), '%_\\') . '%'; }
        return DB::all($sql . ' ORDER BY u.user_type, u.full_name LIMIT 40', $p);
    }
    private static function participant(int $threadId): array
    {
        $root = DB::one('SELECT * FROM messages WHERE id = ? AND school_id = ? AND (thread_id IS NULL OR thread_id = id)', [$threadId, Tenant::require()]) ?? throw new HttpException(404, 'المحادثة غير موجودة');
        $me = (int)Auth::id();
        $ok = (int)$root['sender_user_id'] === $me || DB::value('SELECT 1 FROM messages m JOIN message_recipients r ON r.message_id = m.id WHERE (m.id = ? OR m.thread_id = ?) AND r.user_id = ? LIMIT 1', [$threadId, $threadId, $me]) || DB::value('SELECT 1 FROM messages WHERE thread_id = ? AND sender_user_id = ? LIMIT 1', [$threadId, $me]);
        if (!$ok) { throw new HttpException(403, 'لست طرفًا في هذه المحادثة'); } return $root;
    }
    /** رسالة جديدة، أو رد إن مُرّر $threadId (يُرسَل لبقية المشاركين دون إعادة فحص العلاقة). */
    public static function send(array $recipientIds, string $subject, string $body, ?int $threadId = null): int
    {
        Auth::authorize('comms.view'); $sid = Tenant::require(); $me = (int)Auth::id();
        if (mb_strlen(trim($body)) < 1) { throw new HttpException(422, 'نص الرسالة مطلوب', ['body' => 'مطلوب']); } if (mb_strlen($body) > 5000) { throw new HttpException(422, 'الرسالة أطول من 5000 حرف'); }
        $to = array_values(array_unique(array_map('intval', $recipientIds)));
        if ($threadId) {
            $root = self::participant($threadId); $subject = (string)$root['subject'];
            $to = array_map('intval', array_column(DB::all('SELECT DISTINCT x.u AS id FROM (SELECT sender_user_id AS u FROM messages WHERE id = ? OR thread_id = ? UNION SELECT r.user_id FROM message_recipients r JOIN messages m ON m.id = r.message_id WHERE m.id = ? OR m.thread_id = ?) x', [$threadId, $threadId, $threadId, $threadId]), 'id'));
            $to = array_values(array_diff($to, [$me]));
        } else {
            if (mb_strlen(trim($subject)) < 2) { throw new HttpException(422, 'الموضوع مطلوب', ['subject' => 'مطلوب']); }
            if (!$to || count($to) > 50) { throw new HttpException(422, 'اختر من 1 إلى 50 مستلمًا', ['recipients' => 'مطلوب']); }
            $allowed = self::allowedIds(); foreach ($to as $r) { if (!in_array($r, $allowed, true)) { throw new HttpException(403, 'لا يمكنك مراسلة هذا المستخدم'); } }
        }
        if (!$to) { throw new HttpException(422, 'لا يوجد مستلمون'); }
        return DB::transaction(function () use ($sid, $me, $to, $subject, $body, $threadId) {
            DB::query('INSERT INTO messages (school_id, sender_user_id, parent_id, thread_id, subject, body, created_at) VALUES (?,?,?,?,?,?,?)', [$sid, $me, $threadId, $threadId, trim($subject), trim($body), now()]); $id = DB::lastId();
            if (!$threadId) { DB::query('UPDATE messages SET thread_id = id WHERE id = ?', [$id]); $threadId = $id; }
            foreach ($to as $r) { DB::query('INSERT INTO message_recipients (message_id, user_id) VALUES (?,?)', [$id, $r]); }
            NotificationService::notify($to, 'رسالة جديدة من ' . Auth::user()['full_name'], mb_substr(trim($subject), 0, 100), url('messages/' . $threadId));
            return $threadId;
        });
    }
    public static function inbox(): array
    {
        Auth::authorize('comms.view'); $me = (int)Auth::id(); $sid = Tenant::require();
        return DB::all("SELECT t.id, t.subject, t.created_at, (SELECT MAX(m2.created_at) FROM messages m2 WHERE m2.thread_id = t.id) AS last_at,
              (SELECT COUNT(*) FROM message_recipients r JOIN messages m3 ON m3.id = r.message_id WHERE m3.thread_id = t.id AND r.user_id = ? AND r.read_at IS NULL) AS unread, u.full_name AS starter
            FROM messages t JOIN users u ON u.id = t.sender_user_id WHERE t.school_id = ? AND t.thread_id = t.id AND (t.sender_user_id = ? OR EXISTS (SELECT 1 FROM messages m JOIN message_recipients r ON r.message_id = m.id WHERE m.thread_id = t.id AND r.user_id = ?) OR EXISTS (SELECT 1 FROM messages m WHERE m.thread_id = t.id AND m.sender_user_id = ?))
            ORDER BY last_at DESC LIMIT 100", [$me, $sid, $me, $me, $me]);
    }
    public static function thread(int $id): array
    {
        $root = self::participant($id); $me = (int)Auth::id();
        $msgs = DB::all('SELECT m.id, m.body, m.created_at, m.sender_user_id, u.full_name AS sender FROM messages m JOIN users u ON u.id = m.sender_user_id WHERE m.thread_id = ? AND m.school_id = ? ORDER BY m.id', [$id, Tenant::require()]);
        DB::query('UPDATE message_recipients r JOIN messages m ON m.id = r.message_id SET r.read_at = NOW() WHERE m.thread_id = ? AND r.user_id = ? AND r.read_at IS NULL', [$id, $me]);
        $people = DB::all('SELECT DISTINCT u.full_name FROM users u WHERE u.id IN (SELECT sender_user_id FROM messages WHERE thread_id = ? UNION SELECT r.user_id FROM message_recipients r JOIN messages m ON m.id = r.message_id WHERE m.thread_id = ?) AND u.id <> ?', [$id, $id, $me]);
        return ['subject' => $root['subject'], 'id' => $id, 'messages' => $msgs, 'people' => array_column($people, 'full_name'), 'me' => $me];
    }
    public static function unreadMessages(): int
    {
        return (int)DB::value('SELECT COUNT(*) FROM message_recipients r JOIN messages m ON m.id = r.message_id WHERE r.user_id = ? AND r.read_at IS NULL AND m.school_id = ?', [Auth::id(), Tenant::require()]);
    }
}
