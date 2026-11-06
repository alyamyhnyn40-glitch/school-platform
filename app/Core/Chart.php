<?php
declare(strict_types=1);
namespace App\Core;

/** رسوم بيانية SVG تُولَّد في الخادم (بلا مكتبات ولا JavaScript، تعمل مع CSP والطباعة والوضع الليلي). */
final class Chart
{
    private static function x(string $s): string { return htmlspecialchars($s, ENT_QUOTES | ENT_XML1, 'UTF-8'); }
    /** أعمدة أفقية: [label => value]. $max حد المحور، $suffix لاحقة القيمة. */
    public static function hbars(array $data, float $max = 100, string $suffix = '', ?float $threshold = null, string $title = ''): string
    {
        if (!$data) { return '<div class="small text-muted">لا توجد بيانات</div>'; }
        $row = 30; $h = count($data) * $row + 10; $lw = 130; $w = 520; $bw = $w - $lw - 60; $y = 5; $out = '';
        foreach ($data as $label => $v) {
            $len = $max > 0 ? max(0, min(1, (float)$v / $max)) * $bw : 0; $cls = $threshold !== null && (float)$v < $threshold ? 'ch-bar ch-bad' : 'ch-bar';
            $out .= '<text x="' . ($w - 4) . '" y="' . ($y + 19) . '" class="ch-lbl" text-anchor="start">' . self::x(mb_strimwidth((string)$label, 0, 22, '…')) . '</text>'
                . '<rect x="' . ($w - $lw - $len) . '" y="' . ($y + 5) . '" width="' . round($len, 1) . '" height="' . ($row - 10) . '" rx="4" class="' . $cls . '"><title>' . self::x($label . ': ' . $v . $suffix) . '</title></rect>'
                . '<text x="' . ($w - $lw - $len - 6) . '" y="' . ($y + 19) . '" class="ch-val" text-anchor="start">' . self::x(rtrim(rtrim(number_format((float)$v, 1), '0'), '.') . $suffix) . '</text>';
            $y += $row;
        }
        if ($threshold !== null && $max > 0) { $tx = $w - $lw - $threshold / $max * $bw; $out .= '<line x1="' . round($tx, 1) . '" x2="' . round($tx, 1) . '" y1="0" y2="' . $h . '" class="ch-th"/>'; }
        return '<svg class="chart" direction="rtl" viewBox="0 0 ' . $w . ' ' . $h . '" role="img" aria-label="' . self::x($title) . '" preserveAspectRatio="xMidYMid meet">' . $out . '</svg>';
    }
    /** أعمدة رأسية (توزيع): [label => count]. */
    public static function columns(array $data, string $title = ''): string
    {
        if (!$data || !array_sum($data)) { return '<div class="small text-muted">لا توجد بيانات</div>'; }
        $w = 520; $h = 220; $n = count($data); $gap = 14; $bw = ($w - $gap * ($n + 1)) / $n; $mx = max($data); $out = ''; $i = 0;
        foreach (array_reverse($data, true) as $label => $v) {   // RTL: الأقل يمينًا
            $bh = $mx > 0 ? $v / $mx * ($h - 60) : 0; $x = $gap + $i * ($bw + $gap); $y = $h - 30 - $bh;
            $out .= '<rect x="' . round($x, 1) . '" y="' . round($y, 1) . '" width="' . round($bw, 1) . '" height="' . round($bh, 1) . '" rx="5" class="ch-bar ch-c' . ($n - $i) . '"><title>' . self::x("$label: $v") . '</title></rect>'
                . '<text x="' . round($x + $bw / 2, 1) . '" y="' . round($y - 6, 1) . '" class="ch-val" text-anchor="middle">' . (int)$v . '</text>'
                . '<text x="' . round($x + $bw / 2, 1) . '" y="' . ($h - 10) . '" class="ch-lbl" text-anchor="middle">' . self::x((string)$label) . '</text>';
            $i++;
        }
        return '<svg class="chart" direction="rtl" viewBox="0 0 ' . $w . ' ' . $h . '" role="img" aria-label="' . self::x($title) . '">' . $out . '</svg>';
    }
    /** حلقة نسبة (نجاح/رسوب). */
    public static function donut(float $good, float $bad, string $center = ''): string
    {
        $t = $good + $bad; if ($t <= 0) { return '<div class="small text-muted">لا توجد بيانات</div>'; }
        $r = 52; $c = 2 * M_PI * $r; $g = $good / $t * $c;
        return '<svg class="chart chart-donut" viewBox="0 0 140 140" role="img" aria-label="' . self::x($center) . '"><circle cx="70" cy="70" r="' . $r . '" class="ch-ring-bad" stroke-width="18" fill="none"/>'
            . '<circle cx="70" cy="70" r="' . $r . '" class="ch-ring-good" stroke-width="18" fill="none" stroke-dasharray="' . round($g, 2) . ' ' . round($c, 2) . '" transform="rotate(-90 70 70)" stroke-linecap="round"/>'
            . '<text x="70" y="76" text-anchor="middle" class="ch-center">' . self::x($center) . '</text></svg>';
    }
}
