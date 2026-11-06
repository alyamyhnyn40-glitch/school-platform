<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\{Auth, DB, HttpException, Tenant};

/** الإنذار المبكر: مؤشرات مفسَّرة بأرقامها وعتبات قابلة للتهيئة. تُنشئ «تنبيهات» فقط؛ القرار دائمًا للمرشد ولا إجراء آلي ولا إشعار لولي الأمر. */
final class RiskService
{
    public const WINDOW_DAYS = 30;
    /** code => [label, default threshold, unit, description] */
    public const INDICATORS = [
        'absence_rate' => ['نسبة الغياب مرتفعة', 20, '%', 'نسبة أيام الغياب من إجمالي الأيام المسجّلة خلال آخر 30 يومًا (بحد أدنى 5 أيام مسجلة)'],
        'consecutive_absence' => ['غياب متتابع', 3, 'أيام', 'عدد أيام الغياب الكامل المتتالية حتى آخر يوم مسجّل'],
        'low_average' => ['معدل متدنٍّ', 50, '%', 'معدل الفصل الحالي (يشمل التقييمات غير المعتمدة) أقل من الحد'],
        'grade_drop' => ['تراجع في المعدل', 15, 'نقطة', 'انخفاض معدل الفصل الحالي عن السابق بمقدار الحد أو أكثر'],
        'discipline' => ['مخالفات سلوكية', 3, 'مخالفات', 'عدد المخالفات المسجلة خلال آخر 30 يومًا'],
        'fee_arrears' => ['تأخر في الرسوم', 60, 'يومًا', 'أقدم قسط متأخر منذ أكثر من الحد (عامل خطر انقطاع محتمل)'],
    ];

    private static bool $system = false;
    private static function guard(string $perm): void { if (!self::$system) { Auth::authorize($perm); } }
    /** للمهمة المجدولة فقط (CLI): يفحص مدرسة بلا جلسة مستخدم. */
    public static function scanSystem(int $schoolId): array
    {
        if (PHP_SAPI !== 'cli') { throw new HttpException(403, 'للاستخدام من سطر الأوامر فقط'); }
        Tenant::setForCli($schoolId); self::$system = true;
        try { return self::scan(); } finally { self::$system = false; Tenant::setForCli(null); }
    }

    public static function indicators(): array
    {
        self::guard('risk.view'); $sid = Tenant::require();
        foreach (self::INDICATORS as $code => [$label, $def]) { DB::query('INSERT IGNORE INTO risk_indicators (school_id, code, label, threshold, is_active) VALUES (?,?,?,?,1)', [$sid, $code, $label, $def]); }
        $rows = DB::all('SELECT * FROM risk_indicators WHERE school_id = ? ORDER BY id', [$sid]);
        foreach ($rows as &$r) { $r['unit'] = self::INDICATORS[$r['code']][2] ?? ''; $r['description'] = self::INDICATORS[$r['code']][3] ?? ''; } return $rows;
    }
    public static function setIndicator(string $code, string $threshold, bool $active): void
    {
        Auth::authorize('counseling.manage'); self::indicators();
        if (!isset(self::INDICATORS[$code])) { throw new HttpException(404, 'مؤشر غير معروف'); }
        if (!is_numeric($threshold) || (float)$threshold < 0 || (float)$threshold > 1000) { throw new HttpException(422, 'عتبة غير صالحة', ['threshold' => 'غير صالحة']); }
        DB::query('UPDATE risk_indicators SET threshold = ?, is_active = ? WHERE school_id = ? AND code = ?', [round((float)$threshold, 2), (int)$active, Tenant::require(), $code]);
        AuditService::log('risk.indicator', 'risk_indicator', null, null, ['code' => $code, 'threshold' => $threshold, 'active' => $active]);
    }

