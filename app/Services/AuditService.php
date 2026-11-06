<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\{Auth, DB, Session, Tenant};

/** سجل التدقيق: من، متى، القيمة القديمة والجديدة. */
final class AuditService
{
    public static function log(string $action, string $entity, ?int $entityId = null, ?array $old = null, ?array $new = null, ?int $schoolId = null): void
    {
        DB::query('INSERT INTO audit_logs (school_id, user_id, action, entity, entity_id, old_values, new_values, ip, user_agent, via_support) VALUES (?,?,?,?,?,?,?,?,?,?)', [
            $schoolId ?? Tenant::id(), Auth::id(), $action, $entity, $entityId,
            $old === null ? null : json_encode($old, JSON_UNESCAPED_UNICODE),
            $new === null ? null : json_encode($new, JSON_UNESCAPED_UNICODE),
            $_SERVER['REMOTE_ADDR'] ?? null, substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            Session::get('support_mode') ? 1 : 0,
        ]);
    }
    /** يعيد الحقول التي تغيرت فقط (قديم/جديد). */
    public static function diff(array $old, array $new): array
    {
        $o = $n = [];
        foreach ($new as $k => $v) { if (!array_key_exists($k, $old) || (string)$old[$k] !== (string)$v) { $o[$k] = $old[$k] ?? null; $n[$k] = $v; } }
        return [$o, $n];
    }
}
