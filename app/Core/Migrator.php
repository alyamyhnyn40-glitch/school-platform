<?php
declare(strict_types=1);
namespace App\Core;

/** يشغّل ملفات database/migrations/*.sql بالترتيب مرة واحدة لكل ملف. */
final class Migrator
{
    public static function run(?string $dir = null): array
    {
        $dir ??= base_path('database/migrations');
        $pdo = DB::pdo();
        $pdo->exec('CREATE TABLE IF NOT EXISTS migrations (name VARCHAR(190) PRIMARY KEY, ran_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB');
        $done = array_column(DB::all('SELECT name FROM migrations'), 'name');
        $files = glob($dir . '/*.sql') ?: [];
        sort($files);
        $ran = [];
        foreach ($files as $f) {
            $name = basename($f);
            if (in_array($name, $done, true)) { continue; }
            // DDL يعمل commit ضمنيًا في MySQL؛ نشغّل كل ملف دفعة واحدة
            $pdo->exec((string)file_get_contents($f));
            DB::query('INSERT INTO migrations (name) VALUES (?)', [$name]);
            $ran[] = $name;
        }
        return $ran;
    }
}
