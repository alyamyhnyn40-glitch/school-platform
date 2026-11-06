<?php
declare(strict_types=1);
namespace App\Services;

/** قارئ XLSX مبسّط (الورقة الأولى): نصوص مشتركة/مضمنة وأرقام. يعيد مصفوفة صفوف من نصوص. */
final class XlsxReader
{
    private const MAX_XML = 30 * 1024 * 1024;

    public static function read(string $path): array
    {
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) { throw new \RuntimeException('ملف Excel غير صالح'); }
        $get = function (string $name) use ($zip): ?string {
            $st = $zip->statName($name); if (!$st) { return null; }
            if ($st['size'] > self::MAX_XML) { throw new \RuntimeException('الملف كبير جدًا'); }   // حماية من zip bomb
            return $zip->getFromName($name) ?: null;
        };
        $shared = [];
        if ($xml = $get('xl/sharedStrings.xml')) {
            $d = self::dom($xml);
            foreach ($d->getElementsByTagName('si') as $si) { $t = ''; foreach ($si->getElementsByTagName('t') as $n) { $t .= $n->textContent; } $shared[] = $t; }
        }
        $sheetPath = 'xl/worksheets/sheet1.xml';
        if (($wb = $get('xl/workbook.xml')) && ($rels = $get('xl/_rels/workbook.xml.rels'))) {   // الورقة الأولى الفعلية
            $w = self::dom($wb); $first = $w->getElementsByTagName('sheet')->item(0);
            if ($first) {
                $rid = $first->getAttributeNS('http://schemas.openxmlformats.org/officeDocument/2006/relationships', 'id');
                foreach (self::dom($rels)->getElementsByTagName('Relationship') as $r) { if ($r->getAttribute('Id') === $rid) { $t = $r->getAttribute('Target'); $sheetPath = str_starts_with($t, '/') ? ltrim($t, '/') : 'xl/' . $t; } }   // الهدف مطلق أو نسبي
            }
        }
        $xml = $get($sheetPath) ?? throw new \RuntimeException('لم يُعثر على ورقة بيانات');
        $zip->close();
        $rows = [];
        foreach (self::dom($xml)->getElementsByTagName('row') as $row) {
            $r = []; $max = -1;
            foreach ($row->getElementsByTagName('c') as $c) {
                $ref = $c->getAttribute('r'); $idx = self::colIndex($ref);
                $t = $c->getAttribute('t'); $v = '';
                if ($t === 'inlineStr') { foreach ($c->getElementsByTagName('t') as $n) { $v .= $n->textContent; } }
                else { $vn = $c->getElementsByTagName('v')->item(0); $v = $vn ? $vn->textContent : ''; if ($t === 's') { $v = $shared[(int)$v] ?? ''; } }
                $r[$idx] = trim($v); $max = max($max, $idx);
            }
            $out = []; for ($i = 0; $i <= $max; $i++) { $out[] = $r[$i] ?? ''; }
            $rows[] = $out;
            if (count($rows) > 20000) { throw new \RuntimeException('عدد الصفوف كبير جدًا'); }
        }
        return $rows;
    }
    private static function dom(string $xml): \DOMDocument
    {
        $d = new \DOMDocument();
        if (!$d->loadXML($xml, LIBXML_NONET | LIBXML_COMPACT)) { throw new \RuntimeException('XML غير صالح'); }
        return $d;
    }
    private static function colIndex(string $ref): int
    {
        preg_match('/^([A-Z]+)/', $ref, $m); $n = 0;
        foreach (str_split($m[1] ?? 'A') as $ch) { $n = $n * 26 + (ord($ch) - 64); }
        return $n - 1;
    }
}
