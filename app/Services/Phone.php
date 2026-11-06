<?php
declare(strict_types=1);
namespace App\Services;

final class Phone
{
    /** توحيد صيغة الأرقام إلى +E.164. الافتراضي مفتاح اليمن +967 (قابل للتهيئة). */
    public static function normalize(string $raw, string $cc = '967'): string
    {
        $s = preg_replace('/[^\d+]/', '', strtr($raw, ['٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9'])) ?? '';
        if (str_starts_with($s, '00')) { $s = '+' . substr($s, 2); }
        if (str_starts_with($s, '+')) { return $s; }
        if (str_starts_with($s, '0')) { $s = substr($s, 1); }
        return '+' . $cc . $s;
    }
    public static function looksLikePhone(string $s): bool { return (bool)preg_match('/^[\d+\s٠-٩-]{7,}$/u', trim($s)); }
}
