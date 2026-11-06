<?php
declare(strict_types=1);
namespace App\Core;

final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) { return; }
        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || Env::get('SESSION_SECURE') === '1';
        session_name('SCHSESS');
        session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => $secure, 'httponly' => true, 'samesite' => 'Lax']);
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        $dir = storage_path('cache/sessions');
        if (!is_dir($dir)) { @mkdir($dir, 0770, true); }
        if (is_writable($dir)) { session_save_path($dir); }
        session_start();
        // مهلة خمول (دقائق) – D-18
        $idle = (int)Env::get('SESSION_IDLE_MINUTES', 30) * 60;
        if (isset($_SESSION['_last']) && time() - $_SESSION['_last'] > $idle) {
            $_SESSION = []; self::regenerate();
        }
        $_SESSION['_last'] = time();
    }

    public static function get(string $k, mixed $d = null): mixed { return $_SESSION[$k] ?? $d; }
    public static function set(string $k, mixed $v): void { $_SESSION[$k] = $v; }
    public static function forget(string $k): void { unset($_SESSION[$k]); }
    public static function regenerate(): void { if (session_status() === PHP_SESSION_ACTIVE) { session_regenerate_id(true); } }
    public static function destroy(): void
    {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) { session_regenerate_id(true); session_destroy(); }
    }
    public static function flash(string $k, mixed $v): void { $_SESSION['_flash'][$k] = $v; }
    public static function getFlash(string $k, mixed $d = []): mixed
    {
        // القراءة لا تحذف داخل نفس الطلب؛ الحذف عند flashSweep
        return $_SESSION['_flash_now'][$k] ?? $d;
    }
    /** يُستدعى في بداية كل طلب: يحوّل flash إلى flash_now ويفرغ القديم. */
    public static function flashSweep(): void
    {
        $_SESSION['_flash_now'] = $_SESSION['_flash'] ?? [];
        $_SESSION['_flash'] = [];
    }
}
