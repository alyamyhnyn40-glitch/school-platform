<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\{Auth, DB, HttpException, Tenant};

/** أسعار الصرف وطرق الدفع وتحصيل الدفعات وإلغاؤها وسندات القبض. */
final class PaymentService
{
    // ---------- أسعار الصرف ----------
    public static function baseCurrency(): string { return (string)DB::value('SELECT base_currency FROM schools WHERE id = ?', [Tenant::require()]); }
    /** سعر وحدة العملة بالعملة الأساسية (الأساسية = 1). يعيد null إن لم يُحدد. */
    public static function latestRate(string $currency): ?string
    {
        if ($currency === self::baseCurrency()) { return '1.000000'; }
        $r = DB::value('SELECT rate_to_base FROM exchange_rates WHERE school_id = ? AND currency = ? ORDER BY effective_from DESC, id DESC LIMIT 1', [Tenant::require(), $currency]);
        return $r === null ? null : (string)$r;
    }
    public static function rates(): array
    {
        Auth::authorize('fees.view');
        return DB::all('SELECT x.*, u.full_name AS by_name FROM exchange_rates x LEFT JOIN users u ON u.id = x.created_by WHERE x.school_id = ? ORDER BY x.effective_from DESC, x.id DESC LIMIT 60', [Tenant::require()]);
    }
    public static function setRate(string $currency, string $rate): void
    {
        Auth::authorize('fees.manage');
        if (!in_array($currency, FeeService::CURRENCIES, true)) { throw new HttpException(422, 'عملة غير صالحة'); }
        if ($currency === self::baseCurrency()) { throw new HttpException(422, 'العملة الأساسية سعرها 1 ثابت'); }
        if (!is_numeric($rate) || (float)$rate <= 0 || (float)$rate > 1e9) { throw new HttpException(422, 'سعر صرف غير صالح', ['rate' => 'غير صالح']); }
        DB::query('INSERT INTO exchange_rates (school_id, currency, rate_to_base, created_by) VALUES (?,?,?,?)', [Tenant::require(), $currency, round((float)$rate, 6), Auth::id()]);
        AuditService::log('fees.rate', 'exchange_rate', DB::lastId(), null, ['currency' => $currency, 'rate' => $rate]);
    }

    // ---------- طرق الدفع ----------
    public static function methods(bool $onlyActive = false): array
    {
        Auth::authorize('fees.view');
        return DB::all('SELECT * FROM payment_methods WHERE school_id = ?' . ($onlyActive ? ' AND is_active = 1' : '') . ' ORDER BY kind, name', [Tenant::require()]);
    }
    public static function saveMethod(string $kind, string $name, ?string $info): int
    {
        Auth::authorize('fees.manage');
        if (!in_array($kind, ['cash', 'bank', 'wallet', 'exchange'], true) || trim($name) === '') { throw new HttpException(422, 'بيانات طريقة الدفع غير صالحة'); }
        DB::query('INSERT INTO payment_methods (school_id, kind, name, account_info) VALUES (?,?,?,?)', [Tenant::require(), $kind, trim($name), $info ?: null]);
        return DB::lastId();
    }
    public static function toggleMethod(int $id, bool $active): void
    {
        Auth::authorize('fees.manage');
        DB::query('UPDATE payment_methods SET is_active = ? WHERE id = ? AND school_id = ?', [(int)$active, $id, Tenant::require()]);
    }

