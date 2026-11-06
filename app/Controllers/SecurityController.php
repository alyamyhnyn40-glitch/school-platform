<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\{Auth, Request, Response, Tenant, View};
use App\Services\SecurityLog as L;

/** عرض سجل أحداث الأمان: للمشغّل (كل المنصة) ولمدير المدرسة (مدرسته فقط). */
final class SecurityController
{
    private function page(Request $r, ?int $school, string $title): Response
    {
        $f = ['type' => (string)$r->query('type', ''), 'q' => (string)$r->query('q', ''), 'from' => (string)$r->query('from', ''), 'to' => (string)$r->query('to', ''), 'perm' => (string)$r->query('perm', ''), 'school' => (int)$r->query('school', 0)];
        return Response::html(View::render('shared/security', ['title' => $title, 'rows' => L::search($f, $school), 'sum' => L::summary($school), 'f' => $f, 'platform' => $school === null]));
    }
    public function platform(Request $r): Response { return $this->page($r, null, 'أمان المنصة وأخطاء الصلاحيات'); }
    public function school(Request $r): Response { return $this->page($r, Tenant::require(), 'أمان المدرسة وأخطاء الصلاحيات'); }
}
