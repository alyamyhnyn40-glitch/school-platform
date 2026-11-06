<?php
declare(strict_types=1);
namespace App\Core;

/** كل النصوص من ملفات lang/{locale}.php بمفاتيح منقّطة. */
final class Lang
{
    private static array $cache = [];
    public static string $locale = 'ar';

    public static function get(string $key, array $vars = []): string
    {
        $all = self::$cache[self::$locale] ??= (is_file($f = base_path('lang/' . self::$locale . '.php')) ? require $f : []);
        if ($key === 'app.name') { return \App\Services\Brand::name(); }
        if ($key === 'app.tagline') { return \App\Services\Brand::get('tagline') ?: ($all[$key] ?? $key); }
        $text = $all[$key] ?? $key;
        foreach ($vars as $k => $v) { $text = str_replace(':' . $k, (string)$v, $text); }
        return $text;
    }
}
