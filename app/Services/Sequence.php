<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\{DB, Tenant};

/** ترقيم تسلسلي لكل مدرسة وسنة (سندات/فواتير). يجب استدعاؤه داخل transaction الإنشاء فيكون الترقيم بلا فجوات عند التراجع. */
final class Sequence
{
    public static function next(string $name, ?int $year = null): int
    {
        $sid = Tenant::require(); $year ??= (int)date('Y');
        if (!DB::pdo()->inTransaction()) { throw new \LogicException('Sequence::next يتطلب transaction'); }
        DB::query('INSERT IGNORE INTO counters (school_id, name, year, value) VALUES (?,?,?,0)', [$sid, $name, $year]);
        DB::query('UPDATE counters SET value = value + 1 WHERE school_id = ? AND name = ? AND year = ?', [$sid, $name, $year]);
        return (int)DB::value('SELECT value FROM counters WHERE school_id = ? AND name = ? AND year = ?', [$sid, $name, $year]);
    }
    public static function format(string $prefix, int $year, int $n): string { return sprintf('%s-%d-%06d', $prefix, $year, $n); }
}