    // ---------- التحصيل ----------
    /**
     * يسجل دفعة ويصدر سندًا مرقمًا. المبلغ بعملة الدفع؛ يُحوَّل لعملة الرسوم بأسعار الصرف المثبَّتة على الدفعة.
     * @return array{payment_id:int, receipt_id:int, receipt_no:string, applied:string, balance:string}
     */
    public static function receive(int $studentFeeId, string $amount, string $currency, int $methodId, ?string $reference, ?string $paidOn = null, ?string $rateOverride = null): array
    {
        Auth::authorize('fees.collect');
        return self::doReceive($studentFeeId, $amount, $currency, $methodId, $reference, $paidOn, $rateOverride, Auth::id());
    }
    /**
     * قيد دفعة من بوابة الدفع الإلكتروني بعد التحقق منها (بلا مستخدم). يتطلب سياق مدرسة. لا يُستدعى إلا من OnlinePaymentService بعد التحقق من الإشعار.
     * @internal
     */
    public static function receiveVerifiedOnline(int $studentFeeId, string $amount, string $currency, string $reference): array
    {
        $sid = Tenant::require();
        $m = DB::value("SELECT id FROM payment_methods WHERE school_id = ? AND kind = 'wallet' AND name = 'دفع إلكتروني' LIMIT 1", [$sid]);
        if (!$m) { DB::query("INSERT INTO payment_methods (school_id, kind, name, account_info) VALUES (?, 'wallet', 'دفع إلكتروني', 'بوابة الدفع الإلكتروني')", [$sid]); $m = DB::lastId(); }
        else { DB::query('UPDATE payment_methods SET is_active = 1 WHERE id = ?', [$m]); }
        return self::doReceive($studentFeeId, $amount, $currency, (int)$m, $reference, null, null, null);
    }
    private static function doReceive(int $studentFeeId, string $amount, string $currency, int $methodId, ?string $reference, ?string $paidOn, ?string $rateOverride, ?int $by): array
    {
        $sid = Tenant::require();
        if (!in_array($currency, FeeService::CURRENCIES, true)) { throw new HttpException(422, 'عملة غير صالحة', ['currency' => 'غير صالحة']); }
        try { $cents = Money::cents($amount); } catch (\InvalidArgumentException) { throw new HttpException(422, 'مبلغ غير صالح', ['amount' => 'غير صالح']); }
        if ($cents <= 0) { throw new HttpException(422, 'المبلغ يجب أن يكون أكبر من صفر', ['amount' => 'غير صالح']); }
        $paidOn = $paidOn ?: date('Y-m-d');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $paidOn) || $paidOn > date('Y-m-d')) { throw new HttpException(422, 'تاريخ الدفع غير صالح أو مستقبلي', ['paid_on' => 'غير صالح']); }
        $m = DB::one('SELECT * FROM payment_methods WHERE id = ? AND school_id = ? AND is_active = 1', [$methodId, $sid]) ?? throw new HttpException(422, 'طريقة الدفع غير صالحة', ['method_id' => 'غير صالحة']);
        $reference = trim((string)$reference);
        if ($m['kind'] !== 'cash' && $reference === '') { throw new HttpException(422, 'رقم المرجع مطلوب لهذه الطريقة', ['reference_no' => 'مطلوب']); }
        return DB::transaction(function () use ($studentFeeId, $cents, $currency, $m, $reference, $paidOn, $rateOverride, $sid, $by) {
            $f = DB::one("SELECT * FROM student_fees WHERE id = ? AND school_id = ? FOR UPDATE", [$studentFeeId, $sid]) ?? throw new HttpException(404, 'رسوم الطالب غير موجودة');
            if ($f['status'] !== 'active') { throw new HttpException(422, 'هذه الرسوم ملغاة'); }
            $rPay = $rateOverride !== null && $rateOverride !== '' ? $rateOverride : self::latestRate($currency);
            $rFee = self::latestRate($f['currency']);
            if ($currency !== self::baseCurrency() && ($rPay === null || !is_numeric($rPay) || (float)$rPay <= 0)) { throw new HttpException(422, "حدّد سعر صرف $currency مقابل " . self::baseCurrency() . ' أولًا', ['rate' => 'مطلوب']); }
            if ($currency === self::baseCurrency()) { $rPay = '1.000000'; }
            if ($f['currency'] !== $currency && $rFee === null) { throw new HttpException(422, 'حدّد سعر صرف ' . $f['currency'] . ' أولًا'); }
            $applied = $currency === $f['currency'] ? $cents : Money::convert($cents, Money::rate($rPay), Money::rate($rFee));
            $paid = Money::cents(DB::value('SELECT COALESCE(SUM(applied_amount),0) FROM payments WHERE student_fee_id = ? AND school_id = ? AND voided_at IS NULL', [$studentFeeId, $sid]));
            $balance = Money::cents($f['payable']) - $paid;
            if ($applied <= 0) { throw new HttpException(422, 'المبلغ صغير جدًا بعد التحويل'); }
            if ($applied > $balance) { throw new HttpException(422, 'المبلغ يتجاوز المتبقي (' . Money::fmt($balance, $f['currency']) . ' ≈ ' . ($currency === $f['currency'] ? '' : Money::fmt(Money::convert($balance, Money::rate($rFee), Money::rate($rPay)), $currency)) . ')', ['amount' => 'يتجاوز المتبقي']); }
            DB::query('INSERT INTO payments (school_id, student_fee_id, method_id, currency, amount, rate_to_base, fee_currency, fee_rate_to_base, applied_amount, reference_no, paid_on, received_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',
                [$sid, $studentFeeId, $m['id'], $currency, Money::str($cents), $rPay, $f['currency'], $rFee, Money::str($applied), $reference ?: null, $paidOn, $by]);
            $pid = DB::lastId();
            $left = $applied;   // توزيع على أقدم الأقساط المستحقة أولًا
            foreach (DB::all("SELECT * FROM installments WHERE student_fee_id = ? AND school_id = ? AND status <> 'paid' ORDER BY due_date, seq FOR UPDATE", [$studentFeeId, $sid]) as $i) {
                if ($left <= 0) { break; }
                $room = Money::cents($i['amount']) - Money::cents($i['paid_amount']); $take = min($room, $left); if ($take <= 0) { continue; }
                $newPaid = Money::cents($i['paid_amount']) + $take;
                DB::query('UPDATE installments SET paid_amount = ?, status = ? WHERE id = ?', [Money::str($newPaid), $newPaid >= Money::cents($i['amount']) ? 'paid' : 'partial', $i['id']]);
                DB::query('INSERT INTO payment_allocations (payment_id, installment_id, amount) VALUES (?,?,?)', [$pid, $i['id'], Money::str($take)]);
                $left -= $take;
            }
            if ($left !== 0) { throw new \LogicException('تعذّر توزيع المبلغ على الأقساط'); }   // لا يحدث: المبلغ ≤ المتبقي = مجموع غير المسدد
            $year = (int)date('Y', strtotime($paidOn)); $seq = Sequence::next('receipt', $year);
            $no = Sequence::format(SettingsService::get('fees.receipt_prefix', 'RC') ?: 'RC', $year, $seq);
            DB::query('INSERT INTO receipts (school_id, payment_id, number) VALUES (?,?,?)', [$sid, $pid, $no]); $rid = DB::lastId();
            self::syncInvoice($studentFeeId);
            AuditService::log('payment.receive', 'payment', $pid, null, ['receipt' => $no, 'amount' => Money::str($cents), 'currency' => $currency, 'applied' => Money::str($applied) . ' ' . $f['currency'], 'method' => $m['name']]);
            return ['payment_id' => $pid, 'receipt_id' => $rid, 'receipt_no' => $no, 'applied' => Money::str($applied), 'balance' => Money::str($balance - $applied)];
        });
    }

    /** إلغاء دفعة: يعكس التوزيع على الأقساط ويُبقي السند (بعلامة ملغى) ولا يُعاد استخدام رقمه. */
    public static function void(int $paymentId, string $reason): void
    {
        Auth::authorize('fees.void');
        if (mb_strlen(trim($reason)) < 5) { throw new HttpException(422, 'اذكر سبب الإلغاء (5 أحرف على الأقل)', ['reason' => 'مطلوب']); }
        $sid = Tenant::require();
        DB::transaction(function () use ($paymentId, $reason, $sid) {
            $p = DB::one('SELECT * FROM payments WHERE id = ? AND school_id = ? FOR UPDATE', [$paymentId, $sid]) ?? throw new HttpException(404, 'الدفعة غير موجودة');
            if ($p['voided_at']) { throw new HttpException(422, 'الدفعة ملغاة مسبقًا'); }
            DB::query('UPDATE payments SET voided_at = NOW(), void_reason = ? WHERE id = ?', [mb_substr($reason, 0, 255), $paymentId]);
            if ($p['student_fee_id']) { self::reallocate((int)$p['student_fee_id']); self::syncInvoice((int)$p['student_fee_id']); }
            AuditService::log('payment.void', 'payment', $paymentId, ['amount' => $p['amount'], 'currency' => $p['currency']], ['reason' => $reason]);
        });
    }

    /** يعيد توزيع كل الدفعات الصحيحة (غير الملغاة) على الأقساط من الأقدم، فلا تبقى دفعة على قسط لاحق بينما قسط أقدم مفتوح. */
    private static function reallocate(int $feeId): void
    {
        $sid = Tenant::require();
        $inst = DB::all('SELECT id, amount FROM installments WHERE student_fee_id = ? AND school_id = ? ORDER BY due_date, seq FOR UPDATE', [$feeId, $sid]);
        DB::query('DELETE a FROM payment_allocations a JOIN payments p ON p.id = a.payment_id WHERE p.student_fee_id = ? AND p.school_id = ?', [$feeId, $sid]);
        $room = []; foreach ($inst as $i) { $room[$i['id']] = ['cap' => Money::cents($i['amount']), 'paid' => 0]; }
        foreach (DB::all('SELECT id, applied_amount FROM payments WHERE student_fee_id = ? AND school_id = ? AND voided_at IS NULL ORDER BY paid_on, id', [$feeId, $sid]) as $p) {
            $left = Money::cents($p['applied_amount']);
            foreach ($room as $iid => &$r) {
                if ($left <= 0) { break; } $take = min($r['cap'] - $r['paid'], $left); if ($take <= 0) { continue; }
                $r['paid'] += $take; $left -= $take; DB::query('INSERT INTO payment_allocations (payment_id, installment_id, amount) VALUES (?,?,?)', [$p['id'], $iid, Money::str($take)]);
            } unset($r);
        }
        foreach ($room as $iid => $r) { DB::query('UPDATE installments SET paid_amount = ?, status = ? WHERE id = ?', [Money::str($r['paid']), $r['paid'] <= 0 ? 'pending' : ($r['paid'] >= $r['cap'] ? 'paid' : 'partial'), $iid]); }
    }

    /** يحدّث حالة فاتورة الرسوم حسب المدفوع. */
    public static function syncInvoice(int $studentFeeId): void
    {
        $sid = Tenant::require();
        $inv = DB::one("SELECT id, total FROM invoices WHERE student_fee_id = ? AND school_id = ? AND status <> 'cancelled' ORDER BY id DESC LIMIT 1 FOR UPDATE", [$studentFeeId, $sid]);
        if (!$inv) { return; }
        $paid = Money::cents(DB::value('SELECT COALESCE(SUM(applied_amount),0) FROM payments WHERE student_fee_id = ? AND school_id = ? AND voided_at IS NULL', [$studentFeeId, $sid]));
        $t = Money::cents($inv['total']);
        DB::query('UPDATE invoices SET status = ? WHERE id = ?', [$paid >= $t ? 'paid' : ($paid > 0 ? 'partial' : 'issued'), $inv['id']]);
    }

    /** بيانات سند للطباعة. للموظف (fees.view) أو لولي الأمر المرتبط بالطالب. */
    public static function receipt(int $receiptId): array
    {
        $sid = Tenant::require(); $portal = in_array(Auth::type(), ['guardian', 'student'], true);
        $portal ? Auth::authorize('portal.view') : Auth::authorize('fees.view');
        $r = DB::one("SELECT r.id, r.number, r.issued_at, p.*, m.name AS method_name, m.kind AS method_kind, s.id AS student_id, s.full_name AS student_name, s.student_no, sch.name AS school_name, sch.city, sch.phone AS school_phone, u.full_name AS received_by_name,
                fs.name AS structure_name, sf.payable, sf.currency AS fee_cur
            FROM receipts r JOIN payments p ON p.id = r.payment_id JOIN student_fees sf ON sf.id = p.student_fee_id JOIN student_enrollments e ON e.id = sf.enrollment_id JOIN students s ON s.id = e.student_id
            JOIN fee_structures fs ON fs.id = sf.structure_id JOIN schools sch ON sch.id = r.school_id LEFT JOIN payment_methods m ON m.id = p.method_id LEFT JOIN users u ON u.id = p.received_by WHERE r.id = ? AND r.school_id = ?", [$receiptId, $sid]) ?? throw new HttpException(404, 'السند غير موجود');
        if ($portal && !in_array((int)$r['student_id'], PortalService::studentIds(), true)) { throw new HttpException(403, 'لا تملك صلاحية عرض هذا السند'); }
        $paid = Money::cents(DB::value('SELECT COALESCE(SUM(applied_amount),0) FROM payments WHERE student_fee_id = ? AND school_id = ? AND voided_at IS NULL', [$r['student_fee_id'], $sid]));
        $r['balance_now'] = Money::str(Money::cents($r['payable']) - $paid);
        $r['guardian'] = DB::value('SELECT g.full_name FROM student_guardians sg JOIN guardians g ON g.id = sg.guardian_id WHERE sg.student_id = ? AND sg.school_id = ? ORDER BY sg.financially_responsible DESC, sg.is_primary DESC LIMIT 1', [$r['student_id'], $sid]);
        return $r;
    }
}
