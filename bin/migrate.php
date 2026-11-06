<?php
declare(strict_types=1);
require dirname(__DIR__) . '/vendor/autoload.php';
App\Core\Env::load();
$ran = App\Core\Migrator::run();
echo $ran ? "تم تنفيذ: " . implode(', ', $ran) . "\n" : "لا توجد migrations جديدة\n";
echo "صلاحيات مضافة لمديري المدارس: " . App\Services\SeedService::syncAdminRoles() . "\n";
