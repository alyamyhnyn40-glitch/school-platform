<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\{Auth, DB, HttpException, Request, Session, Tenant};

/**
 * سجل أحداث الأمان: يُسجَّل كل رفض (401/403/419/429) وفشل الدخول والقفل، مع المستخدم والمدرسة والمسار والصلاحية المطلوبة.
 * لا يُسجَّل أي كلمة مرور أو رمز، ولا يُسقط التطبيق عند فشل التسجيل نفسه.
 */
final class SecurityLog
{
    public const TYPES = ['forbidden' => 'رفض صلاحية (403)', 'unauthenticated' => 'غير مسجّل (401)', 'csrf' => 'CSRF (419)', 'rate_limited' => 'تجاوز المعدل (429)', 'login_failed' => 'فشل دخول', 'account_locked' => 'قفل حساب', 'login_ok' => 'دخول ناجح', 'switch_school' => 'تبديل مدرسة'];

    public static function record(string $type, ?int $status = null, ?string $detail = null, ?string $perm = null, ?Request $req = null, ?int $schoolId = null, ?int $userId = null, ?string $ip = null): void
    {
        try {
            $u = Auth::user(); $req ??= new Request();
            $sid = $schoolId ?? Tenant::id();
            DB::query('INSERT INTO security_events (school_id, user_id, user_type, type, status, method, path, perm, detail, ip, ua) VALUES (?,?,?,?,?,?,?,?,?,?,?)', [
                $sid, $userId ?? ($u['id'] ?? null), $u['user_type'] ?? null, $type, $status, $req->method(), mb_substr($req->path(), 0, 255), $perm ? mb_substr($perm, 0, 80) : null,
                $detail !== null ? mb_substr($detail, 0, 255) : null, $ip ?? $req->ip(), mb_substr($req->ua(), 0, 160)]);
            if (random_int(1, 200) === 1) { DB::query('DELETE FROM security_events WHERE created_at < NOW() - INTERVAL 180 DAY'); }
        } catch (\Throwable) { /* لا يُعطّل الطلب */ }
    }
    /** من معالج الأخطاء المركزي. */
    public static function fromException(HttpException $e, Request $req): void
    {
        $type = match ($e->status) { 401 => 'unauthenticated', 403 => 'forbidden', 419 => 'csrf', 429 => 'rate_limited', default => null };
        if ($type === null) { return; }
        if ($req->path() === '/api/v1/notifications' && $e->status === 401) { return; }   // استطلاع الجرس بعد انتهاء الجلسة: ضجيج
        self::record($type, $e->status, $e->getMessage(), $e->perm, $req);
    }

    /** @param array{type?:string,school?:int,user?:int,q?:string,from?:string,to?:string,perm?:string} $f */
    public static function search(array $f, ?int $forSchool = null, int $limit = 200): array
    {
        $w = '1=1'; $p = [];
        if ($forSchool !== null) { $w .= ' AND e.school_id = ?'; $p[] = $forSchool; } elseif (!empty($f['school'])) { $w .= ' AND e.school_id = ?'; $p[] = (int)$f['school']; }
        if (!empty($f['type']) && isset(self::TYPES[$f['type']])) { $w .= ' AND e.type = ?'; $p[] = $f['type']; }
        if (!empty($f['user'])) { $w .= ' AND e.user_id = ?'; $p[] = (int)$f['user']; }
        if (!empty($f['perm'])) { $w .= ' AND e.perm = ?'; $p[] = (string)$f['perm']; }
        if (!empty($f['from']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $f['from'])) { $w .= ' AND e.created_at >= ?'; $p[] = $f['from'] . ' 00:00:00'; }
        if (!empty($f['to']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $f['to'])) { $w .= ' AND e.created_at <= ?'; $p[] = $f['to'] . ' 23:59:59'; }
        if (!empty($f['q'])) { $l = '%' . addcslashes((string)$f['q'], '%_\\') . '%'; $w .= ' AND (e.path LIKE ? OR e.detail LIKE ? OR e.ip LIKE ?)'; array_push($p, $l, $l, $l); }
        return DB::all("SELECT e.*, u.full_name AS user_name, sc.name AS school_name FROM security_events e LEFT JOIN users u ON u.id = e.user_id LEFT JOIN schools sc ON sc.id = e.school_id WHERE $w ORDER BY e.id DESC LIMIT " . (int)$limit, $p);
    }
    /** ملخص آخر 7 أيام: أكثر الصلاحيات/المسارات رفضًا، وأكثر المستخدمين تكرارًا (لاكتشاف مشكلات صلاحيات حقيقية أو محاولات تخمين). */
    public static function summary(?int $forSchool = null): array
    {
        $w = "created_at > NOW() - INTERVAL 7 DAY"; $p = []; if ($forSchool !== null) { $w .= ' AND school_id = ?'; $p[] = $forSchool; }
        return [
            'by_type' => DB::all("SELECT type, COUNT(*) n FROM security_events WHERE $w GROUP BY type ORDER BY n DESC", $p),
            'top_forbidden' => DB::all("SELECT COALESCE(perm, '—') perm, path, COUNT(*) n, COUNT(DISTINCT user_id) users FROM security_events WHERE $w AND type = 'forbidden' GROUP BY perm, path ORDER BY n DESC LIMIT 10", $p),
            'repeat_users' => DB::all("SELECT e.user_id, u.full_name, COUNT(*) n FROM security_events e LEFT JOIN users u ON u.id = e.user_id WHERE " . str_replace('created_at', 'e.created_at', str_replace('school_id', 'e.school_id', $w)) . " AND e.type = 'forbidden' AND e.user_id IS NOT NULL GROUP BY e.user_id, u.full_name HAVING n >= 5 ORDER BY n DESC LIMIT 10", $p),
            'failed_logins_ips' => DB::all("SELECT ip, COUNT(*) n FROM security_events WHERE $w AND type IN ('login_failed','account_locked') GROUP BY ip HAVING n >= 5 ORDER BY n DESC LIMIT 10", $p),
        ];
    }
}
