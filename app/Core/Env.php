<?php
declare(strict_types=1);
namespace App\Core;

/** قارئ ملف .env بسيط. */
final class Env
{
    private static array $vars = [];
    private static bool $loaded = false;

    public static function load(?string $file = null): void
    {
        $file ??= base_path('.env');
        self::$loaded = true;
        if (!is_file($file)) { return; }
        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) { continue; }
            [$k, $v] = explode('=', $line, 2);
            self::$vars[trim($k)] ??= trim(trim($v), "\"'"); // القيم المضبوطة برمجيًا لها الأولوية
        }
        // نفس توقيت التطبيق في سكربتات CLI (cron/migrate/backup)؛ وإلا يختلف NOW() عن توقيت الويب فتتأخر الرسائل المجدولة ساعات
        $tz = self::$vars['APP_TIMEZONE'] ?? null;
        if ($tz && in_array($tz, \DateTimeZone::listIdentifiers(), true)) { date_default_timezone_set($tz); }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        if (!self::$loaded) { self::load(); }
        return $_ENV[$key] ?? self::$vars[$key] ?? (getenv($key) !== false ? getenv($key) : $default);
    }

    public static function set(string $key, string $value): void { self::$vars[$key] = $value; }
    public static function isProduction(): bool { return self::get('APP_ENV', 'production') === 'production'; }
}
