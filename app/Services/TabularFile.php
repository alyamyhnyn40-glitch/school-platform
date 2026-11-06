<?php
declare(strict_types=1);
namespace App\Services;

/** يقرأ CSV أو XLSX إلى مصفوفة صفوف (مع كشف الترميز والفاصل). */
final class TabularFile
{
    public static function read(string $path, string $ext): array
    {
        if ($ext === 'xlsx') { return self::clean(XlsxReader::read($path)); }
        $raw = (string)file_get_contents($path);
        if (str_starts_with($raw, "\xEF\xBB\xBF")) { $raw = substr($raw, 3); }
        if (!mb_check_encoding($raw, 'UTF-8')) {   // ملفات Excel العربية القديمة (Windows-1256؛ mbstring لا يدعمه فنستخدم iconv)
            $conv = function_exists('iconv') ? @iconv('CP1256', 'UTF-8//IGNORE', $raw) : false;
            if ($conv === false || $conv === '') { throw new \RuntimeException('ترميز الملف غير مدعوم. احفظه بصيغة UTF-8 أو xlsx.'); }
            $raw = $conv;
        }
        $first = strtok($raw, "\n") ?: '';
        $delim = ','; $best = 0;
        foreach ([',', ';', "\t"] as $d) { $c = substr_count($first, $d); if ($c > $best) { $best = $c; $delim = $d; } }
        $rows = []; $fh = fopen('php://temp', 'r+'); fwrite($fh, $raw); rewind($fh);
        while (($r = fgetcsv($fh, 0, $delim, '"', '\\')) !== false) { $rows[] = array_map(fn($v) => trim((string)$v), $r); if (count($rows) > 20000) { break; } }
        fclose($fh);
        return self::clean($rows);
    }
    /** يحذف الصفوف الفارغة كليًا. */
    private static function clean(array $rows): array
    {
        return array_values(array_filter($rows, fn($r) => array_filter($r, fn($v) => trim((string)$v) !== '') !== []));
    }
    /** توحيد نص للمقارنة (إزالة التشكيل والتطويل وتوحيد الهمزات وألف المقصورة). */
    public static function norm(string $s): string
    {
        $s = preg_replace('/[\x{064B}-\x{065F}\x{0640}]/u', '', $s) ?? $s;
        $s = strtr($s, ['أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا', 'ى' => 'ي', 'ة' => 'ه']);
        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', $s) ?? $s));
    }
}
