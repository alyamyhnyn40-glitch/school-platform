<?php
declare(strict_types=1);
namespace App\Middleware;

use App\Core\{Csrf, HttpException, Request, Response};

/** يتحقق من CSRF لكل طلب معدِّل (POST/PUT/DELETE/PATCH). */
final class CsrfMiddleware
{
    public function handle(Request $r, callable $next, ?string $arg = null): Response
    {
        // إشعارات بوابة الدفع (من خادم المزود بلا متصفح): مُصادَق عليها بتوقيع HMAC/استعلام حالة داخل الخدمة بدل CSRF
        if (str_starts_with($r->path(), '/pay/callback/')) { return $next($r); }
        if (in_array($r->method(), ['POST', 'PUT', 'DELETE', 'PATCH'], true) && !Csrf::check($r->csrfToken())) {
            throw new HttpException(419, 'انتهت صلاحية الصفحة، أعد تحميلها وحاول مجددًا');
        }
        return $next($r);
    }
}
