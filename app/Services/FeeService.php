<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\{Auth, DB, HttpException, Tenant};

/** هياكل الرسوم والخصومات وتعيينها للطلاب وتوليد الأقساط. المبالغ داخليًا بالهللات/السنتات (Money). */
final class FeeService
{
    public const CURRENCIES = ['YER', 'USD', 'SAR'];

    // ---------- الضريبة ----------
    public static function tax(): array
    {
        return DB::one('SELECT * FROM tax_settings WHERE school_id = ?', [Tenant::require()]) ?? ['tax_enabled' => 0, 'rate_percent' => null, 'tax_label' => null, 'tax_number' => null, 'exempt_note' => null, 'prices_include_tax' => 0];
    }
    /** لا نسبة افتراضية (D-06): التفعيل يتطلب نسبة صريحة، والتعطيل = معفاة. */
    public static function saveTax(bool $enabled, ?string $rate, ?string $label, ?string $number, ?string $exempt, bool $include): void
    {
        Auth::authorize('fees.manage');
        if ($enabled && ($rate === null || $rate === '' || !is_numeric($rate) || (float)$rate < 0 || (float)$rate > 100)) { throw new HttpException(422, 'أدخل نسبة الضريبة (0–100) صراحة عند التفعيل', ['rate_percent' => 'مطلوبة']); }
        $old = self::tax();
        DB::query('INSERT INTO tax_settings (school_id, tax_enabled, rate_percent, tax_label, tax_number, exempt_note, prices_include_tax) VALUES (?,?,?,?,?,?,?)
            ON DUPLICATE KEY UPDATE tax_enabled = VALUES(tax_enabled), rate_percent = VALUES(rate_percent), tax_label = VALUES(tax_label), tax_number = VALUES(tax_number), exempt_note = VALUES(exempt_note), prices_include_tax = VALUES(prices_include_tax)',
            [Tenant::require(), (int)$enabled, $enabled ? round((float)$rate, 2) : null, $label ?: null, $number ?: null, $exempt ?: null, (int)$include]);
        AuditService::log('fees.tax', 'tax_settings', Tenant::require(), ['enabled' => (int)$old['tax_enabled'], 'rate' => $old['rate_percent']], ['enabled' => (int)$enabled, 'rate' => $enabled ? $rate : null, 'include' => (int)$include]);
    }

    // ---------- الخصومات ----------
    public static function discounts(): array { Auth::authorize('fees.view'); return DB::all('SELECT * FROM discounts WHERE school_id = ? ORDER BY name', [Tenant::require()]); }
    public static function saveDiscount(?int $id, string $name, string $kind, string $value, string $reason): int
    {
        Auth::authorize('fees.manage');
        if (!in_array($kind, ['percent', 'fixed'], true) || !in_array($reason, ['sibling', 'scholarship', 'staff_child', 'other'], true) || trim($name) === '') { throw new HttpException(422, 'بيانات الخصم غير صالحة'); }
        if (!is_numeric($value) || (float)$value <= 0 || ($kind === 'percent' && (float)$value > 100)) { throw new HttpException(422, 'قيمة الخصم غير صالحة', ['value' => 'غير صالحة']); }
        $sid = Tenant::require();
        if ($id) { DB::value('SELECT 1 FROM discounts WHERE id = ? AND school_id = ?', [$id, $sid]) ?? throw new HttpException(404, 'الخصم غير موجود'); DB::query('UPDATE discounts SET name=?, kind=?, value=?, reason=? WHERE id=? AND school_id=?', [trim($name), $kind, round((float)$value, 2), $reason, $id, $sid]); }
        else { DB::query('INSERT INTO discounts (school_id, name, kind, value, reason) VALUES (?,?,?,?,?)', [$sid, trim($name), $kind, round((float)$value, 2), $reason]); $id = DB::lastId(); }
        AuditService::log('fees.discount', 'discount', $id, null, ['name' => $name, 'kind' => $kind, 'value' => $value]);
        return $id;
    }
    public static function deleteDiscount(int $id): void
    {
        Auth::authorize('fees.manage');
        if (DB::value('SELECT 1 FROM student_fees WHERE discount_id = ? AND school_id = ? LIMIT 1', [$id, Tenant::require()])) { throw new HttpException(409, 'الخصم مستخدم في رسوم طلاب'); }
        DB::query('DELETE FROM discounts WHERE id = ? AND school_id = ?', [$id, Tenant::require()]);
    }

