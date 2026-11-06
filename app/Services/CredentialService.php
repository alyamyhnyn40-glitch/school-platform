<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\{Auth, DB, HttpException, Tenant};

/** الشهادات والاعتمادات الرقمية: رمز تحقق عام غير قابل للتخمين، والتحقق العلني يُظهر اسمًا مقنَّعًا فقط (بيانات قاصرين). */
final class CredentialService
{
    public const KINDS = ['completion' => 'إتمام مرحلة', 'graduation' => 'تخرّج', 'excellence' => 'تفوّق', 'attendance' => 'مواظبة', 'participation' => 'مشاركة', 'other' => 'أخرى'];

    private static function code(): string
    {
        $a = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; $c = ''; for ($i = 0; $i < 12; $i++) { $c .= $a[random_int(0, strlen($a) - 1)]; } return substr($c, 0, 4) . '-' . substr($c, 4, 4) . '-' . substr($c, 8, 4);
    }
    public static function issue(int $studentId, string $kind, string $title, ?string $notes): int
    {
        Auth::authorize('reportcards.issue'); $sid = Tenant::require();
        DB::value('SELECT 1 FROM students WHERE id = ? AND school_id = ? AND deleted_at IS NULL', [$studentId, $sid]) ?? throw new HttpException(404, 'الطالب غير موجود');
        if (!isset(self::KINDS[$kind])) { throw new HttpException(422, 'نوع غير صالح'); } if (mb_strlen(trim($title)) < 3) { throw new HttpException(422, 'عنوان الشهادة مطلوب', ['title' => 'مطلوب']); }
        for ($i = 0; $i < 5; $i++) {
            $code = self::code();
            try { DB::query('INSERT INTO credentials (school_id, student_id, title, verify_code, issued_on, issued_by, kind, notes) VALUES (?,?,?,?,?,?,?,?)', [$sid, $studentId, trim($title), $code, date('Y-m-d'), Auth::id(), $kind, $notes ?: null]); $id = DB::lastId(); AuditService::log('credential.issue', 'credential', $id, null, ['student_id' => $studentId, 'title' => $title]); return $id; }
            catch (\PDOException $e) { if (($e->errorInfo[1] ?? 0) !== 1062) { throw $e; } }
        }
        throw new HttpException(500, 'تعذّر توليد رمز فريد');
    }
    public static function list(?int $studentId = null): array
    {
        Auth::authorize('reportcards.issue'); $w = 'c.school_id = ?'; $p = [Tenant::require()]; if ($studentId) { $w .= ' AND c.student_id = ?'; $p[] = $studentId; }
        return DB::all("SELECT c.*, s.full_name, s.student_no FROM credentials c JOIN students s ON s.id = c.student_id WHERE $w ORDER BY c.id DESC LIMIT 200", $p);
    }
    public static function revoke(int $id, string $reason): void
    {
        Auth::authorize('reportcards.issue'); if (mb_strlen(trim($reason)) < 5) { throw new HttpException(422, 'اذكر سبب السحب (5 أحرف على الأقل)', ['reason' => 'مطلوب']); }
        $c = DB::one('SELECT * FROM credentials WHERE id = ? AND school_id = ?', [$id, Tenant::require()]) ?? throw new HttpException(404, 'الشهادة غير موجودة'); if ($c['revoked_at']) { return; }
        DB::query('UPDATE credentials SET revoked_at = ?, revoke_reason = ? WHERE id = ?', [now(), mb_substr($reason, 0, 255), $id]); AuditService::log('credential.revoke', 'credential', $id, null, ['reason' => $reason]);
    }
    public static function find(int $id): array
    {
        Auth::authorize('reportcards.issue');
        return DB::one('SELECT c.*, s.full_name, s.student_no, sch.name AS school_name, sch.city FROM credentials c JOIN students s ON s.id = c.student_id JOIN schools sch ON sch.id = c.school_id WHERE c.id = ? AND c.school_id = ?', [$id, Tenant::require()]) ?? throw new HttpException(404, 'الشهادة غير موجودة');
    }
    /** تحقق عام بلا تسجيل دخول: لا يعيد إلا ما يلزم. */
    public static function verify(string $code): ?array
    {
        $code = strtoupper(trim($code)); if (!preg_match('/^[A-Z2-9]{4}-[A-Z2-9]{4}-[A-Z2-9]{4}$/', $code)) { return null; }
        $c = DB::one('SELECT c.title, c.kind, c.issued_on, c.revoked_at, c.revoke_reason, s.full_name, sch.name AS school_name FROM credentials c JOIN students s ON s.id = c.student_id JOIN schools sch ON sch.id = c.school_id WHERE c.verify_code = ?', [$code]);
        if (!$c) { return null; }
        $parts = preg_split('/\s+/u', trim($c['full_name'])) ?: []; $masked = $parts ? $parts[0] . (count($parts) > 1 ? ' ' . implode(' ', array_map(fn($p) => mb_substr($p, 0, 1) . '.', array_slice($parts, 1))) : '') : '';
        return ['school' => $c['school_name'], 'holder' => $masked, 'title' => $c['title'], 'kind' => self::KINDS[$c['kind']] ?? $c['kind'], 'issued_on' => $c['issued_on'], 'valid' => $c['revoked_at'] === null, 'revoked_at' => $c['revoked_at'], 'code' => $code];
    }
}
