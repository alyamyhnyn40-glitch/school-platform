<?php
// php bin/backup.php  — نسخة احتياطية: قاعدة البيانات (PHP خالص، لا يحتاج mysqldump) + ملفات storage/uploads
// جدولة (cron يومي 2:30):  30 2 * * * php /path/bin/backup.php >> /path/storage/logs/backup.log 2>&1
declare(strict_types=1);
require dirname(__DIR__) . '/vendor/autoload.php';
use App\Core\{DB, Env};
Env::load();
$keepDays = (int)Env::get('BACKUP_KEEP_DAYS', 14);
$dir = storage_path('backups'); if (!is_dir($dir)) { mkdir($dir, 0770, true); }
$stamp = date('Ymd-His');
$sql = "$dir/db-$stamp.sql";
$fh = fopen($sql, 'w');
$pdo = DB::pdo();
fwrite($fh, "-- نسخة احتياطية " . date('c') . "\nSET FOREIGN_KEY_CHECKS=0;\nSET NAMES utf8mb4;\n");
foreach ($pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $t) {
    fwrite($fh, "\nDROP TABLE IF EXISTS `$t`;\n" . $pdo->query("SHOW CREATE TABLE `$t`")->fetch(PDO::FETCH_NUM)[1] . ";\n");
    $st = $pdo->query("SELECT * FROM `$t`", PDO::FETCH_NUM);
    $batch = [];
    while ($row = $st->fetch()) {
        $batch[] = '(' . implode(',', array_map(fn($v) => $v === null ? 'NULL' : $pdo->quote((string)$v), $row)) . ')';
        if (count($batch) >= 200) { fwrite($fh, "INSERT INTO `$t` VALUES " . implode(',', $batch) . ";\n"); $batch = []; }
    }
    if ($batch) { fwrite($fh, "INSERT INTO `$t` VALUES " . implode(',', $batch) . ";\n"); }
}
fwrite($fh, "SET FOREIGN_KEY_CHECKS=1;\n"); fclose($fh);

$zipPath = "$dir/backup-$stamp.zip";
$zip = new ZipArchive();
$zip->open($zipPath, ZipArchive::CREATE);
$zip->addFile($sql, "db-$stamp.sql");
$up = storage_path('uploads');
if (is_dir($up)) {
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($up, FilesystemIterator::SKIP_DOTS)) as $f) {
        if ($f->isFile() && $f->getFilename() !== '.gitkeep') { $zip->addFile($f->getPathname(), 'uploads/' . substr($f->getPathname(), strlen($up) + 1)); }
    }
}
$zip->close(); unlink($sql);
chmod($zipPath, 0640);
foreach (glob("$dir/backup-*.zip") ?: [] as $old) { if (filemtime($old) < time() - $keepDays * 86400) { unlink($old); } }
echo date('c') . " تم: $zipPath (" . round(filesize($zipPath) / 1024) . " KB)\n";