    // ---------- هياكل الرسوم ----------
    public static function structures(?int $yearId = null): array
    {
        Auth::authorize('fees.view'); $sid = Tenant::require();
        $w = 'fs.school_id = ?'; $p = [$sid]; if ($yearId) { $w .= ' AND fs.academic_year_id = ?'; $p[] = $yearId; }
        $rows = DB::all("SELECT fs.*, y.name AS year_name, g.name AS grade_name, (SELECT COUNT(*) FROM student_fees sf WHERE sf.structure_id = fs.id AND sf.status = 'active') AS assigned FROM fee_structures fs
            JOIN academic_years y ON y.id = fs.academic_year_id LEFT JOIN grade_levels g ON g.id = fs.grade_level_id WHERE $w ORDER BY y.starts_on DESC, fs.name", $p);
        foreach ($rows as &$r) { $r['items'] = DB::all('SELECT * FROM fee_items WHERE structure_id = ? ORDER BY id', [$r['id']]); $r['total'] = Money::str(array_sum(array_map(fn($i) => Money::cents($i['amount']), $r['items']))); }
        return $rows;
    }
    public static function structure(int $id): array
    {
        Auth::authorize('fees.view');
        $s = DB::one('SELECT * FROM fee_structures WHERE id = ? AND school_id = ?', [$id, Tenant::require()]) ?? throw new HttpException(404, 'هيكل الرسوم غير موجود');
        $s['items'] = DB::all('SELECT * FROM fee_items WHERE structure_id = ? ORDER BY id', [$id]); return $s;
    }

    /** d: academic_year_id, grade_level_id?, name, currency, installments_count, first_due_date?, interval_months, items[{name,amount}] */
    public static function saveStructure(?int $id, array $d): int
    {
        Auth::authorize('fees.manage'); $sid = Tenant::require();
        if (trim((string)($d['name'] ?? '')) === '') { throw new HttpException(422, 'اسم الهيكل مطلوب', ['name' => 'مطلوب']); }
        if (!in_array($d['currency'] ?? '', self::CURRENCIES, true)) { throw new HttpException(422, 'عملة غير صالحة', ['currency' => 'غير صالحة']); }
        $n = (int)($d['installments_count'] ?? 1); if ($n < 1 || $n > 12) { throw new HttpException(422, 'عدد الأقساط من 1 إلى 12', ['installments_count' => 'غير صالح']); }
        $step = (int)($d['interval_months'] ?? 1); if ($step < 1 || $step > 12) { throw new HttpException(422, 'الفاصل بين الأقساط من 1 إلى 12 شهرًا'); }
        $due = ($d['first_due_date'] ?? '') ?: null; if ($due && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $due)) { throw new HttpException(422, 'تاريخ أول قسط غير صالح', ['first_due_date' => 'غير صالح']); }
        $year = DB::one('SELECT id, status FROM academic_years WHERE id = ? AND school_id = ?', [(int)($d['academic_year_id'] ?? 0), $sid]) ?? throw new HttpException(422, 'السنة غير صالحة', ['academic_year_id' => 'غير صالحة']);
        if ($year['status'] === 'closed') { throw new HttpException(422, 'السنة مغلقة'); }
        $grade = ($d['grade_level_id'] ?? '') === '' ? null : (int)$d['grade_level_id'];
        if ($grade) { DB::value('SELECT 1 FROM grade_levels WHERE id = ? AND school_id = ?', [$grade, $sid]) ?? throw new HttpException(422, 'صف غير صالح'); }
        $items = [];
        foreach ((array)($d['items'] ?? []) as $it) {
            if (trim((string)($it['name'] ?? '')) === '' && ($it['amount'] ?? '') === '') { continue; }
            try { $c = Money::cents($it['amount'] ?? ''); } catch (\InvalidArgumentException) { throw new HttpException(422, 'مبلغ بند غير صالح', ['items' => 'مبلغ غير صالح']); }
            if ($c <= 0 || trim((string)$it['name']) === '') { throw new HttpException(422, 'كل بند يحتاج اسمًا ومبلغًا أكبر من صفر', ['items' => 'بند ناقص']); }
            $items[] = ['name' => trim((string)$it['name']), 'amount' => Money::str($c)];
        }
        if (!$items) { throw new HttpException(422, 'أضف بندًا واحدًا على الأقل', ['items' => 'مطلوب']); }
        return DB::transaction(function () use ($id, $d, $sid, $grade, $n, $step, $due, $items) {
            if ($id) {
                $old = DB::one('SELECT * FROM fee_structures WHERE id = ? AND school_id = ? FOR UPDATE', [$id, $sid]) ?? throw new HttpException(404, 'الهيكل غير موجود');
                if (DB::value("SELECT 1 FROM student_fees WHERE structure_id = ? AND status = 'active' LIMIT 1", [$id])) { throw new HttpException(409, 'الهيكل معيَّن لطلاب؛ لا يمكن تعديله. أنشئ هيكلًا جديدًا.'); }
                DB::query('UPDATE fee_structures SET academic_year_id=?, grade_level_id=?, name=?, currency=?, installments_count=?, first_due_date=?, interval_months=? WHERE id=?', [(int)$d['academic_year_id'], $grade, trim($d['name']), $d['currency'], $n, $due, $step, $id]);
                DB::query('DELETE FROM fee_items WHERE structure_id = ?', [$id]);
            } else {
                DB::query('INSERT INTO fee_structures (school_id, academic_year_id, grade_level_id, name, currency, installments_count, first_due_date, interval_months) VALUES (?,?,?,?,?,?,?,?)', [$sid, (int)$d['academic_year_id'], $grade, trim($d['name']), $d['currency'], $n, $due, $step]);
                $id = DB::lastId();
            }
            foreach ($items as $it) { DB::query('INSERT INTO fee_items (school_id, structure_id, name, amount, installments_count) VALUES (?,?,?,?,1)', [$sid, $id, $it['name'], $it['amount']]); }
            AuditService::log('fees.structure', 'fee_structure', $id, null, ['name' => $d['name'], 'currency' => $d['currency'], 'items' => count($items)]);
            return $id;
        });
    }
    public static function deleteStructure(int $id): void
    {
        Auth::authorize('fees.manage'); $s = self::structure($id);
        if (DB::value('SELECT 1 FROM student_fees WHERE structure_id = ? LIMIT 1', [$id])) { throw new HttpException(409, 'الهيكل معيَّن لطلاب'); }
        DB::query('DELETE FROM fee_items WHERE structure_id = ?', [$id]); DB::query('DELETE FROM fee_structures WHERE id = ? AND school_id = ?', [$id, Tenant::require()]);
        AuditService::log('fees.structure_delete', 'fee_structure', $id, ['name' => $s['name']]);
    }

