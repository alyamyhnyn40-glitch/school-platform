<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\{Request, Response, View};
use App\Services\SchoolBackupService as B;

final class BackupController
{
    public function index(Request $r): Response { return Response::html(View::render('school/backup', ['title' => 'النسخ الاحتياطي', 'rows' => B::list(), 'cfg' => B::config(), 'scripts' => ['backup.js']])); }
    public function create(Request $r): Response { $b = B::makeNow($r->all()); return Response::ok(['message' => 'تم إنشاء النسخة: ' . $b['filename'] . ($b['cloud_status'] === 'ok' ? ' ورُفعت للسحابة' : '')]); }
    public function saveConfig(Request $r): Response { B::saveConfig($r->all()); return Response::ok(['message' => 'حُفظت إعدادات النسخ الاحتياطي']); }
    public function testCloud(Request $r): Response { B::testCloud(); return Response::ok(['message' => 'نجح الاتصال بالتخزين السحابي']); }
    public function upload(Request $r): Response { B::uploadNow((int)$r->params["id"]); return Response::ok(['message' => 'رُفعت النسخة إلى السحابة']); }
    public function delete(Request $r): Response { B::remove((int)$r->params["id"]); return Response::ok(['message' => 'حُذفت النسخة']); }
    public function download(Request $r): Response
    {
        [$path, $name] = B::downloadPath((int)$r->params["id"]);
        return new Response((string)file_get_contents($path), 200, ['Content-Type' => 'application/zip', 'Content-Disposition' => 'attachment; filename="' . $name . '"', 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store']);
    }
}
