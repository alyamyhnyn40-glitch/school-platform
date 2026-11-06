<?php
declare(strict_types=1);
namespace App\Core;

final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['_csrf'])) { $_SESSION['_csrf'] = bin2hex(random_bytes(32)); }
        return $_SESSION['_csrf'];
    }
    public static function check(?string $given): bool
    {
        return is_string($given) && $given !== '' && hash_equals(self::token(), $given);
    }
}
