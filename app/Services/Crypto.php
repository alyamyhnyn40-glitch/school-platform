<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\Env;

/** تشفير الحقول الحساسة بـ sodium (مفتاح APP_ENC_KEY بصيغة base64 في .env). */
final class Crypto
{
    private static function key(): string
    {
        $k = base64_decode((string)Env::get('APP_ENC_KEY', ''), true);
        if ($k === false || strlen($k) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES) { throw new \RuntimeException('APP_ENC_KEY غير صالح'); }
        return $k;
    }
    public static function generateKey(): string { return base64_encode(random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES)); }
    public static function encrypt(?string $plain): ?string
    {
        if ($plain === null || $plain === '') { return null; }
        $n = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        return base64_encode($n . sodium_crypto_secretbox($plain, $n, self::key()));
    }
    public static function decrypt(?string $cipher): ?string
    {
        if ($cipher === null || $cipher === '') { return null; }
        $raw = base64_decode($cipher, true);
        if ($raw === false || strlen($raw) < SODIUM_CRYPTO_SECRETBOX_NONCEBYTES + 16) { return null; }
        $p = sodium_crypto_secretbox_open(substr($raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES), substr($raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES), self::key());
        return $p === false ? null : $p;
    }
}
