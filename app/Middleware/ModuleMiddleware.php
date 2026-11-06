<?php
declare(strict_types=1);
namespace App\Middleware;

use App\Core\{HttpException, Request, Response, Tenant};
use App\Services\SubscriptionService;

/** يمنع الوصول إن كانت الوحدة غير مفعّلة في اشتراك المدرسة. الاستخدام: ModuleMiddleware::class.':fees' */
final class ModuleMiddleware
{
    public function handle(Request $r, callable $next, ?string $arg = null): Response
    {
        if ($arg && !SubscriptionService::moduleEnabled(Tenant::require(), $arg)) { throw new HttpException(403, 'هذه الوحدة غير مفعّلة في اشتراك مدرستك. تواصل مع إدارة المنصة.'); }
        return $next($r);
    }
}
