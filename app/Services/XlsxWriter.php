<?php
declare(strict_types=1);
namespace App\Services;

/** مولّد XLSX مبسّط (نصوص وأرقام، اتجاه RTL) بلا مكتبات خارجية، مع قوائم منسدلة اختيارية (ورقة «قوائم» مخفية + تحقق البيانات). */
final class XlsxWriter
{
    /**
     * @param array $rows مصفوفة صفوف؛ الصف الأول رؤوس. يعيد محتوى الملف الثنائي.
     * @param array $lists [رقم العمود => ['values' => [...], 'multi' => bool]] — قائمة منسدلة للعمود؛ multi تسمح بكتابة عدة قيم مفصولة بـ «،».
     */
    public static function build(array $rows, string $sheetName = 'تقرير', array $lists = []): string
    {
        $col = function (int $i): string { $s = ''; for ($i++; $i > 0; $i = intdiv($i - 1, 26)) { $s = chr(65 + ($i - 1) % 26) . $s; } return $s; };
        $x = fn(string $v): string => htmlspecialchars(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $v), ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $sheet = '';
        foreach ($rows as $r => $row) {
            $sheet .= '<row r="' . ($r + 1) . '">';
            foreach (array_values($row) as $c => $v) {
                $ref = $col($c) . ($r + 1); $style = $r === 0 ? ' s="1"' : '';
                if ($v === null || $v === '') { $sheet .= "<c r=\"$ref\"$style/>"; }
                elseif (is_int($v) || is_float($v)) { $sheet .= "<c r=\"$ref\"$style><v>$v</v></c>"; }
                else { $sheet .= "<c r=\"$ref\"$style t=\"inlineStr\"><is><t xml:space=\"preserve\">" . $x((string)$v) . '</t></is></c>'; }
            }
            $sheet .= '</row>';
        }
        // ورقة القوائم والتحقق
        $dv = ''; $listSheet = ''; $lc = 0; $listRows = [];
        foreach ($lists as $colIdx => $spec) {
            $vals = array_values(array_unique(array_filter(array_map('strval', $spec['values'] ?? []), fn($v) => $v !== ''))); if (!$vals) { continue; }
            $L = $col($lc); foreach ($vals as $i => $v) { $listRows[$i][$lc] = $v; }
            $range = "'قوائم'!\$" . $L . '$1:$' . $L . '$' . count($vals); $multi = !empty($spec['multi']);
            $dv .= '<dataValidation type="list" allowBlank="1" showInputMessage="1" showErrorMessage="' . ($multi ? '0' : '1') . '"' . ($multi ? ' promptTitle="عدة قيم" prompt="اختر من القائمة أو اكتب أكثر من قيمة مفصولة بالفاصلة ،"' : ' errorTitle="قيمة غير صالحة" error="اختر قيمة من القائمة"') . ' sqref="' . $col((int)$colIdx) . '2:' . $col((int)$colIdx) . '2000"><formula1>' . $x($range) . '</formula1></dataValidation>';
            $lc++;
        }
        if ($dv !== '') {
            ksort($listRows);
            foreach ($listRows as $r => $cells) { $listSheet .= '<row r="' . ($r + 1) . '">'; ksort($cells); foreach ($cells as $c => $v) { $listSheet .= '<c r="' . $col($c) . ($r + 1) . '" t="inlineStr"><is><t xml:space="preserve">' . $x($v) . '</t></is></c>'; } $listSheet .= '</row>'; }
            $dv = '<dataValidations count="' . substr_count($dv, '<dataValidation ') . '">' . $dv . '</dataValidations>';
        }
        $files = [
            '[Content_Types].xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>' . ($dv !== '' ? '<Override PartName="/xl/worksheets/sheet2.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>' : '') . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>',
            '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>',
            'xl/workbook.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="' . $x(mb_substr(preg_replace('/[\\\\\/\?\*\[\]:]/u', ' ', $sheetName), 0, 31)) . '" sheetId="1" r:id="rId1"/>' . ($dv !== '' ? '<sheet name="قوائم" sheetId="2" state="hidden" r:id="rId3"/>' : '') . '</sheets></workbook>',
            'xl/_rels/workbook.xml.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>' . ($dv !== '' ? '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet2.xml"/>' : '') . '</Relationships>',
            'xl/styles.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts><fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills><borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/></cellXfs></styleSheet>',
            'xl/worksheets/sheet1.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetViews><sheetView rightToLeft="1" workbookViewId="0"/></sheetViews><sheetFormatPr defaultRowHeight="15"/><cols><col min="1" max="20" width="18" customWidth="1"/></cols><sheetData>' . $sheet . '</sheetData>' . $dv . '</worksheet>',
        ];
        if ($dv !== '') { $files['xl/worksheets/sheet2.xml'] = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>' . $listSheet . '</sheetData></worksheet>'; }
        $tmp = tempnam(sys_get_temp_dir(), 'xlsx'); $zip = new \ZipArchive(); $zip->open($tmp, \ZipArchive::OVERWRITE);
        foreach ($files as $n => $c) { $zip->addFromString($n, $c); }
        $zip->close(); $bin = (string)file_get_contents($tmp); @unlink($tmp);
        return $bin;
    }
}
