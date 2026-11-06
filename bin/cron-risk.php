<?php
// php bin/cron-risk.php — يفحص مؤشرات الإنذار المبكر لكل المدارس الفعّالة المشتركة في وحدة extended. جدولة يومية:  15 5 * * * php /path/bin/cron-risk.php
declare(strict_types=1);
require dirname(__DIR__) . '/vendor/autoload.php';
use App\Core\{DB, Env, Logger};
use App\Services\{RiskService, SubscriptionService};
Env::load();
$done = 0;
foreach (DB::all("SELECT id FROM schools WHERE status = 'active'") as $s) {
    try { if (!SubscriptionService::moduleEnabled((int)$s['id'], 'extended')) { continue; } $r = RiskService::scanSystem((int)$s['id']); $done++; echo "school {$s['id']}: students={$r['students']} new={$r['flags']} cleared={$r['cleared']}\n"; }
    catch (Throwable $e) { Logger::error('risk cron failed', ['school' => $s['id'], 'e' => $e->getMessage()]); echo "school {$s['id']}: FAILED {$e->getMessage()}\n"; }
}
echo date('c') . " schools=$done\n";
