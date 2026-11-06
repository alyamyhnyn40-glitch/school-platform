<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\DB;

/** إعدادات على مستوى المنصة (جدول settings بـ school_id = NULL). القيم السرية تُشفَّر بمفتاح التطبيق. */
final class PlatformSettings
{
    public static function get(string $key, ?string $default = null, bool $secret = false): ?string
    {
        $v = DB::value('SELECT svalue FROM settings WHERE school_id IS NULL AND skey = ?', [$key]);
        if ($v === null || $v === false) { return $default; }
        return $secret ? (Crypto::decrypt((string)$v) ?? $default) : (string)$v;
    }
    public static function set(string $key, ?string $value, bool $secret = false): void
    {
        $store = ($value === null || $value === '') ? null : ($secret ? Crypto::encrypt($value) : $value);
        $id = DB::value('SELECT id FROM settings WHERE school_id IS NULL AND skey = ?', [$key]);
        if ($id) { DB::query('UPDATE settings SET svalue = ? WHERE id = ?', [$store, $id]); }
        else { DB::query('INSERT INTO settings (school_id, skey, svalue) VALUES (NULL, ?, ?)', [$key, $store]); }
    }
    public static function has(string $key): bool { return DB::value('SELECT 1 FROM settings WHERE school_id IS NULL AND skey = ? AND svalue IS NOT NULL', [$key]) !== null; }
}
