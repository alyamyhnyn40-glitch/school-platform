<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\{Auth, DB, HttpException, Tenant};

/** الإرشاد الطلابي (دراسي/مهني/سلوكي/اجتماعي). الملاحظات مشفّرة، وكل اطلاع على حالة يُسجَّل في التدقيق. */
final class CounselingService
{
    public const KINDS = ['academic' => 'دراسي', 'career' => 'مهني', 'behavior' => 'سلوكي', 'social' => 'اجتماعي', 'other' => 'آخر'];
    public const STATUSES = ['open' => 'مفتوحة', 'follow_up' => 'متابعة', 'closed' => 'مغلقة'];

    public static function create(int $studentId, string $kind, string $title, ?string $note, ?int $flagId = null): int
    {
        Auth::authorize('counseling.manage'); $sid = Tenant::require();
        DB::value('SELECT 1 FROM students WHERE id = ? AND school_id = ? AND deleted_at IS NULL', [$studentId, $sid]) ?? throw new HttpException(404, 'الطالب غير موجود');
        if (!isset(self::KINDS[$kind])) { throw new HttpException(422, 'نوع غير صالح'); } if (mb_strlen(trim($title)) < 3) { throw new HttpException(422, 'عنوان الحالة مطلوب', ['title' => 'مطلوب']); }
        DB::query("INSERT INTO counseling_cases (school_id, student_id, counselor_user_id, title, notes_enc, status, kind, risk_flag_id, updated_at) VALUES (?,?,?,?,?, 'open', ?, ?, ?)", [$sid, $studentId, Auth::id(), trim($title), Crypto::encrypt($note), $kind, $flagId, now()]);
        $id = DB::lastId(); AuditService::log('counseling.create', 'counseling_case', $id, null, ['student_id' => $studentId, 'kind' => $kind]); return $id;   // لا نكتب النص في التدقيق
    }
    public static function cases(string $status = '', string $q = ''): array
    {
        Auth::authorize('counseling.manage'); $w = 'c.school_id = ?'; $p = [Tenant::require()];
        if (isset(self::STATUSES[$status])) { $w .= ' AND c.status = ?'; $p[] = $status; }
        if ($q !== '') { $w .= ' AND s.full_name LIKE ?'; $p[] = '%' . addcslashes($q, '%_\\') . '%'; }
        return DB::all("SELECT c.id, c.title, c.kind, c.status, c.updated_at, c.created_at, s.id AS student_id, s.full_name, u.full_name AS counselor, (SELECT COUNT(*) FROM counseling_notes n WHERE n.case_id = c.id) AS notes
            FROM counseling_cases c JOIN students s ON s.id = c.student_id JOIN users u ON u.id = c.counselor_user_id WHERE $w ORDER BY c.updated_at DESC, c.id DESC LIMIT 200", $p);
    }
    public static function detail(int $id): array
    {
        Auth::authorize('counseling.manage');
        $c = DB::one('SELECT c.*, s.full_name, s.student_no, u.full_name AS counselor FROM counseling_cases c JOIN students s ON s.id = c.student_id JOIN users u ON u.id = c.counselor_user_id WHERE c.id = ? AND c.school_id = ?', [$id, Tenant::require()]) ?? throw new HttpException(404, 'الحالة غير موجودة');
        $c['opening_note'] = Crypto::decrypt($c['notes_enc']); unset($c['notes_enc']);
        $c['notes'] = array_map(fn($n) => ['text' => Crypto::decrypt($n['note_enc']), 'by' => $n['author'], 'at' => $n['created_at']], DB::all('SELECT n.note_enc, n.created_at, u.full_name AS author FROM counseling_notes n JOIN users u ON u.id = n.user_id WHERE n.case_id = ? ORDER BY n.id', [$id]));
        AuditService::log('counseling.view', 'counseling_case', $id);   // الاطلاع على بيانات حساسة مُسجَّل
        return $c;
    }
    public static function addNote(int $id, string $note): void
    {
        Auth::authorize('counseling.manage'); $c = DB::one('SELECT id, status FROM counseling_cases WHERE id = ? AND school_id = ?', [$id, Tenant::require()]) ?? throw new HttpException(404, 'الحالة غير موجودة');
        if ($c['status'] === 'closed') { throw new HttpException(422, 'الحالة مغلقة'); } if (mb_strlen(trim($note)) < 2) { throw new HttpException(422, 'الملاحظة مطلوبة', ['note' => 'مطلوبة']); }
        DB::query('INSERT INTO counseling_notes (school_id, case_id, user_id, note_enc) VALUES (?,?,?,?)', [Tenant::require(), $id, Auth::id(), Crypto::encrypt(trim($note))]); DB::query('UPDATE counseling_cases SET updated_at = ? WHERE id = ?', [now(), $id]);
        AuditService::log('counseling.note', 'counseling_case', $id);
    }
    public static function setStatus(int $id, string $status): void
    {
        Auth::authorize('counseling.manage'); if (!isset(self::STATUSES[$status])) { throw new HttpException(422, 'حالة غير صالحة'); }
        $c = DB::one('SELECT status FROM counseling_cases WHERE id = ? AND school_id = ?', [$id, Tenant::require()]) ?? throw new HttpException(404, 'الحالة غير موجودة');
        DB::query('UPDATE counseling_cases SET status = ?, updated_at = ? WHERE id = ?', [$status, now(), $id]); AuditService::log('counseling.status', 'counseling_case', $id, ['status' => $c['status']], ['status' => $status]);
    }
}
