<?php
declare(strict_types=1);
namespace App\Middleware;

use App\Core\{Auth, DB, HttpException, Request, Response, Tenant};

/** يضمن وجود سياق مدرسة فعّالة (غير موقوفة) للمستخدم. */
final class TenantMiddleware
{
    public function handle(Request $r, callable $next, ?string $arg = null): Response
    {
        $sid = Tenant::id();
        if ($sid === null) { throw new HttpException(403, 'هذه الصفحة تخص مستخدمي المدارس'); }
        $st = DB::value('SELECT status FROM schools WHERE id = ? AND deleted_at IS NULL', [$sid]);
        if ($st !== 'active') { throw new HttpException(403, 'حساب المدرسة غير فعّال. تواصل مع إدارة المنصة.'); }
        return $next($r);
    }
}
