<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\{Auth, DB, HttpException, Tenant};

/**
 * طرق الدفع كما تظهر لولي الأمر عند السداد: بوابة الدفع الإلكتروني (إن فُعّلت) وطرق يدوية بتعليمات (تحويل بنكي/محفظة/نقدًا في المدرسة).
 * المدير يتحكم بالظهور والترتيب والنص. الطرق اليدوية تنتج «إشعار دفع» يراجعه المحاسب ثم يُقيَّد عبر مسار التحصيل المعتاد (سند مرقّم).
 */
final class CheckoutService
{
    public const CLAIM_STATUSES = ['pending' => 'قيد المراجعة', 'approved' => 'مقبول', 'rejected' => 'مرفوض'];

    private static function ensureDefaults(): void
    {
        $sid = Tenant::require(); if (DB::value('SELECT 1 FROM checkout_methods WHERE school_id = ? LIMIT 1', [$sid])) { return; }
        DB::query("INSERT INTO checkout_methods (school_id, kind, label, instructions, is_visible, sort_order) VALUES (?, 'gateway', 'دفع إلكتروني (بطاقة / محفظة)', 'ادفع بأمان عبر بوابة الدفع. يصدر سند القبض تلقائيًا بعد تأكيد الدفع.', 1, 0)", [$sid]);
        DB::query("INSERT INTO checkout_methods (school_id, kind, label, instructions, is_visible, sort_order) VALUES (?, 'manual', 'تحويل بنكي أو عبر محفظة', 'حوّل المبلغ إلى الحساب الموضّح، ثم أبلغنا بالتحويل برقم العملية وصورة الإشعار.\nاكتب هنا رقم الحساب/المحفظة واسم المستفيد.', 0, 1)", [$sid]);
        DB::query("INSERT INTO checkout_methods (school_id, kind, label, instructions, is_visible, sort_order) VALUES (?, 'manual', 'نقدًا في المدرسة', 'يمكنك السداد نقدًا في إدارة المدرسة خلال أوقات الدوام، وسيُسلَّم لك سند قبض.', 0, 2)", [$sid]);
    }
    // ---------- الإدارة (مدير المدرسة) ----------
    public static function manage(): array
    {
        Auth::authorize('fees.gateway'); self::ensureDefaults();
        return DB::all('SELECT * FROM checkout_methods WHERE school_id = ? ORDER BY sort_order, id', [Tenant::require()]);
    }
    public static function save(?int $id, string $label, ?string $instructions, bool $visible, int $sort): int
    {
        Auth::authorize('fees.gateway'); $sid = Tenant::require(); self::ensureDefaults();
        $label = trim($label); if (mb_strlen($label) < 2 || mb_strlen($label) > 100) { throw new HttpException(422, 'اسم الطريقة مطلوب (حتى 100 حرف)', ['label' => 'مطلوب']); }
        $ins = $instructions !== null ? mb_substr(trim($instructions), 0, 2000) : null; $sort = max(0, min(999, $sort));
        if ($id) {
            $m = DB::one('SELECT * FROM checkout_methods WHERE id = ? AND school_id = ?', [$id, $sid]) ?? throw new HttpException(404, 'الطريقة غير موجودة');
            DB::query('UPDATE checkout_methods SET label = ?, instructions = ?, is_visible = ?, sort_order = ? WHERE id = ?', [$label, $ins ?: null, (int)$visible, $sort, $id]);
        } else { DB::query("INSERT INTO checkout_methods (school_id, kind, label, instructions, is_visible, sort_order) VALUES (?, 'manual', ?, ?, ?, ?)", [$sid, $label, $ins ?: null, (int)$visible, $sort]); $id = DB::lastId(); }
        AuditService::log('checkout.method', 'checkout_method', $id, null, ['label' => $label, 'visible' => $visible]); return $id;
    }
    public static function setVisible(int $id, bool $v): void
    {
        Auth::authorize('fees.gateway'); $m = DB::one('SELECT * FROM checkout_methods WHERE id = ? AND school_id = ?', [$id, Tenant::require()]) ?? throw new HttpException(404, 'الطريقة غير موجودة');
        DB::query('UPDATE checkout_methods SET is_visible = ? WHERE id = ?', [(int)$v, $id]); AuditService::log('checkout.visibility', 'checkout_method', $id, ['visible' => (bool)$m['is_visible']], ['visible' => $v]);
    }
    public static function delete(int $id): void
    {
        Auth::authorize('fees.gateway'); $m = DB::one('SELECT * FROM checkout_methods WHERE id = ? AND school_id = ?', [$id, Tenant::require()]) ?? throw new HttpException(404, 'الطريقة غير موجودة');
        if ($m['kind'] === 'gateway') { throw new HttpException(422, 'لا تُحذف طريقة الدفع الإلكتروني؛ أخفها بدلًا من ذلك'); }
        if (DB::value('SELECT 1 FROM payment_claims WHERE method_id = ? LIMIT 1', [$id])) { throw new HttpException(409, 'عليها إشعارات دفع مسجّلة؛ أخفها بدلًا من حذفها'); }
        DB::query('DELETE FROM checkout_methods WHERE id = ?', [$id]); AuditService::log('checkout.method_delete', 'checkout_method', $id, ['label' => $m['label']]);
    }

