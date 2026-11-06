<?php
declare(strict_types=1);
namespace App\Controllers\Platform;

use App\Core\{Auth, DB, Request, Response, View};

final class AuditController
{
    public function index(Request $r): Response
    {
        return Response::html(View::render('platform/audit', ['title' => __t('nav.audit'), 'schools' => DB::all('SELECT id, name FROM schools WHERE deleted_at IS NULL ORDER BY name'), 'scripts' => ['datatable.js', 'platform-audit.js']]));
    }
    public function api(Request $r): Response
    {
        Auth::authorize('platform.audit');
        $page = max(1, (int)$r->query('page', 1)); $per = 25;
        $w = '1=1'; $p = [];
        if ($r->query('school_id')) { $w .= ' AND a.school_id = ?'; $p[] = (int)$r->query('school_id'); }
        if ($r->query('action')) { $w .= ' AND a.action LIKE ?'; $p[] = addcslashes((string)$r->query('action'), '%_\\') . '%'; }
        if ($r->query('from')) { $w .= ' AND a.created_at >= ?'; $p[] = (string)$r->query('from') . ' 00:00:00'; }
        if ($r->query('to')) { $w .= ' AND a.created_at <= ?'; $p[] = (string)$r->query('to') . ' 23:59:59'; }
        $total = (int)DB::value("SELECT COUNT(*) FROM audit_logs a WHERE $w", $p);
        $rows = DB::all("SELECT a.id, a.created_at, a.action, a.entity, a.entity_id, a.old_values, a.new_values, a.ip, a.via_support, u.full_name AS user_name, s.name AS school_name
            FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id LEFT JOIN schools s ON s.id = a.school_id WHERE $w ORDER BY a.id DESC LIMIT $per OFFSET " . (($page - 1) * $per), $p);
        return Response::ok($rows, ['page' => $page, 'per_page' => $per, 'total' => $total, 'pages' => (int)ceil($total / $per)]);
    }
}
