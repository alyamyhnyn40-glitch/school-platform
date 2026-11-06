<?php
declare(strict_types=1);
namespace App\Core;

/** سياق المدرسة الحالية: يُحدد من الجلسة فقط (لا من المدخلات). */
final class Tenant
{
    private static ?int $override = null; // للاختبارات وسكربتات CLI

    public static function id(): ?int
    {
        if (self::$override !== null) { return self::$override; }
        $id = Session::get('school_id');
        return $id === null ? null : (int)$id;
    }
    public static function require(): int
    {
        $id = self::id();
        if ($id === null) { throw new TenantException('لا يوجد سياق مدرسة لهذا الاستعلام'); }
        return $id;
    }
    public static function setForCli(?int $id): void { self::$override = $id; }
}
