<?php
declare(strict_types=1);
namespace App\Services;

/** حساب مالي بالأعداد الصحيحة (أصغر وحدة = 1/100). بلا float في الجمع والتقسيم والتوزيع. */
final class Money
{
    public const MAX_CENTS = 99_999_999_999;   // 999,999,999.99

    /** "1234.5" أو 1234.5 → 123450. يرفض السالب والقيم غير الرقمية. */
    public static function cents(string|int|float $v): int
    {
        if (is_float($v)) { $c = $v * 100; if ($v < 0 || $v >= 1e9 || abs($c - round($c)) > 1e-6) { throw new \InvalidArgumentException('مبلغ غير صالح'); } return (int)round($c); }
        $s = trim((string)$v);
        if (!preg_match('/^\d{1,9}(\.\d{1,2})?$/', $s)) { throw new \InvalidArgumentException('مبلغ غير صالح (حتى خانتين عشريتين)'); }   // لا تقريب صامت لأكثر من خانتين
        [$i, $d] = array_pad(explode('.', $s, 2), 2, '0');
        return (int)$i * 100 + (int)str_pad($d, 2, '0');
    }
    public static function str(int $cents): string { return ($cents < 0 ? '-' : '') . intdiv(abs($cents), 100) . '.' . str_pad((string)(abs($cents) % 100), 2, '0', STR_PAD_LEFT); }
    public static function fmt(int|string $c, string $cur = ''): string
    {
        $c = is_string($c) ? self::cents($c) : $c;
        return number_format($c / 100, 2) . ($cur !== '' ? ' ' . $cur : '');
    }

    /** سعر صرف DECIMAL(18,6) كنص → عدد صحيح مضروب في 1e6. */
    public static function rate(string|float $r): int
    {
        $s = number_format((float)$r, 6, '.', '');
        if ((float)$s <= 0) { throw new \InvalidArgumentException('سعر صرف غير صالح'); }
        [$i, $d] = explode('.', $s); return (int)$i * 1_000_000 + (int)$d;
    }

    /** تحويل مبلغ بين عملتين عبر العملة الأساسية: cents × from ÷ to (مع تقريب نصف لأعلى). */
    public static function convert(int $cents, int $fromRate, int $toRate): int
    {
        if ($fromRate === $toRate) { return $cents; }
        if ($cents > 0 && $fromRate > intdiv(PHP_INT_MAX, $cents)) {   // تفادي تجاوز int64 بمبالغ ضخمة
            return (int)round($cents * ($fromRate / $toRate));
        }
        return intdiv($cents * $fromRate * 2 + $toRate, $toRate * 2);
    }

    /** نسبة مئوية (مثل 15.5) من مبلغ، تقريب نصف لأعلى. */
    public static function percent(int $cents, string|float $pct): int
    {
        $p = (int)round((float)$pct * 100);   // النسبة بدقة 0.01%
        return intdiv($cents * $p * 2 + 10000, 20000);
    }

    /** تقسيم مبلغ إلى n أقساط متساوية؛ الباقي يُوزَّع على الأقساط الأولى بحيث يساوي المجموع المبلغ تمامًا. */
    public static function split(int $cents, int $n): array
    {
        $base = intdiv($cents, $n); $rem = $cents - $base * $n; $out = [];
        for ($i = 0; $i < $n; $i++) { $out[] = $base + ($i < $rem ? 1 : 0); }
        return $out;
    }

    /** إضافة أشهر مع الحفاظ على آخر الشهر (31 يناير + شهر = 28/29 فبراير). */
    public static function addMonths(string $date, int $months): string
    {
        $d = new \DateTimeImmutable($date); $day = (int)$d->format('j');
        $first = $d->modify('first day of this month')->modify("+$months month");
        return $first->setDate((int)$first->format('Y'), (int)$first->format('n'), min($day, (int)$first->format('t')))->format('Y-m-d');
    }
}
