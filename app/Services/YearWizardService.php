<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\{Auth, DB, HttpException, Tenant};

/** إنشاء سنة دراسية جديدة بنقرة: السنة + الفصول (منقولة سنة للأمام) + الشعب ومواد الشعب + الخطة الدراسية + إسناد المعلمين (اختياري). */
final class YearWizardService
{
    /** اقتراحات المعالج: الاسم والتواريخ التالية لآخر سنة. */
    public static function suggest(): array
    {
        Auth::authorize('academic.manage');
        $last = DB::one('SELECT * FROM academic_years WHERE school_id = ? ORDER BY starts_on DESC LIMIT 1', [Tenant::require()]);
        if (!$last) { $y = (int)date('Y'); return ['source' => null, 'name' => $y . '/' . ($y + 1), 'starts_on' => "$y-09-01", 'ends_on' => ($y + 1) . '-06-30']; }
        $name = preg_match('/(\d{4})\s*\/\s*(\d{4})/', $last['name'], $m) ? ((int)$m[1] + 1) . '/' . ((int)$m[2] + 1) : $last['name'] . ' (جديدة)';
        return ['source' => (int)$last['id'], 'name' => $name, 'starts_on' => self::plusYear($last['starts_on']), 'ends_on' => self::plusYear($last['ends_on'])];
    }
    private static function plusYear(string $d): string { return (new \DateTimeImmutable($d))->modify('+1 year')->format('Y-m-d'); }

    /** @return array{year_id:int, terms:int, sections:int, plan:int, summary:string} */
    public static function create(array $d): array
    {
        Auth::authorize('academic.manage'); $sid = Tenant::require();
        $name = trim((string)($d['name'] ?? '')); $starts = (string)($d['starts_on'] ?? ''); $ends = (string)($d['ends_on'] ?? '');
        if ($name === '' || mb_strlen($name) > 50) { throw new HttpException(422, 'اكتب اسم السنة (مثل 2027/2028)', ['name' => 'مطلوب']); }
        foreach (['starts_on' => $starts, 'ends_on' => $ends] as $k => $v) { if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) { throw new HttpException(422, 'التاريخ غير صالح', [$k => 'غير صالح']); } }
        $src = ($d['source_year_id'] ?? '') === '' ? null : (int)$d['source_year_id'];
        if ($src && !DB::value('SELECT 1 FROM academic_years WHERE id = ? AND school_id = ?', [$src, $sid])) { throw new HttpException(422, 'سنة المصدر غير موجودة', ['source_year_id' => 'غير صالحة']); }
        $flag = fn(string $k) => !empty($d[$k]) && $d[$k] !== '0';
        $out = ['terms' => 0, 'sections' => 0, 'plan' => 0];
        $yearId = AcademicService::createYear($name, $starts, $ends);
        try {
            if ($src && $flag('copy_terms')) {
                foreach (DB::all('SELECT * FROM terms WHERE academic_year_id = ? AND school_id = ? ORDER BY term_no', [$src, $sid]) as $t) {
                    $ts = max(self::plusYear($t['starts_on']), $starts); $te = min(self::plusYear($t['ends_on']), $ends); if ($te <= $ts) { continue; }
                    AcademicService::createTerm($yearId, $t['name'], (int)$t['term_no'], $ts, $te, (bool)$t['is_final']); $out['terms']++;
                }
            } elseif (($n = (int)($d['terms_count'] ?? 0)) > 0) {   // فصول جديدة مقسّمة بالتساوي
                $a = new \DateTimeImmutable($starts); $span = (int)$a->diff(new \DateTimeImmutable($ends))->days; $len = intdiv($span, $n);
                for ($i = 1; $i <= min($n, 4); $i++) {
                    $ts = $a->modify('+' . (($i - 1) * $len) . ' day')->format('Y-m-d'); $te = $i === $n ? $ends : $a->modify('+' . ($i * $len - 1) . ' day')->format('Y-m-d');
                    AcademicService::createTerm($yearId, ['', 'الفصل الأول', 'الفصل الثاني', 'الفصل الثالث', 'الفصل الرابع'][$i], $i, $ts, $te, $i === $n); $out['terms']++;
                }
            }
            if ($src && $flag('copy_plan')) { $out['plan'] = StudyPlanService::copy($src, $yearId); }
            if ($src && $flag('copy_sections')) { $out['sections'] = AcademicService::cloneStructure($src, $yearId, $flag('copy_teachers')); }
            if ($out['plan'] && $out['sections'] && $flag('apply_plan')) { StudyPlanService::apply($yearId); }
        } catch (\Throwable $e) {
            // لا نترك سنة نصف مكتملة: تُحذف السنة الجديدة وما أُنشئ فيها ثم يُعرض السبب
            self::rollback($yearId); throw $e instanceof HttpException ? $e : new HttpException(500, 'تعذّر إكمال إنشاء السنة: ' . $e->getMessage());
        }
        AuditService::log('year.wizard', 'academic_year', $yearId, null, $out + ['source' => $src]);
        $parts = ['أُنشئت السنة «' . $name . '»']; if ($out['terms']) { $parts[] = "{$out['terms']} فصول"; } if ($out['sections']) { $parts[] = "{$out['sections']} شعبة بموادها"; } if ($out['plan']) { $parts[] = "خطة دراسية بـ {$out['plan']} مادة"; }
        return $out + ['year_id' => $yearId, 'summary' => implode('، ', $parts) . '. الخطوة التالية: ترفيع الطلاب إليها من «السنوات والفصول».'];
    }
    private static function rollback(int $yearId): void
    {
        $sid = Tenant::require();
        try {
            DB::query('DELETE ta FROM teacher_assignments ta JOIN section_subjects ss ON ss.id = ta.section_subject_id JOIN sections sc ON sc.id = ss.section_id WHERE sc.academic_year_id = ? AND sc.school_id = ?', [$yearId, $sid]);
            DB::query('DELETE ss FROM section_subjects ss JOIN sections sc ON sc.id = ss.section_id WHERE sc.academic_year_id = ? AND sc.school_id = ?', [$yearId, $sid]);
            DB::query('DELETE FROM sections WHERE academic_year_id = ? AND school_id = ?', [$yearId, $sid]); DB::query('DELETE FROM study_plans WHERE academic_year_id = ? AND school_id = ?', [$yearId, $sid]);
            DB::query('DELETE FROM terms WHERE academic_year_id = ? AND school_id = ?', [$yearId, $sid]); DB::query("DELETE FROM academic_years WHERE id = ? AND school_id = ? AND status = 'draft'", [$yearId, $sid]);
        } catch (\Throwable) {}
    }
}
