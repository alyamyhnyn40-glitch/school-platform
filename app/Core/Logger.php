<?php
declare(strict_types=1);
namespace App\Core;

final class Logger
{
    public static function log(string $level, string $msg, array $ctx = []): void
    {
        $dir = storage_path('logs');
        if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
        $line = sprintf("[%s] %s: %s %s\n", date('c'), strtoupper($level), $msg, $ctx ? json_encode($ctx, JSON_UNESCAPED_UNICODE) : '');
        @file_put_contents($dir . '/app-' . date('Y-m-d') . '.log', $line, FILE_APPEND | LOCK_EX);
    }
    public static function error(string $m, array $c = []): void { self::log('error', $m, $c); }
    public static function info(string $m, array $c = []): void { self::log('info', $m, $c); }
}
