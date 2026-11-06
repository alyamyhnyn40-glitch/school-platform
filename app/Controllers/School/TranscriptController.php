<?php
declare(strict_types=1);
namespace App\Controllers\School;

use App\Core\{DB, Request, Response, Tenant, View};
use App\Services\{TranscriptService as T, StudentService, PortalService};

final class TranscriptController
{
    public function show(Request $r): Response { $t = T::forStudent((int)$r->params['id']); return Response::html(View::render('school/students/transcript', ['title' => 'السجل الأكاديمي', 't' => $t])); }
    public function printPage(Request $r): Response
    {
        $t = T::forStudent((int)$r->params['id']); \App\Services\AuditService::log('transcript.print', 'student', (int)$r->params['id']);
        return Response::html(View::render('school/students/transcript_print', ['title' => 'السجل الأكاديمي', 't' => $t, 'school' => DB::one('SELECT name, city FROM schools WHERE id = ?', [Tenant::require()])], 'print'));
    }
    /** بحث سريع عن طالب لفتح سجله. */
    public function index(Request $r): Response
    {
        $q = trim((string)$r->query('q', '')); $rows = $q !== '' ? StudentService::search(1, 30, $q, null, null, '')['rows'] : [];
        return Response::html(View::render('school/students/transcripts', ['title' => 'السجل الأكاديمي', 'q' => $q, 'rows' => $rows]));
    }
    public function portal(Request $r): Response
    {
        $id = (int)$r->params['id']; PortalService::assertStudent($id); $t = T::forPortal($id);
        return Response::html(View::render('portal/transcript', ['title' => 'السجل الأكاديمي', 't' => $t]));
    }
    /** تجميد نتائج سنة يدويًا (مثلًا بعد تصحيح درجات سنة مغلقة). */
    public function freeze(Request $r): Response { $n = T::freezeYear((int)$r->params['id']); return Response::ok(['message' => "جُمِّد السجل الأكاديمي لـ $n طالب في هذه السنة."]); }
}
