<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\{Auth, DB, HttpException, Tenant};

final class InvoiceService
{
    /** يصدر فاتورة رسمية مرقمة لرسوم طالب (واحدة فعّالة لكل رسوم). السعر المرجعي للعملة الأساسية يُثبَّت عند الإصدار. */
    public static function issue(int $studentFeeId): int
    {
        Auth::authorize('fees.manage'); $sid = Tenant::require();
        return DB::transaction(function () use ($studentFeeId, $sid) {
            $f = DB::one('SELECT * FROM student_fees WHERE id = ? AND school_id = ? FOR UPDATE', [$studentFeeId, $sid]) ?? throw new HttpException(404, 'رسوم الطالب غير موجودة');
            if ($f['status'] !== 'active') { throw new HttpException(422, 'الرسوم ملغاة'); }
            if (DB::value("SELECT 1 FROM invoices WHERE student_fee_id = ? AND school_id = ? AND status <> 'cancelled'", [$studentFeeId, $sid])) { throw new HttpException(409, 'توجد فاتورة فعّالة لهذه الرسوم'); }
            $rate = PaymentService::latestRate($f['currency']) ?? throw new HttpException(422, 'حدّد سعر صرف ' . $f['currency'] . ' أولًا');
            $guardian = DB::value('SELECT sg.guardian_id FROM student_fees sf JOIN student_enrollments e ON e.id = sf.enrollment_id JOIN student_guardians sg ON sg.student_id = e.student_id AND sg.school_id = sf.school_id WHERE sf.id = ? ORDER BY sg.financially_responsible DESC, sg.is_primary DESC LIMIT 1', [$studentFeeId]);
            $year = (int)date('Y'); $no = Sequence::format(SettingsService::get('fees.invoice_prefix', 'INV') ?: 'INV', $year, Sequence::next('invoice', $year));
            $tax = Money::cents($f['tax_amount']); $payable = Money::cents($f['payable']); $subtotal = $payable - $tax;
            DB::query('INSERT INTO invoices (school_id, number, guardian_id, student_fee_id, currency, rate_to_base, subtotal, tax_percent, tax_amount, total, status, issued_on, created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)',
                [$sid, $no, $guardian ?: null, $studentFeeId, $f['currency'], $rate, Money::str($subtotal), $f['tax_percent'], Money::str($tax), Money::str($payable), 'issued', date('Y-m-d'), Auth::id()]);
            $id = DB::lastId();
            foreach (DB::all('SELECT i.name, i.amount FROM fee_items i WHERE i.structure_id = ?', [$f['structure_id']]) as $it) { DB::query('INSERT INTO invoice_lines (invoice_id, description, amount) VALUES (?,?,?)', [$id, $it['name'], $it['amount']]); }
            if (Money::cents($f['discount_total']) > 0) { DB::query('INSERT INTO invoice_lines (invoice_id, description, amount) VALUES (?,?,?)', [$id, 'خصم' . ($f['discount_reason'] ? ': ' . $f['discount_reason'] : ''), '-' . $f['discount_total']]); }
            PaymentService::syncInvoice($studentFeeId);
            AuditService::log('invoice.issue', 'invoice', $id, null, ['number' => $no, 'total' => Money::str($payable) . ' ' . $f['currency']]);
            return $id;
        });
    }

    public static function cancel(int $id, string $reason): void
    {
        Auth::authorize('fees.void');
        if (mb_strlen(trim($reason)) < 5) { throw new HttpException(422, 'اذكر سبب الإلغاء (5 أحرف على الأقل)', ['reason' => 'مطلوب']); }
        $sid = Tenant::require();
        DB::transaction(function () use ($id, $reason, $sid) {
            $i = DB::one('SELECT * FROM invoices WHERE id = ? AND school_id = ? FOR UPDATE', [$id, $sid]) ?? throw new HttpException(404, 'الفاتورة غير موجودة');
            if ($i['status'] === 'cancelled') { return; }
            if (DB::value('SELECT 1 FROM payments WHERE student_fee_id = ? AND school_id = ? AND voided_at IS NULL LIMIT 1', [$i['student_fee_id'], $sid])) { throw new HttpException(409, 'توجد دفعات على هذه الرسوم؛ ألغِ السندات أولًا'); }
            DB::query("UPDATE invoices SET status = 'cancelled', cancelled_reason = ? WHERE id = ?", [mb_substr($reason, 0, 255), $id]);
            AuditService::log('invoice.cancel', 'invoice', $id, ['number' => $i['number']], ['reason' => $reason]);
        });
    }

    public static function find(int $id): array
    {
        $portal = in_array(Auth::type(), ['guardian', 'student'], true); $portal ? Auth::authorize('portal.view') : Auth::authorize('fees.view'); $sid = Tenant::require();
        $i = DB::one("SELECT i.*, s.id AS student_id, s.full_name AS student_name, s.student_no, sch.name AS school_name, sch.city, sch.phone AS school_phone, g.full_name AS guardian_name, y.name AS year_name, sc.name AS section_name, gl.name AS grade_name
            FROM invoices i JOIN student_fees sf ON sf.id = i.student_fee_id JOIN student_enrollments e ON e.id = sf.enrollment_id JOIN students s ON s.id = e.student_id JOIN sections sc ON sc.id = e.section_id JOIN grade_levels gl ON gl.id = sc.grade_level_id
            JOIN academic_years y ON y.id = e.academic_year_id JOIN schools sch ON sch.id = i.school_id LEFT JOIN guardians g ON g.id = i.guardian_id WHERE i.id = ? AND i.school_id = ?", [$id, $sid]) ?? throw new HttpException(404, 'الفاتورة غير موجودة');
        if ($portal && !in_array((int)$i['student_id'], PortalService::studentIds(), true)) { throw new HttpException(403, 'لا تملك صلاحية عرض هذه الفاتورة'); }
        $i['lines'] = DB::all('SELECT description, amount FROM invoice_lines WHERE invoice_id = ? ORDER BY id', [$id]);
        $i['tax'] = FeeService::tax(); return $i;
    }
}
