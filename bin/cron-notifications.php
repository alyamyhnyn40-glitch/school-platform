<?php
// php bin/cron-notifications.php — يعالج طابور الرسائل (SMS وواتساب وبريد). جدولة كل دقيقة:  * * * * * php /path/bin/cron-notifications.php
declare(strict_types=1);
require dirname(__DIR__) . '/vendor/autoload.php';
use App\Core\Env;
use App\Services\NotificationQueueService;
Env::load();
$r = NotificationQueueService::process(100);
echo date('c') . " sent={$r['sent']} failed={$r['failed']}\n";
