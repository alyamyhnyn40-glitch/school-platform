<?php
declare(strict_types=1);
namespace App\Core;

use PDO;

/** اتصال PDO وحيد. كل الاستعلامات prepared. */
final class DB
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                Env::get('DB_HOST', '127.0.0.1'), Env::get('DB_PORT', '3306'), Env::get('DB_NAME', 'school_platform'));
            self::$pdo = new PDO($dsn, (string)Env::get('DB_USER', 'root'), (string)Env::get('DB_PASS', ''), [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            self::$pdo->exec("SET time_zone = '" . (new \DateTimeImmutable('now'))->format('P') . "'");   // نفس توقيت التطبيق: NOW() وCURRENT_TIMESTAMP محليان
        }
        return self::$pdo;
    }

    public static function reset(): void { self::$pdo = null; }

    public static function query(string $sql, array $params = []): \PDOStatement
    {
        $st = self::pdo()->prepare($sql);
        $st->execute($params);
        return $st;
    }
    public static function all(string $sql, array $params = []): array { return self::query($sql, $params)->fetchAll(); }
    public static function one(string $sql, array $params = []): ?array { $r = self::query($sql, $params)->fetch(); return $r ?: null; }
    public static function value(string $sql, array $params = []): mixed { $r = self::query($sql, $params)->fetchColumn(); return $r === false ? null : $r; }
    public static function lastId(): int { return (int)self::pdo()->lastInsertId(); }

    private static int $depth = 0;

    /** يدعم التداخل عبر SAVEPOINT (الخدمات قد تستدعي بعضها داخل transaction). */
    public static function transaction(callable $fn): mixed
    {
        $pdo = self::pdo();
        $outer = !$pdo->inTransaction();
        $sp = 'sp_' . (++self::$depth);
        $outer ? $pdo->beginTransaction() : $pdo->exec("SAVEPOINT $sp");
        try {
            $r = $fn();
            $outer ? $pdo->commit() : $pdo->exec("RELEASE SAVEPOINT $sp");
            return $r;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) { $outer ? $pdo->rollBack() : $pdo->exec("ROLLBACK TO SAVEPOINT $sp"); }
            throw $e;
        } finally { self::$depth--; }
    }
}
