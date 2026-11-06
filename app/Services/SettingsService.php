<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\{DB, Tenant};

/** إعدادات لكل مدرسة (جدول settings). */
final class SettingsService
{
    public static function get(string $key, ?string $default = null): ?string
    {
        $v = DB::value('SELECT svalue FROM settings WHERE school_id = ? AND skey = ?', [Tenant::require(), $key]);
        return $v ?? $default;
    }
    public static function set(string $key, ?string $value): void
    {
        DB::query('INSERT INTO settings (school_id, skey, svalue) VALUES (?,?,?) ON DUPLICATE KEY UPDATE svalue = VALUES(svalue)', [Tenant::require(), $key, $value]);
    }
}
