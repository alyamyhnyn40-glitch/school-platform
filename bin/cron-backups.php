<?php
// php bin/cron-backups.php — ينشئ النسخ الاحتياطية التلقائية للمدارس التي فعّلتها (يومي/أسبوعي). جدولة: 30 2 * * * php /path/bin/cron-backups.php
declare(strict_types=1);
require dirname(__DIR__) . '/vendor/autoload.php';
App\Core\Env::load();
foreach (App\Services\SchoolBackupService::runDue() ?: ['لا توجد نسخ مستحقة'] as $l) { echo date('c') . ' ' . $l . "\n"; }
