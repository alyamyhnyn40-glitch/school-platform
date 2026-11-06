<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\{Auth, DB, HttpException, Tenant};

/** المتأخرات وكشوف الحساب وملخصات التحصيل. كل مجموع مفصول بالعملة (لا خلط عملات). */
final class FinanceReportService
{
    /** الطلاب المتأخرون: مستحق متأخر لكل رسوم (بعملتها). */
    public static function arrears(?string $asOf = null, ?int $sectionId = null): array
    {
        Auth::authorize('fees.view'); $sid = Tenant::require(); $asOf = $asOf ?: date('Y-m-d');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $asOf)) { throw new HttpException(422, 'تاريخ غير صالح'); }
        $w = "sf.school_id = ? AND sf.status = 'active' AND i.due_date < ? AND i.status <> 'paid' AND s.deleted_at IS NULL"; $p = [$sid, $asOf];
        if ($sectionId) { $w .= ' AND e.section_id = ?'; $p[] = $sectionId; }
        $rows = DB::all("SELECT sf.id AS fee_id, sf.currency, s.id AS student_id, s.full_name, s.student_no, sc.name AS section_name, g.name AS grade_name, SUM(i.amount - i.paid_amount) AS overdue, MIN(i.due_date) AS oldest, COUNT(*) AS installments,
              (SELECT gd.full_name FROM student_guardians sg JOIN guardians gd ON gd.id = sg.guardian_id WHERE sg.student_id = s.id AND sg.school_id = sf.school_id ORDER BY sg.financially_responsible DESC, sg.is_primary DESC LIMIT 1) AS guardian,
              (SELECT gd.phone FROM student_guardians sg JOIN guardians gd ON gd.id = sg.guardian_id WHERE sg.student_id = s.id AND sg.school_id = sf.school_id ORDER BY sg.financially_responsible DESC, sg.is_primary DESC LIMIT 1) AS guardian_phone
            FROM installments i JOIN student_fees sf ON sf.id = i.student_fee_id JOIN student_enrollments e ON e.id = sf.enrollment_id JOIN students s ON s.id = e.student_id JOIN sections sc ON sc.id = e.section_id JOIN grade_levels g ON g.id = sc.grade_level_id
            WHERE $w GROUP BY sf.id, sf.currency, s.id, s.full_name, s.student_no, sc.name, g.name ORDER BY oldest, s.full_name", $p);
        foreach ($rows as &$r) { $r['days'] = (int)((strtotime($asOf) - strtotime($r['oldest'])) / 86400); }
        return $rows;
    }
    public static function arrearsTotals(array $rows): array
    {
        $t = []; foreach ($rows as $r) { $t[$r['currency']] = ($t[$r['currency']] ?? 0) + Money::cents($r['overdue']); }
        return array_map(fn($c) => Money::str($c), $t);
    }

    /** إرسال تذكير SMS (طابور) لأولياء الأمور المسؤولين عن الرسوم المتأخرة. لا يتكرر لنفس اليوم. يعيد عدد الرسائل. */
    public static function remind(array $feeIds, string $channel = 'sms'): int
    {
        Auth::authorize('fees.collect');
        if (!in_array($channel, ['sms', 'whatsapp'], true)) { throw new HttpException(422, 'قناة غير صالحة'); }
        if ($channel === 'whatsapp' && !NotificationService::whatsappEnabled()) { throw new HttpException(422, 'قناة واتساب غير مفعّلة من إدارة المنصة'); } $sid = Tenant::require(); $n = 0; $school = DB::value('SELECT name FROM schools WHERE id = ?', [$sid]); $today = date('Y-m-d');
        $byFee = array_column(self::arrears(null, null), null, 'fee_id');
        foreach (array_unique(array_map('intval', $feeIds)) as $fid) {
            $r = $byFee[$fid] ?? null; if (!$r) { continue; }
            $phones = DB::all('SELECT DISTINCT ' . ($channel === 'whatsapp' ? "COALESCE(NULLIF(g.whatsapp, ''), g.phone)" : 'g.phone') . ' AS phone FROM student_guardians sg JOIN guardians g ON g.id = sg.guardian_id WHERE sg.student_id = ? AND sg.school_id = ? AND sg.can_view = 1 AND g.phone IS NOT NULL AND (sg.financially_responsible = 1 OR sg.is_primary = 1)', [$r['student_id'], $sid]);
            $body = "$school: تذكير بمتأخرات رسوم الطالب/ة {$r['full_name']} بقيمة " . Money::fmt($r['overdue'], $r['currency']) . " (حتى $today). نرجو السداد.";
            foreach ($phones as $ph) {
                if (DB::value('SELECT 1 FROM notification_queue WHERE school_id = ? AND channel = ? AND recipient = ? AND body = ?', [$sid, $channel, $ph['phone'], $body])) { continue; }
                DB::query('INSERT INTO notification_queue (school_id, channel, recipient, body) VALUES (?, ?, ?, ?)', [$sid, $channel, $ph['phone'], $body]); $n++;
            }
        }
        AuditService::log('fees.remind', 'school', $sid, null, ['queued' => $n]);
        return $n;
    }

    /** ملخص التحصيل في فترة: لكل عملة ولكل طريقة دفع ولكل يوم (بدون الملغى). */
    public static function collections(string $from, string $to): array
    {
        Auth::authorize('fees.view'); $sid = Tenant::require();
        foreach ([$from, $to] as $d) { if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) { throw new HttpException(422, 'تاريخ غير صالح'); } }
        $base = "FROM payments p LEFT JOIN payment_methods m ON m.id = p.method_id WHERE p.school_id = ? AND p.paid_on BETWEEN ? AND ?"; $p = [$sid, $from, $to];
        return [
            'by_currency' => DB::all("SELECT p.currency, COUNT(*) AS n, SUM(p.amount) AS total $base AND p.voided_at IS NULL GROUP BY p.currency ORDER BY p.currency", $p),
            'by_method' => DB::all("SELECT m.name AS method, p.currency, COUNT(*) AS n, SUM(p.amount) AS total $base AND p.voided_at IS NULL GROUP BY m.name, p.currency ORDER BY m.name", $p),
            'by_day' => DB::all("SELECT p.paid_on AS d, p.currency, SUM(p.amount) AS total $base AND p.voided_at IS NULL GROUP BY p.paid_on, p.currency ORDER BY p.paid_on", $p),
            'voided' => (int)DB::value("SELECT COUNT(*) $base AND p.voided_at IS NOT NULL", $p),
            'rows' => DB::all("SELECT p.id, p.paid_on, p.currency, p.amount, p.reference_no, p.voided_at, r.number AS receipt_no, r.id AS receipt_id, m.name AS method, s.full_name AS student, u.full_name AS cashier
                FROM payments p LEFT JOIN receipts r ON r.payment_id = p.id LEFT JOIN payment_methods m ON m.id = p.method_id JOIN student_fees sf ON sf.id = p.student_fee_id JOIN student_enrollments e ON e.id = sf.enrollment_id JOIN students s ON s.id = e.student_id LEFT JOIN users u ON u.id = p.received_by
                WHERE p.school_id = ? AND p.paid_on BETWEEN ? AND ? ORDER BY p.id DESC LIMIT 1000", $p),
        ];
    }

    /** إجمالي المستحق والمحصّل والمتبقي لكل عملة (للوحة الرسوم). */
    public static function outstanding(): array
    {
        Auth::authorize('fees.view'); $sid = Tenant::require();
        $rows = DB::all("SELECT sf.currency, COUNT(*) AS n, SUM(sf.payable) AS payable, SUM((SELECT COALESCE(SUM(p.applied_amount),0) FROM payments p WHERE p.student_fee_id = sf.id AND p.voided_at IS NULL)) AS paid
            FROM student_fees sf WHERE sf.school_id = ? AND sf.status = 'active' GROUP BY sf.currency ORDER BY sf.currency", [$sid]);
        foreach ($rows as &$r) { $r['balance'] = Money::str(Money::cents($r['payable']) - Money::cents($r['paid'])); }
        return $rows;
    }

    /** كشف حساب ولي أمر (أو طالب): كل الرسوم والأقساط والدفعات، مفصولة بالعملة. */
    public static function statement(int $studentId): array
    {
        $sid = Tenant::require(); $portal = in_array(Auth::type(), ['guardian', 'student'], true);
        $portal ? PortalService::assertStudent($studentId) : Auth::authorize('fees.view');
        $stu = DB::one('SELECT id, full_name, student_no FROM students WHERE id = ? AND school_id = ?', [$studentId, $sid]) ?? throw new HttpException(404, 'الطالب غير موجود');
        $fees = DB::all("SELECT sf.id, sf.currency, sf.gross, sf.discount_total, sf.tax_amount, sf.payable, sf.discount_reason, fs.name AS structure_name, y.name AS year_name FROM student_fees sf JOIN student_enrollments e ON e.id = sf.enrollment_id JOIN fee_structures fs ON fs.id = sf.structure_id JOIN academic_years y ON y.id = fs.academic_year_id
            WHERE e.student_id = ? AND sf.school_id = ? AND sf.status = 'active' ORDER BY y.starts_on DESC, sf.id", [$studentId, $sid]);
        $totals = [];
        foreach ($fees as &$f) {
            $f['installments'] = DB::all('SELECT seq, due_date, amount, paid_amount, status FROM installments WHERE student_fee_id = ? ORDER BY seq', [$f['id']]);
            $f['payments'] = DB::all('SELECT p.paid_on, p.amount, p.currency, p.applied_amount, p.voided_at, r.number AS receipt_no, r.id AS receipt_id, m.name AS method FROM payments p LEFT JOIN receipts r ON r.payment_id = p.id LEFT JOIN payment_methods m ON m.id = p.method_id WHERE p.student_fee_id = ? ORDER BY p.paid_on, p.id', [$f['id']]);
            $paid = 0; foreach ($f['payments'] as $p) { if (!$p['voided_at']) { $paid += Money::cents($p['applied_amount']); } }
            $f['paid'] = Money::str($paid); $f['balance'] = Money::str(Money::cents($f['payable']) - $paid);
            $t = &$totals[$f['currency']]; $t['payable'] = ($t['payable'] ?? 0) + Money::cents($f['payable']); $t['paid'] = ($t['paid'] ?? 0) + $paid; unset($t);
        }
        $out = []; foreach ($totals as $cur => $t) { $out[$cur] = ['payable' => Money::str($t['payable']), 'paid' => Money::str($t['paid']), 'balance' => Money::str($t['payable'] - $t['paid'])]; }
        return ['student' => $stu, 'fees' => $fees, 'totals' => $out, 'school' => DB::one('SELECT name, city, phone FROM schools WHERE id = ?', [$sid]), 'tax' => FeeService::tax()];
    }
}