    /** يحسب المؤشرات لكل طلاب السنة الفعّالة ويُنشئ/يحدّث التنبيهات. المراجَعة/المرفوضة لا تُمسّ. يعيد ملخصًا. */
    public static function scan(): array
    {
        self::guard('risk.view'); $sid = Tenant::require(); $y = AcademicService::activeYear(); if (!$y) { return ['students' => 0, 'flags' => 0, 'cleared' => 0]; }
        $ind = array_column(self::indicators(), null, 'code'); $on = fn(string $c) => !empty($ind[$c]['is_active']); $th = fn(string $c) => (float)$ind[$c]['threshold'];
        $enr = DB::all("SELECT e.id AS eid, e.student_id, e.section_id, s.full_name FROM student_enrollments e JOIN students s ON s.id = e.student_id WHERE e.school_id = ? AND e.academic_year_id = ? AND e.status = 'active' AND s.status = 'active' AND s.deleted_at IS NULL", [$sid, $y['id']]);
        $hits = [];   // [student_id][code] = [metric, explanation]
        $from = date('Y-m-d', strtotime('-' . self::WINDOW_DAYS . ' day'));
        // الغياب
        if ($on('absence_rate') || $on('consecutive_absence')) {
            $days = [];   // [eid][date] = كل السجلات غياب؟
            foreach (DB::all("SELECT r.enrollment_id AS eid, a.session_date AS d, SUM(r.status <> 'absent') AS present_n FROM attendance_records r JOIN attendance_sessions a ON a.id = r.session_id WHERE r.school_id = ? AND a.session_date >= ? AND a.kind <> 'live' GROUP BY r.enrollment_id, a.session_date", [$sid, $from]) as $r) { $days[$r['eid']][$r['d']] = ((int)$r['present_n'] === 0); }
            foreach ($enr as $e) {
                $d = $days[$e['eid']] ?? []; ksort($d); $n = count($d); if ($n < 5) { continue; } $abs = count(array_filter($d)); $pct = round($abs / $n * 100, 1);
                if ($on('absence_rate') && $pct >= $th('absence_rate')) { $hits[$e['student_id']]['absence_rate'] = [$pct, "غاب $abs من $n يومًا مسجّلًا ($pct%) خلال آخر " . self::WINDOW_DAYS . ' يومًا — الحد ' . $th('absence_rate') . '%']; }
                if ($on('consecutive_absence')) { $run = 0; foreach (array_reverse($d, true) as $isAbs) { if ($isAbs) { $run++; } else { break; } } if ($run >= $th('consecutive_absence')) { $hits[$e['student_id']]['consecutive_absence'] = [$run, "غاب $run أيام متتالية حتى آخر يوم مسجّل — الحد " . (int)$th('consecutive_absence')]; } }
            }
        }
        // الدرجات
        if ($on('low_average') || $on('grade_drop')) {
            $terms = DB::all('SELECT * FROM terms WHERE school_id = ? AND academic_year_id = ? ORDER BY term_no', [$sid, $y['id']]); $today = date('Y-m-d'); $cur = null; $prev = null;
            foreach ($terms as $t) { if ($t['starts_on'] <= $today) { $prev = $cur; $cur = $t; } }
            if ($cur) {
                foreach (array_unique(array_column($enr, 'section_id')) as $sec) {
                    try { $r = ResultsService::compute((int)$cur['id'], (int)$sec, false); $rp = $prev ? ResultsService::compute((int)$prev['id'], (int)$sec, false) : null; } catch (\Throwable) { continue; }
                    $pavg = $rp ? array_column($rp['students'], 'average', 'student_id') : [];
                    foreach ($r['students'] as $st) {
                        if ($st['average'] === null) { continue; } $avg = (float)$st['average'];
                        if ($on('low_average') && $avg < $th('low_average')) { $hits[$st['student_id']]['low_average'] = [$avg, "معدل «{$cur['name']}» $avg% وهو دون الحد " . $th('low_average') . '%' . ($st['failed'] ? " (راسب في {$st['failed']} مواد)" : '')]; }
                        if ($on('grade_drop') && isset($pavg[$st['student_id']]) && $pavg[$st['student_id']] !== null) { $drop = round((float)$pavg[$st['student_id']] - $avg, 1); if ($drop >= $th('grade_drop')) { $hits[$st['student_id']]['grade_drop'] = [$drop, "انخفض المعدل من {$pavg[$st['student_id']]}% إلى $avg% ($drop نقطة) — الحد " . $th('grade_drop')]; } }
                    }
                }
            }
        }
        // الانضباط
        if ($on('discipline')) { foreach (DB::all("SELECT student_id, COUNT(*) AS n FROM discipline_records WHERE school_id = ? AND kind = 'violation' AND incident_date >= ? AND deleted_at IS NULL GROUP BY student_id", [$sid, $from]) as $r) { if ((int)$r['n'] >= $th('discipline')) { $hits[$r['student_id']]['discipline'] = [(int)$r['n'], "سُجّلت {$r['n']} مخالفات خلال آخر " . self::WINDOW_DAYS . ' يومًا — الحد ' . (int)$th('discipline')]; } } }
        // الرسوم (إن كانت الوحدة مفعّلة)
        if ($on('fee_arrears') && SubscriptionService::moduleEnabled($sid, 'fees')) {
            foreach (DB::all("SELECT e.student_id, MIN(i.due_date) AS oldest FROM installments i JOIN student_fees sf ON sf.id = i.student_fee_id JOIN student_enrollments e ON e.id = sf.enrollment_id WHERE sf.school_id = ? AND sf.status = 'active' AND i.status <> 'paid' AND i.due_date < CURDATE() GROUP BY e.student_id", [$sid]) as $r) {
                $days = (int)((time() - strtotime($r['oldest'])) / 86400); if ($days >= $th('fee_arrears')) { $hits[$r['student_id']]['fee_arrears'] = [$days, "قسط متأخر منذ $days يومًا — الحد " . (int)$th('fee_arrears')]; }
            }
        }
        $flags = 0; $cleared = 0; $codes = array_keys(self::INDICATORS);
        DB::transaction(function () use ($hits, $enr, $sid, $codes, &$flags, &$cleared) {
            foreach ($hits as $stu => $list) {
                foreach ($list as $code => [$metric, $text]) {
                    $open = DB::one("SELECT id FROM risk_flags WHERE school_id = ? AND student_id = ? AND indicator_code = ? AND status = 'open'", [$sid, $stu, $code]);
                    $handled = DB::value("SELECT 1 FROM risk_flags WHERE school_id = ? AND student_id = ? AND indicator_code = ? AND status IN ('reviewed','dismissed','case_opened') AND created_at >= ?", [$sid, $stu, $code, date('Y-m-d', strtotime('-' . self::WINDOW_DAYS . ' day'))]);
                    if ($open) { DB::query('UPDATE risk_flags SET explanation = ?, metric = ? WHERE id = ?', [$text, $metric, $open['id']]); }
                    elseif (!$handled) { DB::query('INSERT INTO risk_flags (school_id, student_id, indicator_code, explanation, metric) VALUES (?,?,?,?,?)', [$sid, $stu, $code, $text, $metric]); $flags++; }
                }
            }
            // تنبيه مفتوح زالت أسبابه ولم يقرر فيه أحد يُزال
            foreach (DB::all("SELECT id, student_id, indicator_code FROM risk_flags WHERE school_id = ? AND status = 'open'", [$sid]) as $f) { if (!isset($hits[$f['student_id']][$f['indicator_code']])) { DB::query('DELETE FROM risk_flags WHERE id = ?', [$f['id']]); $cleared++; } }
        });
        AuditService::log('risk.scan', 'school', $sid, null, ['students' => count($enr), 'new_flags' => $flags, 'cleared' => $cleared]);
        return ['students' => count($enr), 'flags' => $flags, 'cleared' => $cleared];
    }