    // ---------- ما يراه ولي الأمر ----------
    /** الطرق الظاهرة فعليًا: البوابة تظهر فقط إن كانت مفعّلة وغير محجوبة من المنصة؛ اليدوية إن أُظهرت. */
    public static function visible(): array
    {
        Auth::authorize('portal.view'); self::ensureDefaults(); $out = [];
        foreach (DB::all('SELECT * FROM checkout_methods WHERE school_id = ? AND is_visible = 1 ORDER BY sort_order, id', [Tenant::require()]) as $m) {
            if ($m['kind'] === 'gateway' && !(OnlinePaymentService::enabled() && Brand::enabled('allow_online_payments'))) { continue; }
            $out[] = $m;
        }
        return $out;
    }

    /** هل طريقة البوابة ظاهرة للأولياء؟ (تُفحص عند بدء الدفع الإلكتروني أيضًا). */
    public static function gatewayVisible(): bool { foreach (self::visible() as $m) { if ($m['kind'] === 'gateway') { return true; } } return false; }

    // ---------- إشعار الدفع اليدوي ----------
    public static function submitClaim(int $studentFeeId, int $methodId, string $amount, string $reference, string $paidOn, ?string $note, ?array $receipt): int
    {
        Auth::authorize('portal.view'); $sid = Tenant::require();
        if (Auth::type() !== 'guardian') { throw new HttpException(403, 'الإشعار لولي الأمر'); }
        $m = DB::one("SELECT * FROM checkout_methods WHERE id = ? AND school_id = ? AND kind = 'manual' AND is_visible = 1", [$methodId, $sid]) ?? throw new HttpException(422, 'طريقة الدفع غير متاحة', ['method_id' => 'غير متاحة']);
        $s = OnlinePaymentService::suggest($studentFeeId); PortalService::assertStudent($s['student_id']);
        try { $cents = Money::cents($amount); } catch (\InvalidArgumentException) { throw new HttpException(422, 'مبلغ غير صالح', ['amount' => 'غير صالح']); }
        if ($cents <= 0) { throw new HttpException(422, 'المبلغ يجب أن يكون أكبر من صفر', ['amount' => 'غير صالح']); }
        if ($cents > $s['balance']) { throw new HttpException(422, 'المبلغ يتجاوز المتبقي (' . Money::fmt($s['balance'], $s['currency']) . ')', ['amount' => 'يتجاوز المتبقي']); }
        $ref = trim($reference); if (mb_strlen($ref) < 3 || mb_strlen($ref) > 100) { throw new HttpException(422, 'رقم العملية/المرجع مطلوب', ['reference_no' => 'مطلوب']); }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $paidOn) || $paidOn > date('Y-m-d') || $paidOn < date('Y-m-d', strtotime('-90 day'))) { throw new HttpException(422, 'تاريخ التحويل غير صالح (خلال آخر 90 يومًا)', ['paid_on' => 'غير صالح']); }
        if (DB::value("SELECT 1 FROM payment_claims WHERE school_id = ? AND reference_no = ? AND status IN ('pending','approved')", [$sid, $ref]) || DB::value('SELECT 1 FROM payments WHERE school_id = ? AND reference_no = ? AND voided_at IS NULL', [$sid, $ref])) { throw new HttpException(409, 'رقم العملية هذا مسجّل مسبقًا'); }
        if ((int)DB::value("SELECT COUNT(*) FROM payment_claims WHERE school_id = ? AND guardian_user_id = ? AND status = 'pending'", [$sid, Auth::id()]) >= 10) { throw new HttpException(429, 'لديك إشعارات كثيرة قيد المراجعة'); }
        return DB::transaction(function () use ($sid, $studentFeeId, $methodId, $cents, $s, $ref, $paidOn, $note, $receipt, $m) {
            DB::query('INSERT INTO payment_claims (school_id, student_fee_id, guardian_user_id, method_id, amount, currency, reference_no, paid_on, note) VALUES (?,?,?,?,?,?,?,?,?)', [$sid, $studentFeeId, Auth::id(), $methodId, Money::str($cents), $s['currency'], $ref, $paidOn, $note ? mb_substr(trim($note), 0, 255) : null]);
            $id = DB::lastId();
            if ($receipt) { $fid = FileService::storeImage($receipt, 'claim', $id); DB::query('UPDATE payment_claims SET receipt_file_id = ? WHERE id = ?', [$fid, $id]); }
            AuditService::log('payment_claim.submit', 'payment_claim', $id, null, ['amount' => Money::str($cents), 'method' => $m['label']]);
            try { NotificationService::notify(self::collectors(), 'إشعار دفع جديد', Money::fmt($cents, $s['currency']) . ' — ' . $m['label'], url('school/fees/claims')); } catch (\Throwable) {}
            return $id;
        });
    }
    private static function collectors(): array
    {
        return array_map('intval', array_column(DB::all("SELECT DISTINCT u.id FROM users u JOIN user_roles ur ON ur.user_id = u.id JOIN role_permissions rp ON rp.role_id = ur.role_id JOIN permissions p ON p.id = rp.permission_id WHERE u.school_id = ? AND u.user_type = 'staff' AND u.status = 'active' AND p.code = 'fees.collect'", [Tenant::require()]), 'id'));
    }
    public static function pendingCount(): int { return Auth::can('fees.view') ? (int)DB::value("SELECT COUNT(*) FROM payment_claims WHERE school_id = ? AND status = 'pending'", [Tenant::require()]) : 0; }
    public static function claims(string $status = ''): array
    {
        Auth::authorize('fees.view'); $w = 'c.school_id = ?'; $p = [Tenant::require()]; if (isset(self::CLAIM_STATUSES[$status])) { $w .= ' AND c.status = ?'; $p[] = $status; }
        return DB::all("SELECT c.*, m.label AS method_label, s.full_name, s.student_no, u.full_name AS guardian, d.full_name AS decided_name FROM payment_claims c JOIN checkout_methods m ON m.id = c.method_id JOIN student_fees sf ON sf.id = c.student_fee_id JOIN student_enrollments e ON e.id = sf.enrollment_id JOIN students s ON s.id = e.student_id
            LEFT JOIN users u ON u.id = c.guardian_user_id LEFT JOIN users d ON d.id = c.decided_by WHERE $w ORDER BY (c.status = 'pending') DESC, c.id DESC LIMIT 200", $p);
    }
    public static function myClaims(int $studentFeeId): array
    {
        Auth::authorize('portal.view'); return DB::all('SELECT c.id, c.amount, c.currency, c.reference_no, c.paid_on, c.status, c.decision_note, m.label AS method_label FROM payment_claims c JOIN checkout_methods m ON m.id = c.method_id WHERE c.school_id = ? AND c.student_fee_id = ? AND c.guardian_user_id = ? ORDER BY c.id DESC LIMIT 10', [Tenant::require(), $studentFeeId, Auth::id()]);
    }
    /** يعتمد الإشعار: يقيّد دفعة عبر مسار التحصيل المعتاد (سند مرقّم) بطريقة دفع باسم الطريقة. */
    public static function approve(int $id): array
    {
        Auth::authorize('fees.collect'); $sid = Tenant::require();
        return DB::transaction(function () use ($id, $sid) {
            $c = DB::one('SELECT c.*, m.label FROM payment_claims c JOIN checkout_methods m ON m.id = c.method_id WHERE c.id = ? AND c.school_id = ? FOR UPDATE', [$id, $sid]) ?? throw new HttpException(404, 'الإشعار غير موجود');
            if ($c['status'] !== 'pending') { throw new HttpException(422, 'سبقت معالجة هذا الإشعار'); }
            $pm = DB::value("SELECT id FROM payment_methods WHERE school_id = ? AND kind = 'bank' AND name = ? LIMIT 1", [$sid, $c['label']]);
            if (!$pm) { DB::query("INSERT INTO payment_methods (school_id, kind, name, account_info) VALUES (?, 'bank', ?, 'من إشعارات الدفع')", [$sid, $c['label']]); $pm = DB::lastId(); }
            $r = PaymentService::receive((int)$c['student_fee_id'], $c['amount'], $c['currency'], (int)$pm, $c['reference_no'], $c['paid_on']);   // يتحقق من الرصيد ويصدر السند
            DB::query("UPDATE payment_claims SET status = 'approved', decided_by = ?, decided_at = ?, payment_id = ? WHERE id = ?", [Auth::id(), now(), $r['payment_id'], $id]);
            AuditService::log('payment_claim.approve', 'payment_claim', $id, null, ['receipt' => $r['receipt_no']]);
            try { NotificationService::notify([(int)$c['guardian_user_id']], 'تم قبول إشعار الدفع', 'سند القبض ' . $r['receipt_no'], url('portal')); } catch (\Throwable) {}
            return $r;
        });
    }
    public static function reject(int $id, string $reason): void
    {
        Auth::authorize('fees.collect'); $c = DB::one('SELECT * FROM payment_claims WHERE id = ? AND school_id = ?', [$id, Tenant::require()]) ?? throw new HttpException(404, 'الإشعار غير موجود');
        if ($c['status'] !== 'pending') { throw new HttpException(422, 'سبقت معالجة هذا الإشعار'); } if (mb_strlen(trim($reason)) < 3) { throw new HttpException(422, 'اكتب سبب الرفض', ['note' => 'مطلوب']); }
        DB::query("UPDATE payment_claims SET status = 'rejected', decided_by = ?, decided_at = ?, decision_note = ? WHERE id = ?", [Auth::id(), now(), mb_substr(trim($reason), 0, 255), $id]);
        AuditService::log('payment_claim.reject', 'payment_claim', $id, null, ['reason' => $reason]);
        try { NotificationService::notify([(int)$c['guardian_user_id']], 'تعذّر قبول إشعار الدفع', mb_substr(trim($reason), 0, 120), url('portal')); } catch (\Throwable) {}
    }
}
