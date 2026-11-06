<?php
declare(strict_types=1);
namespace App\Middleware;

use App\Core\{Request, Response};

final class SecurityHeaders
{
    public function handle(Request $r, callable $next, ?string $arg = null): Response
    {
        $res = $next($r);
        $res->headers += [
            'X-Frame-Options' => 'DENY',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'same-origin',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=()',
            'Content-Security-Policy' => "default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline'; script-src 'self'; frame-ancestors 'none'; form-action 'self'",
        ];
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') { $res->headers['Strict-Transport-Security'] = 'max-age=31536000'; }
        return $res;
    }
}