    /** التنبيهات مجمّعة لكل طالب. status: open|handled|all */
    public static function flags(string $status = 'open', ?int $sectionId = null): array
    {
        Auth::authorize('risk.view'); $sid = Tenant::require(); $y = AcademicService::activeYear();
        $w = 'f.school_id = ?'; $p = [$y['id'] ?? 0, $sid];
        if ($status === 'open') { $w .= " AND f.status = 'open'"; } elseif ($status === 'handled') { $w .= " AND f.status <> 'open'"; }
        if ($sectionId) { $w .= ' AND e.section_id = ?'; $p[] = $sectionId; }
        $rows = DB::all("SELECT f.*, s.full_name, s.student_no, sc.name AS section_name, g.name AS grade_name, ri.label, u.full_name AS reviewer FROM risk_flags f JOIN students s ON s.id = f.student_id
            LEFT JOIN student_enrollments e ON e.student_id = s.id AND e.academic_year_id = ? AND e.status = 'active' LEFT JOIN sections sc ON sc.id = e.section_id LEFT JOIN grade_levels g ON g.id = sc.grade_level_id
            LEFT JOIN risk_indicators ri ON ri.school_id = f.school_id AND ri.code = f.indicator_code LEFT JOIN users u ON u.id = f.reviewed_by WHERE $w ORDER BY f.student_id, f.id", $p);
        $out = []; foreach ($rows as $r) { $out[$r['student_id']]['student'] = ['id' => (int)$r['student_id'], 'name' => $r['full_name'], 'no' => $r['student_no'], 'class' => ($r['grade_name'] ?? '') . ' / ' . ($r['section_name'] ?? '')]; $out[$r['student_id']]['flags'][] = $r; }
        uasort($out, fn($a, $b) => count($b['flags']) <=> count($a['flags'])); return array_values($out);
    }
    private static function flag(int $id): array { return DB::one('SELECT * FROM risk_flags WHERE id = ? AND school_id = ?', [$id, Tenant::require()]) ?? throw new HttpException(404, 'التنبيه غير موجود'); }
    public static function review(int $id, string $note): void
    {
        Auth::authorize('counseling.manage'); $f = self::flag($id); if ($f['status'] !== 'open') { throw new HttpException(422, 'التنبيه مُعالَج مسبقًا'); }
        if (mb_strlen(trim($note)) < 3) { throw new HttpException(422, 'اكتب ملاحظة المراجعة', ['note' => 'مطلوبة']); }
        DB::query("UPDATE risk_flags SET status = 'reviewed', note = ?, reviewed_by = ?, reviewed_at = ? WHERE id = ?", [mb_substr($note, 0, 500), Auth::id(), now(), $id]); AuditService::log('risk.review', 'risk_flag', $id, ['status' => 'open'], ['status' => 'reviewed']);
    }
    public static function dismiss(int $id, string $reason): void
    {
        Auth::authorize('counseling.manage'); $f = self::flag($id); if ($f['status'] !== 'open') { throw new HttpException(422, 'التنبيه مُعالَج مسبقًا'); }
        if (mb_strlen(trim($reason)) < 3) { throw new HttpException(422, 'اذكر سبب الاستبعاد (لتجنّب وصم الطالب بلا مبرر)', ['note' => 'مطلوب']); }
        DB::query("UPDATE risk_flags SET status = 'dismissed', note = ?, reviewed_by = ?, reviewed_at = ? WHERE id = ?", [mb_substr($reason, 0, 500), Auth::id(), now(), $id]); AuditService::log('risk.dismiss', 'risk_flag', $id, ['status' => 'open'], ['status' => 'dismissed', 'reason' => $reason]);
    }
    /** يفتح حالة إرشاد من تنبيه (قرار المرشد). */
    public static function openCase(int $id, ?string $note): int
    {
        Auth::authorize('counseling.manage'); $f = self::flag($id); if ($f['status'] !== 'open') { throw new HttpException(422, 'التنبيه مُعالَج مسبقًا'); }
        $label = self::INDICATORS[$f['indicator_code']][0] ?? $f['indicator_code'];
        return DB::transaction(function () use ($f, $id, $note, $label) {
            $case = CounselingService::create((int)$f['student_id'], str_starts_with($f['indicator_code'], 'discipline') ? 'behavior' : ($f['indicator_code'] === 'low_average' || $f['indicator_code'] === 'grade_drop' ? 'academic' : 'social'), 'متابعة: ' . $label, trim("سبب الفتح (تنبيه آلي): {$f['explanation']}\n" . (string)$note), $id);
            DB::query("UPDATE risk_flags SET status = 'case_opened', reviewed_by = ?, reviewed_at = ? WHERE id = ?", [Auth::id(), now(), $id]); return $case;
        });
    }
}
