<?php
declare(strict_types=1);
namespace App\Middleware;

use App\Core\{DB, HttpException, Request, Response};

/** الاستخدام: RateLimitMiddleware::class.':login,10,300' = اسم، أقصى محاولات، نافذة بالثواني (لكل IP). */
final class RateLimitMiddleware
{
    public function handle(Request $r, callable $next, ?string $arg = null): Response
    {
        [$name, $max, $win] = array_pad(explode(',', (string)$arg), 3, null);
        if (!self::hit("$name:" . $r->ip(), (int)($max ?: 10), (int)($win ?: 300))) {
            throw new HttpException(429, 'محاولات كثيرة، انتظر قليلًا ثم أعد المحاولة');
        }
        return $next($r);
    }

    /** يعيد false إذا تجاوز الحد. */
    public static function hit(string $key, int $max, int $windowSec): bool
    {
        return (bool)DB::transaction(function () use ($key, $max, $windowSec) {
            $row = DB::one('SELECT hits, window_start FROM rate_limits WHERE rkey = ? FOR UPDATE', [$key]);
            $now = time();
            if (!$row || strtotime($row['window_start'] . ' UTC') + $windowSec < $now) {
                DB::query('REPLACE INTO rate_limits (rkey, hits, window_start) VALUES (?, 1, UTC_TIMESTAMP())', [$key]);
                return true;
            }
            if ((int)$row['hits'] >= $max) { return false; }
            DB::query('UPDATE rate_limits SET hits = hits + 1 WHERE rkey = ?', [$key]);
            return true;
        });
    }
}
