<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\{Auth, DB, HttpException, Tenant};

/** العقود وكشوف الرواتب الشهرية. بيانات حساسة: صلاحية payroll.manage فقط، المبالغ بالهللات (Money)، وكل عملية في التدقيق. */
final class PayrollService
{
    // ---------- العقود ----------
    public static function contracts(?int $staffId = null): array
    {
        Auth::authorize('payroll.manage'); $w = 'c.school_id = ?'; $p = [Tenant::require()]; if ($staffId) { $w .= ' AND c.staff_id = ?'; $p[] = $staffId; }
        return DB::all("SELECT c.*, s.full_name, s.job_title, (c.status = 'active' AND (c.ends_on IS NULL OR c.ends_on >= CURDATE())) AS running FROM contracts c JOIN staff s ON s.id = c.staff_id WHERE $w AND s.deleted_at IS NULL ORDER BY c.status, s.full_name, c.starts_on DESC", $p);
    }
    public static function saveContract(int $staffId, string $kind, string $starts, ?string $ends, string $salary, string $currency, ?string $notes): int
    {
        Auth::authorize('payroll.manage'); $sid = Tenant::require();
        DB::value('SELECT 1 FROM staff WHERE id = ? AND school_id = ? AND deleted_at IS NULL', [$staffId, $sid]) ?? throw new HttpException(404, 'الموظف غير موجود');
        if (trim($kind) === '') { throw new HttpException(422, 'نوع العقد مطلوب', ['kind' => 'مطلوب']); } if (!in_array($currency, FeeService::CURRENCIES, true)) { throw new HttpException(422, 'عملة غير صالحة', ['currency' => 'غير صالحة']); }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $starts) || ($ends !== null && $ends !== '' && (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $ends) || $ends < $starts))) { throw new HttpException(422, 'تواريخ العقد غير صالحة', ['starts_on' => 'غير صالحة']); }
        try { $c = Money::cents($salary); } catch (\InvalidArgumentException) { throw new HttpException(422, 'الراتب غير صالح', ['base_salary' => 'غير صالح']); } if ($c <= 0) { throw new HttpException(422, 'الراتب يجب أن يكون أكبر من صفر', ['base_salary' => 'غير صالح']); }
        $ends = $ends === '' ? null : $ends;
        if (DB::value("SELECT 1 FROM contracts WHERE staff_id = ? AND school_id = ? AND status = 'active' AND starts_on <= COALESCE(?, '9999-12-31') AND COALESCE(ends_on, '9999-12-31') >= ?", [$staffId, $sid, $ends, $starts])) { throw new HttpException(409, 'يوجد عقد فعّال متداخل زمنيًا لهذا الموظف'); }
        DB::query("INSERT INTO contracts (school_id, staff_id, kind, starts_on, ends_on, base_salary, currency, status, notes) VALUES (?,?,?,?,?,?,?, 'active', ?)", [$sid, $staffId, trim($kind), $starts, $ends, Money::str($c), $currency, $notes ?: null]); $id = DB::lastId();
        AuditService::log('contract.create', 'contract', $id, null, ['staff_id' => $staffId, 'kind' => $kind, 'currency' => $currency]); return $id;   // لا نسجّل الراتب في التدقيق العام
    }
    public static function endContract(int $id, string $on): void
    {
        Auth::authorize('payroll.manage'); $c = DB::one('SELECT * FROM contracts WHERE id = ? AND school_id = ?', [$id, Tenant::require()]) ?? throw new HttpException(404, 'العقد غير موجود');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $on) || $on < $c['starts_on']) { throw new HttpException(422, 'تاريخ الإنهاء غير صالح'); }
        if (DB::value("SELECT 1 FROM payroll_items i JOIN payroll_runs r ON r.id = i.run_id WHERE i.contract_id = ? AND r.status <> 'draft' AND r.period > ?", [$id, substr($on, 0, 7)])) { throw new HttpException(422, 'توجد كشوف معتمدة لفترات بعد هذا التاريخ'); }
        DB::query("UPDATE contracts SET status = 'ended', ends_on = ? WHERE id = ?", [$on, $id]); AuditService::log('contract.end', 'contract', $id, null, ['ends_on' => $on]);
    }

    // ---------- الكشوف ----------
    public static function runs(): array
    {
        Auth::authorize('payroll.manage');
        return DB::all("SELECT r.*, (SELECT COUNT(*) FROM payroll_items i WHERE i.run_id = r.id) AS n FROM payroll_runs r WHERE r.school_id = ? ORDER BY r.period DESC", [Tenant::require()]);
    }
    /** ينشئ كشف شهر (مسودة) من العقود السارية في الشهر، بتناسب الأيام لمن بدأ/انتهى عقده خلال الشهر. */
    public static function createRun(string $period): int
    {
        Auth::authorize('payroll.manage'); $sid = Tenant::require();
        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $period)) { throw new HttpException(422, 'الشهر بصيغة YYYY-MM', ['period' => 'غير صالح']); }
        if (DB::value('SELECT 1 FROM payroll_runs WHERE school_id = ? AND period = ?', [$sid, $period])) { throw new HttpException(409, 'يوجد كشف لهذا الشهر'); }
        $first = "$period-01"; $dim = (int)date('t', strtotime($first)); $last = "$period-" . str_pad((string)$dim, 2, '0', STR_PAD_LEFT);
        $contracts = DB::all("SELECT c.*, s.full_name FROM contracts c JOIN staff s ON s.id = c.staff_id WHERE c.school_id = ? AND c.status IN ('active','ended') AND c.starts_on <= ? AND COALESCE(c.ends_on, '9999-12-31') >= ? AND s.deleted_at IS NULL AND s.status <> 'terminated' ORDER BY s.full_name", [$sid, $last, $first]);
        if (!$contracts) { throw new HttpException(422, 'لا توجد عقود سارية في هذا الشهر'); }
        return DB::transaction(function () use ($sid, $period, $first, $last, $dim, $contracts) {
            DB::query("INSERT INTO payroll_runs (school_id, period, status, created_by) VALUES (?,?, 'draft', ?)", [$sid, $period, Auth::id()]); $rid = DB::lastId();
            foreach ($contracts as $c) {
                $from = max($c['starts_on'], $first); $to = min($c['ends_on'] ?? $last, $last); $days = (int)((strtotime($to) - strtotime($from)) / 86400) + 1; $days = min($days, $dim);
                $base = Money::cents($c['base_salary']); $pro = $days >= $dim ? $base : intdiv($base * $days * 2 + $dim, $dim * 2);   // تناسب أيام الشهر، تقريب لأقرب هللة
                DB::query('INSERT INTO payroll_items (run_id, staff_id, contract_id, base, allowances, deductions, net, currency, days_worked, days_in_month) VALUES (?,?,?,?,0,0,?,?,?,?)', [$rid, $c['staff_id'], $c['id'], Money::str($pro), Money::str($pro), $c['currency'], $days, $dim]);
            }
            AuditService::log('payroll.create', 'payroll_run', $rid, null, ['period' => $period, 'items' => count($contracts)]); return $rid;
        });
    }
    private static function run(int $id): array { return DB::one('SELECT * FROM payroll_runs WHERE id = ? AND school_id = ?', [$id, Tenant::require()]) ?? throw new HttpException(404, 'الكشف غير موجود'); }
    public static function detail(int $id): array
    {
        Auth::authorize('payroll.manage'); $r = self::run($id);
        $r['items'] = DB::all('SELECT i.*, s.full_name, s.job_title FROM payroll_items i JOIN staff s ON s.id = i.staff_id WHERE i.run_id = ? ORDER BY s.full_name', [$id]); $t = [];
        foreach ($r['items'] as $i) { $x = &$t[$i['currency']]; $x['base'] = ($x['base'] ?? 0) + Money::cents($i['base']); $x['allowances'] = ($x['allowances'] ?? 0) + Money::cents($i['allowances']); $x['deductions'] = ($x['deductions'] ?? 0) + Money::cents($i['deductions']); $x['net'] = ($x['net'] ?? 0) + Money::cents($i['net']); unset($x); }
        $r['totals'] = array_map(fn($x) => array_map(fn($c) => Money::str($c), $x), $t); return $r;
    }
    public static function adjust(int $itemId, string $allowances, string $deductions, ?string $note): void
    {
        Auth::authorize('payroll.manage'); $i = DB::one('SELECT i.*, r.status, r.school_id FROM payroll_items i JOIN payroll_runs r ON r.id = i.run_id WHERE i.id = ? AND r.school_id = ?', [$itemId, Tenant::require()]) ?? throw new HttpException(404, 'البند غير موجود');
        if ($i['status'] !== 'draft') { throw new HttpException(422, 'الكشف معتمد ولا يُعدَّل. أعد فتحه أولًا.'); }
        try { $a = Money::cents($allowances === '' ? '0' : $allowances); $d = Money::cents($deductions === '' ? '0' : $deductions); } catch (\InvalidArgumentException) { throw new HttpException(422, 'مبلغ غير صالح'); }
        $net = Money::cents($i['base']) + $a - $d; if ($net < 0) { throw new HttpException(422, 'الخصومات تتجاوز الراتب والبدلات', ['deductions' => 'تتجاوز الراتب']); }
        DB::query('UPDATE payroll_items SET allowances = ?, deductions = ?, net = ?, note = ? WHERE id = ?', [Money::str($a), Money::str($d), Money::str($net), $note ? mb_substr($note, 0, 255) : null, $itemId]);
        AuditService::log('payroll.adjust', 'payroll_item', $itemId, null, ['staff_id' => $i['staff_id']]);
    }
    public static function approve(int $id): void
    {
        Auth::authorize('payroll.manage'); $r = self::run($id); if ($r['status'] !== 'draft') { throw new HttpException(422, 'الكشف ليس مسودة'); }
        DB::query("UPDATE payroll_runs SET status = 'approved', approved_by = ?, approved_at = ? WHERE id = ?", [Auth::id(), now(), $id]); AuditService::log('payroll.approve', 'payroll_run', $id, ['status' => 'draft'], ['status' => 'approved']);
    }
    public static function markPaid(int $id, string $paidOn): void
    {
        Auth::authorize('payroll.manage'); $r = self::run($id); if ($r['status'] !== 'approved') { throw new HttpException(422, 'اعتمد الكشف أولًا'); }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $paidOn) || $paidOn > date('Y-m-d')) { throw new HttpException(422, 'تاريخ الصرف غير صالح', ['paid_on' => 'غير صالح']); }
        DB::query("UPDATE payroll_runs SET status = 'paid', paid_on = ? WHERE id = ?", [$paidOn, $id]); AuditService::log('payroll.paid', 'payroll_run', $id, ['status' => 'approved'], ['status' => 'paid', 'paid_on' => $paidOn]);
    }
    public static function reopen(int $id, string $reason): void
    {
        Auth::authorize('payroll.manage'); $r = self::run($id); if ($r['status'] !== 'approved') { throw new HttpException(422, 'يمكن إعادة فتح الكشف المعتمد غير المصروف فقط'); }
        if (mb_strlen(trim($reason)) < 5) { throw new HttpException(422, 'اذكر السبب (5 أحرف على الأقل)', ['reason' => 'مطلوب']); }
        DB::query("UPDATE payroll_runs SET status = 'draft', approved_by = NULL, approved_at = NULL WHERE id = ?", [$id]); AuditService::log('payroll.reopen', 'payroll_run', $id, ['status' => 'approved'], ['reason' => $reason]);
    }
    public static function deleteRun(int $id): void
    {
        Auth::authorize('payroll.manage'); $r = self::run($id); if ($r['status'] !== 'draft') { throw new HttpException(422, 'يمكن حذف المسودة فقط'); }
        DB::query('DELETE FROM payroll_items WHERE run_id = ?', [$id]); DB::query('DELETE FROM payroll_runs WHERE id = ?', [$id]); AuditService::log('payroll.delete', 'payroll_run', $id, ['period' => $r['period']]);
    }
    public static function payslip(int $itemId): array
    {
        Auth::authorize('payroll.manage');
        $i = DB::one("SELECT i.*, r.period, r.status, r.paid_on, s.full_name, s.job_title, s.employee_no, sch.name AS school_name, sch.city FROM payroll_items i JOIN payroll_runs r ON r.id = i.run_id JOIN staff s ON s.id = i.staff_id JOIN schools sch ON sch.id = r.school_id WHERE i.id = ? AND r.school_id = ?", [$itemId, Tenant::require()]) ?? throw new HttpException(404, 'القسيمة غير موجودة');
        AuditService::log('payroll.payslip', 'payroll_item', $itemId); return $i;
    }
}