    // ---------- التعيين على الطلاب ----------
    /** يحسب الخصم بالهللات بالعملة نفسها للهيكل. الخصم الثابت بعملة الهيكل. */
    private static function discountCents(int $gross, ?array $disc, ?string $custom): int
    {
        if ($custom !== null && $custom !== '') { $c = Money::cents($custom); return min($c, $gross); }
        if (!$disc) { return 0; }
        return min($disc['kind'] === 'percent' ? Money::percent($gross, $disc['value']) : Money::cents($disc['value']), $gross);
    }

    /** الضريبة وفق الإعدادات الحالية (تُجمَّد في سجل الطالب). يعيد [tax, payable, percent|null]. */
    public static function applyTax(int $net): array
    {
        $t = self::tax();
        if (!(int)$t['tax_enabled']) { return [0, $net, null]; }
        if ($t['rate_percent'] === null) { throw new HttpException(422, 'الضريبة مفعّلة بلا نسبة. اضبطها من إعدادات الرسوم.'); }
        $rate = (string)$t['rate_percent'];
        if ((int)$t['prices_include_tax']) { $tax = $net - intdiv($net * 10000 * 2 + (10000 + (int)round((float)$rate * 100)), (10000 + (int)round((float)$rate * 100)) * 2); return [$tax, $net, $rate]; }
        $tax = Money::percent($net, $rate); return [$tax, $net + $tax, $rate];
    }

