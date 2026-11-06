<?php
declare(strict_types=1);
namespace App\Middleware;

use App\Core\{Auth, HttpException, Request, Response};

/** الاستخدام: PermissionMiddleware::class.':students.view' (أو عدة صلاحيات مفصولة بـ | = أيٌّ منها). */
final class PermissionMiddleware
{
    public function handle(Request $r, callable $next, ?string $arg = null): Response
    {
        foreach (explode('|', (string)$arg) as $p) {
            if (Auth::can($p)) { return $next($r); }
        }
        throw (new HttpException(403, 'ليست لديك صلاحية الوصول لهذه الصفحة'))->withPerm((string)$arg);
    }
}
