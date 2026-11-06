<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\{Auth, DB, HttpException, Tenant};
use App\Repositories\AcademicYearRepository;

/** الترفيع الجماعي بين سنتين: ترفيع/إعادة/تخريج/انسحاب لكل طالب، مع إغلاق القديمة وتفعيل الجديدة في transaction واحد. */
final class PromotionService
{
    /** @return array{plan: array, unmapped: array, counts: array} بلا أي كتابة */
    public static function preview(int $fromYear, int $toYear, array $decisions = []): array
    {
        Auth::authorize('academic.promote');
        [$from, $to] = self::years($fromYear, $toYear);
        $sid = Tenant::require();
        $rows = DB::all("SELECT e.id AS enrollment_id, e.student_id, e.section_id, st.full_name, sc.name AS section_name, sc.grade_level_id, g.next_grade_level_id
            FROM student_enrollments e JOIN students st ON st.id = e.student_id JOIN sections sc ON sc.id = e.section_id JOIN grade_levels g ON g.id = sc.grade_level_id
            WHERE e.school_id = ? AND e.academic_year_id = ? AND e.status = 'active' AND st.deleted_at IS NULL ORDER BY g.sort_order, sc.name, st.full_name", [$sid, $fromYear]);
        $plan = []; $unmapped = []; $counts = ['promote' => 0, 'repeat' => 0, 'graduate' => 0, 'withdraw' => 0];
        $load = [];
        foreach ($rows as $r) {
            $d = $decisions[$r['enrollment_id']] ?? 'promote';
            if (!in_array($d, ['promote', 'repeat', 'withdraw'], true)) { throw new HttpException(422, 'قرار غير صالح'); }
            $target = null;
            if ($d === 'promote') {
                if (!$r['next_grade_level_id']) { $d = 'graduate'; }
                else { $target = self::targetSection($toYear, (int)$r['next_grade_level_id'], $r['section_name'], $load); }
            } elseif ($d === 'repeat') { $target = self::targetSection($toYear, (int)$r['grade_level_id'], $r['section_name'], $load); }
            if (in_array($d, ['promote', 'repeat'], true) && $target === null) { $unmapped[] = ['student' => $r['full_name'], 'section' => $r['section_name'], 'decision' => $d]; }
            $counts[$d]++;
            $plan[] = $r + ['decision' => $d, 'target_section_id' => $target];
        }
        return ['plan' => $plan, 'unmapped' => $unmapped, 'counts' => $counts];
    }

    public static function promote(int $fromYear, int $toYear, array $decisions = []): array
    {
        $pv = self::preview($fromYear, $toYear, $decisions);
        if ($pv['unmapped']) { throw new HttpException(422, 'لا توجد شعب مستهدفة في السنة الجديدة لـ ' . count($pv['unmapped']) . ' طالب. أنشئ الشعب أولًا (يمكنك نسخ هيكل السنة).', ['unmapped' => json_encode($pv['unmapped'], JSON_UNESCAPED_UNICODE)]); }
        $sid = Tenant::require();
        TranscriptService::freezeYear($fromYear);   // لقطة نهائية للسجل الأكاديمي قبل تغيير حالات التسجيل
        DB::transaction(function () use ($pv, $fromYear, $toYear, $sid) {
            foreach ($pv['plan'] as $p) {
                $eid = (int)$p['enrollment_id']; $stu = (int)$p['student_id'];
                $newStatus = ['promote' => 'promoted', 'graduate' => 'promoted', 'repeat' => 'repeated', 'withdraw' => 'withdrawn'][$p['decision']];
                DB::query('UPDATE student_enrollments SET status = ?, left_on = CURDATE() WHERE id = ? AND school_id = ?', [$newStatus, $eid, $sid]);
                $to = $p['target_section_id'];
                if ($to) {
                    DB::query("INSERT INTO student_enrollments (school_id, student_id, academic_year_id, section_id, status, enrolled_on) VALUES (?,?,?,?, 'active', CURDATE())", [$sid, $stu, $toYear, $to]);
                }
                $stStatus = $p['decision'] === 'graduate' ? 'graduated' : ($p['decision'] === 'withdraw' ? 'withdrawn' : 'active');
                if ($stStatus !== 'active') { DB::query('UPDATE students SET status = ?, updated_at = NOW() WHERE id = ? AND school_id = ?', [$stStatus, $stu, $sid]); }
                DB::query('INSERT INTO student_status_history (school_id, student_id, academic_year_id, from_status, to_status, reason, changed_by) VALUES (?,?,?,?,?,?,?)',
                    [$sid, $stu, $fromYear, 'active', ['promote' => 'promoted', 'graduate' => 'graduated', 'repeat' => 'repeated', 'withdraw' => 'withdrawn'][$p['decision']], 'ترفيع جماعي', Auth::id()]);
            }
            DB::query("UPDATE academic_years SET status = 'closed', closed_at = NOW() WHERE id = ? AND school_id = ?", [$fromYear, $sid]);
            DB::query("UPDATE academic_years SET status = 'active' WHERE id = ? AND school_id = ?", [$toYear, $sid]);
        });
        AuditService::log('year.promote', 'academic_year', $toYear, ['from' => $fromYear], $pv['counts']);
        return $pv['counts'];
    }

    private static function years(int $from, int $to): array
    {
        $r = new AcademicYearRepository(); $f = $r->findOrFail($from); $t = $r->findOrFail($to);
        if ($f['status'] !== 'active') { throw new HttpException(422, 'سنة المصدر يجب أن تكون فعّالة'); }
        if ($t['status'] !== 'draft') { throw new HttpException(422, 'السنة المستهدفة يجب أن تكون مسودة'); }
        if ($t['starts_on'] <= $f['starts_on']) { throw new HttpException(422, 'السنة المستهدفة يجب أن تلي سنة المصدر'); }
        return [$f, $t];
    }

    /** نفس الاسم أولًا ثم أول شعبة بسعة متاحة. $load يتتبع التعبئة داخل المعاينة. */
    private static function targetSection(int $year, int $gradeId, string $name, array &$load): ?int
    {
        $cands = DB::all('SELECT sc.id, sc.name, sc.capacity, (SELECT COUNT(*) FROM student_enrollments e WHERE e.section_id = sc.id AND e.status = "active") AS n
            FROM sections sc WHERE sc.school_id = ? AND sc.academic_year_id = ? AND sc.grade_level_id = ? ORDER BY (sc.name = ?) DESC, sc.name', [Tenant::require(), $year, $gradeId, $name]);
        foreach ($cands as $c) {
            $used = (int)$c['n'] + ($load[$c['id']] ?? 0);
            if ($c['capacity'] === null || $used < (int)$c['capacity']) { $load[$c['id']] = ($load[$c['id']] ?? 0) + 1; return (int)$c['id']; }
        }
        return null;
    }
}