    /** يعيّن هيكلًا على تسجيل طالب ويولّد أقساطه. $discountId من الكتالوج، أو $customDiscount (مبلغ) + $reason. */
    public static function assign(int $structureId, int $enrollmentId, ?int $discountId = null, ?string $customDiscount = null, ?string $reason = null): int
    {
        Auth::authorize('fees.manage'); $sid = Tenant::require();
        return DB::transaction(function () use ($structureId, $enrollmentId, $discountId, $customDiscount, $reason, $sid) {
            $fs = DB::one('SELECT * FROM fee_structures WHERE id = ? AND school_id = ?', [$structureId, $sid]) ?? throw new HttpException(404, 'هيكل الرسوم غير موجود');
            $e = DB::one("SELECT e.*, sc.grade_level_id, s.full_name FROM student_enrollments e JOIN sections sc ON sc.id = e.section_id JOIN students s ON s.id = e.student_id WHERE e.id = ? AND e.school_id = ? AND s.deleted_at IS NULL", [$enrollmentId, $sid]) ?? throw new HttpException(404, 'التسجيل غير موجود');
            if ($e['status'] !== 'active') { throw new HttpException(422, 'الطالب غير فعّال في هذه السنة'); }
            if ((int)$e['academic_year_id'] !== (int)$fs['academic_year_id']) { throw new HttpException(422, 'الهيكل لسنة أخرى'); }
            if ($fs['grade_level_id'] && (int)$fs['grade_level_id'] !== (int)$e['grade_level_id']) { throw new HttpException(422, 'الهيكل مخصص لصف آخر'); }
            if (DB::value('SELECT 1 FROM student_fees WHERE enrollment_id = ? AND structure_id = ?', [$enrollmentId, $structureId])) { throw new HttpException(409, 'الهيكل معيَّن لهذا الطالب مسبقًا'); }
            $gross = 0; foreach (DB::all('SELECT amount FROM fee_items WHERE structure_id = ?', [$structureId]) as $i) { $gross += Money::cents($i['amount']); }
            if ($gross <= 0) { throw new HttpException(422, 'الهيكل بلا بنود'); }
            $disc = $discountId ? (DB::one('SELECT * FROM discounts WHERE id = ? AND school_id = ?', [$discountId, $sid]) ?? throw new HttpException(422, 'خصم غير صالح')) : null;
            if ($customDiscount !== null && $customDiscount !== '' && trim((string)$reason) === '') { throw new HttpException(422, 'اذكر سبب الخصم الاستثنائي', ['reason' => 'مطلوب']); }
            try { $dc = self::discountCents($gross, $disc, $customDiscount); } catch (\InvalidArgumentException) { throw new HttpException(422, 'مبلغ الخصم غير صالح'); }
            $net = $gross - $dc; [$tax, $payable, $pct] = self::applyTax($net);
            DB::query('INSERT INTO student_fees (school_id, enrollment_id, structure_id, currency, gross, discount_total, net, discount_id, tax_percent, tax_amount, payable, discount_reason) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',
                [$sid, $enrollmentId, $structureId, $fs['currency'], Money::str($gross), Money::str($dc), Money::str($net), $discountId, $pct, Money::str($tax), Money::str($payable), $disc ? $disc['name'] : (($reason ?? '') ?: null)]);
            $id = DB::lastId();
            $due = $fs['first_due_date'] ?: date('Y-m-d');
            foreach (Money::split($payable, (int)$fs['installments_count']) as $k => $amt) {
                DB::query('INSERT INTO installments (school_id, student_fee_id, seq, due_date, amount) VALUES (?,?,?,?,?)', [$sid, $id, $k + 1, Money::addMonths($due, $k * (int)$fs['interval_months']), Money::str($amt)]);
            }
            AuditService::log('fees.assign', 'student_fee', $id, null, ['student' => $e['full_name'], 'structure' => $fs['name'], 'payable' => Money::str($payable), 'discount' => Money::str($dc)]);
            return $id;
        });
    }

