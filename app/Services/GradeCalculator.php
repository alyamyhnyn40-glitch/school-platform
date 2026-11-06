<?php
declare(strict_types=1);
namespace App\Services;

/** حساب الدرجات (دوال نقية بلا قاعدة بيانات، لسهولة الاختبار). */
final class GradeCalculator
{
    /**
     * @param array $scheme  ['max_score','average_method'=>weighted|arithmetic,'rounding']
     * @param array $types   [id => ['weight','name']]
     * @param array $assessments [['id','type_id','max_score']]
     * @param array $scores  [assessment_id => ['score'=>?float,'is_absent'=>bool]]
     * @return array{percent: float, total: float, complete: bool, entered: int}
     *  - الدرجة الفارغة (null) تُهمل وتجعل النتيجة غير مكتملة؛ الغائب (is_absent) يُحتسب صفرًا.
     *  - weighted: متوسط كل نوع تقييم (مجموع الدرجات/مجموع العظمى للمُدخَل) × وزنه. نوع بلا إدخال يُحتسب صفرًا ويُعلَّم غير مكتمل.
     *  - arithmetic: المتوسط المتساوي لنسب كل تقييم مُدخَل.
     */
    public static function subject(array $scheme, array $types, array $assessments, array $scores): array
    {
        $complete = $assessments !== [];
        $entered = [];
        foreach ($assessments as $a) {
            $s = $scores[$a['id']] ?? null;
            if ($s === null || ($s['score'] === null && empty($s['is_absent']))) { $complete = false; continue; }
            $entered[] = ['type_id' => $a['type_id'], 'max' => (float)$a['max_score'], 'score' => !empty($s['is_absent']) ? 0.0 : (float)$s['score']];
        }
        if (($scheme['average_method'] ?? 'weighted') === 'arithmetic') {
            $pcts = array_map(fn($e) => $e['max'] > 0 ? $e['score'] / $e['max'] * 100 : 0, $entered);
            $percent = $pcts ? array_sum($pcts) / count($pcts) : 0.0;
        } else {
            $wsum = 0.0; $acc = 0.0;
            foreach ($types as $tid => $t) {
                $wsum += (float)$t['weight'];
                $items = array_filter($entered, fn($e) => (int)$e['type_id'] === (int)$tid);
                if (!$items) { if ($assessments !== []) { $complete = false; } continue; }
                $max = array_sum(array_column($items, 'max'));
                $acc += (float)$t['weight'] * ($max > 0 ? array_sum(array_column($items, 'score')) / $max : 0);
            }
            $percent = $wsum > 0 ? $acc / $wsum * 100 : 0.0;
        }
        $r = (int)($scheme['rounding'] ?? 2);
        return ['percent' => round($percent, $r), 'total' => round($percent / 100 * (float)$scheme['max_score'], $r), 'complete' => $complete, 'entered' => count($entered)];
    }

    /** أعلى تقدير حدّه الأدنى ≤ النسبة. $scales: [['label','min_percent']]. */
    public static function letter(array $scales, float $percent): ?string
    {
        usort($scales, fn($a, $b) => (float)$b['min_percent'] <=> (float)$a['min_percent']);
        foreach ($scales as $s) { if ($percent + 1e-9 >= (float)$s['min_percent']) { return $s['label']; } }
        return null;
    }

    public static function passed(array $scheme, float $total): bool { return $total + 1e-9 >= (float)$scheme['pass_score']; }

    /** ترتيب تنافسي: 1،2،2،4. يعيد [key => rank] لقيم تنازلية. */
    public static function rank(array $valuesByKey): array
    {
        arsort($valuesByKey);
        $rank = 0; $i = 0; $prev = null; $out = [];
        foreach ($valuesByKey as $k => $v) { $i++; if ($prev === null || abs($v - $prev) > 1e-9) { $rank = $i; } $out[$k] = $rank; $prev = $v; }
        return $out;
    }
}