    /** رتبة الطالب بين إخوته (نفس ولي الأمر المسؤول/الأساسي) في السنة: 1 = الأكبر رقمًا أولًا. */
    public static function siblingRank(int $studentId, int $yearId): int
    {
        $sid = Tenant::require();
        $g = array_column(DB::all('SELECT guardian_id FROM student_guardians WHERE student_id = ? AND school_id = ? AND (financially_responsible = 1 OR is_primary = 1)', [$studentId, $sid]), 'guardian_id');
        if (!$g) { return 1; }
        $in = implode(',', array_map('intval', $g));
        $earlier = (int)DB::value("SELECT COUNT(DISTINCT sg.student_id) FROM student_guardians sg JOIN student_enrollments e ON e.student_id = sg.student_id AND e.academic_year_id = ? AND e.status = 'active'
            WHERE sg.school_id = ? AND sg.guardian_id IN ($in) AND (sg.financially_responsible = 1 OR sg.is_primary = 1) AND sg.student_id < ?", [$yearId, $sid, $studentId]);
        return $earlier + 1;
    }

    /** تعيين جماعي لشعبة أو صف. يطبّق خصم الإخوة (أول خصم بسبب sibling في الكتالوج) على من رتبته ≥ 2. */
    public static function bulkAssign(int $structureId, ?int $sectionId, ?int $gradeId, bool $applySibling = true): array
    {
        Auth::authorize('fees.manage'); $sid = Tenant::require();
        $fs = DB::one('SELECT * FROM fee_structures WHERE id = ? AND school_id = ?', [$structureId, $sid]) ?? throw new HttpException(404, 'هيكل الرسوم غير موجود');
        $w = "e.school_id = ? AND e.academic_year_id = ? AND e.status = 'active' AND s.deleted_at IS NULL"; $p = [$sid, $fs['academic_year_id']];
        if ($sectionId) { $w .= ' AND e.section_id = ?'; $p[] = $sectionId; }
        $g = $gradeId ?: ($fs['grade_level_id'] ? (int)$fs['grade_level_id'] : null); if ($g) { $w .= ' AND sc.grade_level_id = ?'; $p[] = $g; }
        if (!$sectionId && !$g) { throw new HttpException(422, 'حدّد شعبة أو صفًّا للتعيين الجماعي'); }
        $rows = DB::all("SELECT e.id, e.student_id, s.full_name FROM student_enrollments e JOIN students s ON s.id = e.student_id JOIN sections sc ON sc.id = e.section_id WHERE $w ORDER BY s.full_name", $p);
        $sib = $applySibling ? (int)DB::value("SELECT id FROM discounts WHERE school_id = ? AND reason = 'sibling' ORDER BY id LIMIT 1", [$sid]) : 0;
        $res = ['assigned' => 0, 'skipped' => 0, 'siblings' => 0, 'errors' => []];
        foreach ($rows as $r) {
            if (DB::value('SELECT 1 FROM student_fees WHERE enrollment_id = ? AND structure_id = ?', [$r['id'], $structureId])) { $res['skipped']++; continue; }
            $useSib = $sib && self::siblingRank((int)$r['student_id'], (int)$fs['academic_year_id']) >= 2;
            try { self::assign($structureId, (int)$r['id'], $useSib ? $sib : null); $res['assigned']++; $res['siblings'] += $useSib ? 1 : 0; }
            catch (HttpException $e) { $res['errors'][] = $r['full_name'] . ': ' . $e->getMessage(); }
        }
        return $res;
    }

    public static function cancel(int $studentFeeId, string $reason): void
    {
        Auth::authorize('fees.manage');
        if (mb_strlen(trim($reason)) < 5) { throw new HttpException(422, 'اذكر سبب الإلغاء (5 أحرف على الأقل)', ['reason' => 'مطلوب']); }
        $sid = Tenant::require();
        DB::transaction(function () use ($studentFeeId, $reason, $sid) {
            $f = DB::one('SELECT * FROM student_fees WHERE id = ? AND school_id = ? FOR UPDATE', [$studentFeeId, $sid]) ?? throw new HttpException(404, 'غير موجود');
            if ($f['status'] === 'cancelled') { return; }
            if (DB::value('SELECT 1 FROM payments WHERE student_fee_id = ? AND school_id = ? AND voided_at IS NULL LIMIT 1', [$studentFeeId, $sid])) { throw new HttpException(409, 'توجد دفعات على هذه الرسوم؛ ألغِ السندات أولًا'); }
            DB::query("UPDATE student_fees SET status = 'cancelled', cancel_reason = ? WHERE id = ?", [mb_substr($reason, 0, 255), $studentFeeId]);
            DB::query("UPDATE invoices SET status = 'cancelled', cancelled_reason = ? WHERE student_fee_id = ? AND school_id = ? AND status <> 'cancelled'", ['إلغاء الرسوم: ' . mb_substr($reason, 0, 200), $studentFeeId, $sid]);
            AuditService::log('fees.cancel', 'student_fee', $studentFeeId, ['payable' => $f['payable']], ['reason' => $reason]);
        });
    }

    // ---------- القراءة ----------
    private const BAL = "(SELECT COALESCE(SUM(p.applied_amount),0) FROM payments p WHERE p.student_fee_id = sf.id AND p.voided_at IS NULL)";

    /** قائمة رسوم الطلاب مع المدفوع والمتبقي والمتأخر. status: paid|partial|unpaid|overdue */
    public static function search(int $page, int $per, string $q, ?int $sectionId, ?int $structureId, string $status): array
    {
        Auth::authorize('fees.view'); $sid = Tenant::require(); $per = min(100, max(1, $per)); $page = max(1, $page);
        $w = "sf.school_id = ? AND sf.status = 'active' AND s.deleted_at IS NULL"; $p = [$sid];
        if ($q !== '') { $l = '%' . addcslashes($q, '%_\\') . '%'; $w .= ' AND (s.full_name LIKE ? OR s.student_no LIKE ?)'; array_push($p, $l, $l); }
        if ($sectionId) { $w .= ' AND e.section_id = ?'; $p[] = $sectionId; }
        if ($structureId) { $w .= ' AND sf.structure_id = ?'; $p[] = $structureId; }
        $having = ['paid' => 'paid >= payable', 'partial' => 'paid > 0 AND paid < payable', 'unpaid' => 'paid = 0', 'overdue' => 'overdue > 0'][$status] ?? '1=1';
        $base = "SELECT sf.id, sf.currency, sf.payable, s.id AS student_id, s.full_name, s.student_no, sc.name AS section_name, g.name AS grade_name, fs.name AS structure_name, " . self::BAL . " AS paid,
            (SELECT COALESCE(SUM(i.amount - i.paid_amount),0) FROM installments i WHERE i.student_fee_id = sf.id AND i.due_date < CURDATE() AND i.status <> 'paid') AS overdue
            FROM student_fees sf JOIN student_enrollments e ON e.id = sf.enrollment_id JOIN students s ON s.id = e.student_id JOIN sections sc ON sc.id = e.section_id JOIN grade_levels g ON g.id = sc.grade_level_id JOIN fee_structures fs ON fs.id = sf.structure_id WHERE $w";
        $total = (int)DB::value("SELECT COUNT(*) FROM ($base) t WHERE $having", $p);
        $rows = DB::all("SELECT * FROM ($base) t WHERE $having ORDER BY full_name LIMIT $per OFFSET " . (($page - 1) * $per), $p);
        foreach ($rows as &$r) { $r['balance'] = Money::str(Money::cents($r['payable']) - Money::cents($r['paid'])); }
        return ['rows' => $rows, 'meta' => ['page' => $page, 'per_page' => $per, 'total' => $total, 'pages' => (int)ceil($total / $per)]];
    }

    /** تفاصيل رسوم طالب: الأقساط والدفعات والفاتورة والمتبقي. */
    public static function detail(int $id): array
    {
        Auth::authorize('fees.view'); $sid = Tenant::require();
        $f = DB::one("SELECT sf.*, s.id AS student_id, s.full_name, s.student_no, fs.name AS structure_name, sc.name AS section_name, g.name AS grade_name, y.name AS year_name
            FROM student_fees sf JOIN student_enrollments e ON e.id = sf.enrollment_id JOIN students s ON s.id = e.student_id JOIN sections sc ON sc.id = e.section_id JOIN grade_levels g ON g.id = sc.grade_level_id
            JOIN fee_structures fs ON fs.id = sf.structure_id JOIN academic_years y ON y.id = fs.academic_year_id WHERE sf.id = ? AND sf.school_id = ?", [$id, $sid]) ?? throw new HttpException(404, 'رسوم الطالب غير موجودة');
        $f['items'] = DB::all('SELECT name, amount FROM fee_items WHERE structure_id = ? ORDER BY id', [$f['structure_id']]);
        $f['installments'] = DB::all('SELECT * FROM installments WHERE student_fee_id = ? AND school_id = ? ORDER BY seq', [$id, $sid]);
        $f['payments'] = DB::all('SELECT p.*, r.number AS receipt_no, r.id AS receipt_id, m.name AS method_name, u.full_name AS received_by_name FROM payments p LEFT JOIN receipts r ON r.payment_id = p.id LEFT JOIN payment_methods m ON m.id = p.method_id LEFT JOIN users u ON u.id = p.received_by
            WHERE p.student_fee_id = ? AND p.school_id = ? ORDER BY p.id DESC', [$id, $sid]);
        $f['invoice'] = DB::one("SELECT * FROM invoices WHERE student_fee_id = ? AND school_id = ? AND status <> 'cancelled' ORDER BY id DESC LIMIT 1", [$id, $sid]);
        $paid = 0; foreach ($f['payments'] as $p) { if (!$p['voided_at']) { $paid += Money::cents($p['applied_amount']); } }
        $f['paid'] = Money::str($paid); $f['balance'] = Money::str(Money::cents($f['payable']) - $paid);
        $f['guardians'] = DB::all('SELECT g.full_name, g.phone, sg.financially_responsible, sg.is_primary FROM student_guardians sg JOIN guardians g ON g.id = sg.guardian_id WHERE sg.student_id = ? AND sg.school_id = ? ORDER BY sg.financially_responsible DESC, sg.is_primary DESC', [$f['student_id'], $sid]);
        return $f;
    }
}
