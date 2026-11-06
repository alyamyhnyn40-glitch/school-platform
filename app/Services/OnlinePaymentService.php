<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\{Auth, DB, Env, HttpException, Tenant};

/**
 * الدفع الإلكتروني لرسوم الطلاب عبر بوابة دفع خارجية (واجهة HTTP عامة) مع إشعار (webhook) موقَّع.
 * المبادئ: لا يُقيَّد أي مبلغ إلا بعد التحقق من الإشعار (توقيع HMAC و/أو استعلام حالة من المزود)، والمبلغ المتوقَّع يُحفظ عندنا
 * ولا يُؤخذ من المتصفح، وكل عملية idempotent (قفل الصف)، والأخطاء تُحوَّل إلى حالة «مراجعة» ولا تضيع الأموال.
 * الإعدادات لكل مدرسة (مفاتيح pay.* في settings) والأسرار مشفّرة.
 */
final class OnlinePaymentService
{
    public const DRIVERS = ['off' => 'معطّل', 'sandbox' => 'تجريبي (بدون مزود فعلي)', 'http' => 'مزود عبر HTTP'];
    public const STATUSES = ['pending' => 'بانتظار الدفع', 'paid' => 'مدفوع', 'failed' => 'فشل', 'cancelled' => 'أُلغي', 'expired' => 'منتهٍ', 'review' => 'يحتاج مراجعة'];
    private const SECRETS = ['create_headers', 'sig_secret', 'st_headers'];

    // ---------------- الإعدادات ----------------
    private static function get(string $k, ?string $d = null): ?string
    {
        $v = SettingsService::get('pay.' . $k, null); if ($v === null || $v === '') { return $d; }
        return in_array($k, self::SECRETS, true) ? (Crypto::decrypt($v) ?? $d) : $v;
    }
    private static function put(string $k, ?string $v): void
    {
        SettingsService::set('pay.' . $k, ($v === null || $v === '') ? null : (in_array($k, self::SECRETS, true) ? Crypto::encrypt($v) : $v));
    }
    public static function driver(): string { return (string)self::get('driver', 'off'); }
    /** هل الدفع الإلكتروني متاح للأولياء؟ (الوضع التجريبي ممنوع في الإنتاج). */
    public static function enabled(): bool { $d = self::driver(); return $d === 'http' || ($d === 'sandbox' && !Env::isProduction()); }
    public static function webhookUrl(): string
    {
        $t = self::get('callback_token'); $base = rtrim((string)Env::get('APP_URL', ''), '/');
        return $t ? $base . url('pay/callback/' . $t) : '';
    }
    public static function config(): array
    {
        Auth::authorize('fees.gateway'); $o = ['driver' => self::driver(), 'webhook_url' => self::webhookUrl(), 'production' => Env::isProduction()];
        foreach (['create_url', 'create_method', 'create_body', 'checkout_url_path', 'provider_ref_path', 'cb_reference_path', 'cb_status_path', 'cb_success_values', 'cb_failed_values', 'cb_amount_path',
                  'sig_header', 'sig_encoding', 'st_url', 'st_method', 'st_body', 'st_status_path', 'st_success_values', 'st_amount_path', 'expire_minutes'] as $k) { $o[$k] = (string)self::get($k, ''); }
        $o['create_method'] = $o['create_method'] ?: 'POST'; $o['st_method'] = $o['st_method'] ?: 'GET'; $o['sig_encoding'] = $o['sig_encoding'] ?: 'hex'; $o['sig_header'] = $o['sig_header'] ?: 'X-Signature'; $o['expire_minutes'] = $o['expire_minutes'] ?: '60';
        $o['has_create_headers'] = self::get('create_headers') !== null; $o['has_sig_secret'] = self::get('sig_secret') !== null; $o['has_st_headers'] = self::get('st_headers') !== null;
        return $o;
    }
    public static function saveConfig(array $d): void
    {
        Auth::authorize('fees.gateway');
        $driver = (string)($d['driver'] ?? 'off'); if (!isset(self::DRIVERS[$driver])) { throw new HttpException(422, 'نوع غير صالح', ['driver' => 'غير صالح']); }
        if ($driver === 'sandbox' && Env::isProduction()) { throw new HttpException(422, 'الوضع التجريبي غير متاح في بيئة الإنتاج (APP_ENV=production)', ['driver' => 'غير متاح']); }
        $txt = fn(string $k) => trim((string)($d[$k] ?? ''));
        if ($driver === 'http') {
            $url = $txt('create_url'); UrlGuard::check($url);
            $body = $txt('create_body'); if (!str_contains($body, '{reference}') || !(str_contains($body, '{amount}') || str_contains($body, '{amount_minor}'))) { throw new HttpException(422, 'قالب الطلب يجب أن يحوي {reference} و{amount} (أو {amount_minor})', ['create_body' => 'ناقص']); }
            foreach (['checkout_url_path' => 'مسار رابط الدفع في رد المزود', 'cb_reference_path' => 'مسار المرجع في الإشعار', 'cb_status_path' => 'مسار الحالة في الإشعار', 'cb_success_values' => 'قيم النجاح'] as $k => $l) { if ($txt($k) === '') { throw new HttpException(422, "$l مطلوب", [$k => 'مطلوب']); } }
            foreach (['create_headers', 'st_headers'] as $hk) { $h = $txt($hk); if ($h !== '' && !is_array(json_decode($h, true))) { throw new HttpException(422, 'الترويسات يجب أن تكون JSON صالحًا', [$hk => 'JSON غير صالح']); } }
            $hasSecret = $txt('sig_secret') !== '' || self::get('sig_secret') !== null; $stUrl = $txt('st_url');
            if (!$hasSecret && $stUrl === '') { throw new HttpException(422, 'لأمان الأموال يلزم سر توقيع للإشعار أو رابط استعلام حالة من المزود (أو كلاهما)', ['sig_secret' => 'مطلوب']); }
            if ($stUrl !== '') { UrlGuard::check(str_replace(['{reference}', '{provider_ref}'], ['x', 'x'], $stUrl)); if ($txt('st_status_path') === '' || $txt('st_success_values') === '') { throw new HttpException(422, 'مسار الحالة وقيم النجاح مطلوبان لاستعلام الحالة', ['st_status_path' => 'مطلوب']); } }
            $m = strtoupper($txt('create_method') ?: 'POST'); if (!in_array($m, ['POST', 'GET'], true)) { throw new HttpException(422, 'الطريقة POST أو GET'); }
            $sm = strtoupper($txt('st_method') ?: 'GET'); if (!in_array($sm, ['POST', 'GET'], true)) { throw new HttpException(422, 'الطريقة POST أو GET'); }
            $enc = $txt('sig_encoding') ?: 'hex'; if (!in_array($enc, ['hex', 'base64'], true)) { throw new HttpException(422, 'ترميز التوقيع hex أو base64'); }
            self::put('create_url', $url); self::put('create_method', $m); self::put('create_body', $body);
            foreach (['checkout_url_path', 'provider_ref_path', 'cb_reference_path', 'cb_status_path', 'cb_success_values', 'cb_failed_values', 'cb_amount_path', 'sig_header', 'st_url', 'st_body', 'st_status_path', 'st_success_values', 'st_amount_path'] as $k) { self::put($k, $txt($k)); }
            self::put('sig_encoding', $enc); self::put('st_method', $sm);
            foreach (['create_headers', 'sig_secret', 'st_headers'] as $sk) { if ($txt($sk) !== '') { self::put($sk, $txt($sk)); } }
            foreach (['create_headers', 'sig_secret', 'st_headers'] as $sk) { if (!empty($d['clear_' . $sk])) { self::put($sk, null); } }
        }
        $exp = (int)($d['expire_minutes'] ?? 60); self::put('expire_minutes', (string)max(10, min(1440, $exp ?: 60)));
        if (self::get('callback_token') === null || !empty($d['regenerate_token'])) { self::put('callback_token', bin2hex(random_bytes(20))); }
        self::put('driver', $driver);
        AuditService::log('payments.online_config', 'school', Tenant::require(), null, ['driver' => $driver, 'host' => $driver === 'http' ? parse_url($txt('create_url'), PHP_URL_HOST) : null, 'token_rotated' => !empty($d['regenerate_token'])]);
    }

    // ---------------- أدوات ----------------
    public static function pathGet(mixed $data, string $path): mixed
    {
        foreach (array_filter(explode('.', trim($path)), fn($x) => $x !== '') as $k) { if (!is_array($data) || !array_key_exists($k, $data)) { return null; } $data = $data[$k]; }
        return $data;
    }
    private static function fill(string $tpl, array $vars, bool $json): string
    {
        $enc = fn(string $s) => $json ? substr((string)json_encode($s, JSON_UNESCAPED_UNICODE), 1, -1) : rawurlencode($s);
        $map = []; foreach ($vars as $k => $v) { $map['{' . $k . '}'] = $enc((string)$v); }
        return strtr($tpl, $map);
    }
    /** @return array{code:int, body:string, error:?string} */
    private static function http(string $method, string $url, array $headers, ?string $body, bool $json): array
    {
        $g = UrlGuard::check($url); $h = [];
        foreach ($headers as $k => $v) { $h[] = "$k: $v"; }
        if ($json && $method === 'POST') { $h[] = 'Content-Type: application/json'; } elseif ($method === 'POST') { $h[] = 'Content-Type: application/x-www-form-urlencoded'; }
        $ch = curl_init($method === 'GET' && $body ? $url . (str_contains($url, '?') ? '&' : '?') . $body : $url); $buf = '';
        curl_setopt_array($ch, [CURLOPT_TIMEOUT => 20, CURLOPT_CONNECTTIMEOUT => 8, CURLOPT_HTTPHEADER => $h, CURLOPT_FOLLOWLOCATION => false, CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_RESOLVE => [$g['host'] . ':' . $g['port'] . ':' . $g['ip']],   // تثبيت العنوان الذي فُحص
            CURLOPT_WRITEFUNCTION => function ($c, $chunk) use (&$buf) { $buf .= $chunk; return strlen($buf) > 1048576 ? 0 : strlen($chunk); }]);
        if ($method === 'POST') { curl_setopt($ch, CURLOPT_POST, true); curl_setopt($ch, CURLOPT_POSTFIELDS, (string)$body); }
        curl_exec($ch); $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); $err = curl_error($ch); curl_close($ch);
        return ['code' => $code, 'body' => $buf, 'error' => $err !== '' ? $err : null];
    }
    private static function event(?int $opId, string $kind, bool $ok, string $detail, ?string $ip = null): void
    {
        DB::query('INSERT INTO online_payment_events (school_id, online_payment_id, kind, ok, detail, ip) VALUES (?,?,?,?,?,?)', [Tenant::require(), $opId, $kind, (int)$ok, mb_substr($detail, 0, 255), $ip]);
    }
    private static function absolute(string $path): string
    {
        $base = rtrim((string)Env::get('APP_URL', ''), '/'); if ($base === '') { throw new HttpException(500, 'APP_URL غير مضبوط في .env'); }
        return $base . $path;
    }

    // ---------------- بدء الدفع (ولي الأمر) ----------------
    /** يحسب المبلغ المقترح: المتأخر والمستحق حتى اليوم، وإلا القسط التالي، مع الحد الأقصى = المتبقي. */
    public static function suggest(int $studentFeeId): array
    {
        $sid = Tenant::require();
        $f = DB::one("SELECT sf.*, e.student_id FROM student_fees sf JOIN student_enrollments e ON e.id = sf.enrollment_id WHERE sf.id = ? AND sf.school_id = ? AND sf.status = 'active'", [$studentFeeId, $sid]) ?? throw new HttpException(404, 'الرسوم غير موجودة');
        $paid = Money::cents(DB::value('SELECT COALESCE(SUM(applied_amount),0) FROM payments WHERE student_fee_id = ? AND school_id = ? AND voided_at IS NULL', [$studentFeeId, $sid]));
        $balance = Money::cents($f['payable']) - $paid; $due = 0; $next = 0;
        foreach (DB::all("SELECT amount, paid_amount, due_date FROM installments WHERE student_fee_id = ? AND status <> 'paid' ORDER BY due_date, seq", [$studentFeeId]) as $i) {
            $rem = Money::cents($i['amount']) - Money::cents($i['paid_amount']); if ($rem <= 0) { continue; }
            if ($i['due_date'] <= date('Y-m-d')) { $due += $rem; } elseif ($next === 0) { $next = $rem; }
        }
        return ['student_id' => (int)$f['student_id'], 'currency' => $f['currency'], 'balance' => $balance, 'suggested' => min($balance, $due > 0 ? $due : $next), 'overdue' => $due];
    }
    public static function start(int $studentFeeId, ?string $amount = null): array
    {
        Auth::authorize('portal.view'); $sid = Tenant::require();
        if (Auth::type() !== 'guardian') { throw new HttpException(403, 'الدفع الإلكتروني لولي الأمر'); }
        if (!self::enabled() || !CheckoutService::gatewayVisible()) { throw new HttpException(422, 'الدفع الإلكتروني غير متاح حاليًا'); }
        $s = self::suggest($studentFeeId); PortalService::assertStudent($s['student_id']);
        if ($s['balance'] <= 0) { throw new HttpException(422, 'لا يوجد مبلغ مستحق على هذه الرسوم'); }
        try { $cents = ($amount === null || trim($amount) === '') ? $s['suggested'] : Money::cents($amount); } catch (\InvalidArgumentException) { throw new HttpException(422, 'مبلغ غير صالح', ['amount' => 'غير صالح']); }
        if ($cents <= 0) { throw new HttpException(422, 'المبلغ يجب أن يكون أكبر من صفر', ['amount' => 'غير صالح']); }
        if ($cents > $s['balance']) { throw new HttpException(422, 'المبلغ يتجاوز المتبقي (' . Money::fmt($s['balance'], $s['currency']) . ')', ['amount' => 'يتجاوز المتبقي']); }
        if ((int)DB::value("SELECT COUNT(*) FROM online_payments WHERE school_id = ? AND guardian_user_id = ? AND created_at > NOW() - INTERVAL 1 HOUR", [$sid, Auth::id()]) >= 10) { throw new HttpException(429, 'محاولات دفع كثيرة، انتظر قليلًا'); }
        $dup = DB::one("SELECT reference, checkout_url FROM online_payments WHERE school_id = ? AND student_fee_id = ? AND guardian_user_id = ? AND amount = ? AND status = 'pending' AND checkout_url IS NOT NULL AND created_at > NOW() - INTERVAL 20 MINUTE ORDER BY id DESC LIMIT 1", [$sid, $studentFeeId, Auth::id(), Money::str($cents)]);
        if ($dup) { return ['reference' => $dup['reference'], 'checkout_url' => $dup['checkout_url']]; }
        $ref = bin2hex(random_bytes(12)); $driver = self::driver();
        DB::query("INSERT INTO online_payments (school_id, reference, student_fee_id, guardian_user_id, amount, currency, status, driver) VALUES (?,?,?,?,?,?, 'pending', ?)", [$sid, $ref, $studentFeeId, Auth::id(), Money::str($cents), $s['currency'], $driver]);
        $id = DB::lastId();
        try {
            if ($driver === 'sandbox') { $url = url('pay/sandbox/' . $ref); $prov = null; }
            else { [$url, $prov] = self::createCheckout($ref, $cents, $s['currency']); }
        } catch (\Throwable $e) {
            DB::query("UPDATE online_payments SET status = 'failed', failure_reason = ?, updated_at = ? WHERE id = ?", [mb_substr($e->getMessage(), 0, 250), now(), $id]); self::event($id, 'create', false, $e->getMessage(), null);
            throw $e instanceof HttpException ? new HttpException(502, 'تعذّر إنشاء عملية الدفع لدى المزود: ' . $e->getMessage()) : $e;
        }
        DB::query('UPDATE online_payments SET checkout_url = ?, provider_ref = ?, updated_at = ? WHERE id = ?', [$url, $prov, now(), $id]); self::event($id, 'create', true, 'أُنشئت عملية دفع');
        AuditService::log('payments.online_start', 'online_payment', $id, null, ['amount' => Money::str($cents), 'currency' => $s['currency']]);
        return ['reference' => $ref, 'checkout_url' => $url];
    }
    /** @return array{0:string,1:?string} [رابط الدفع، مرجع المزود] */
    private static function createCheckout(string $ref, int $cents, string $currency): array
    {
        $tpl = (string)self::get('create_body', ''); $method = (string)self::get('create_method', 'POST'); $isJson = str_starts_with(ltrim($tpl), '{') || str_starts_with(ltrim($tpl), '[');
        $body = self::fill($tpl, ['amount' => Money::str($cents), 'amount_minor' => $cents, 'currency' => $currency, 'reference' => $ref, 'description' => 'رسوم دراسية',
            'return_url' => self::absolute(url('portal/pay/return/' . $ref)), 'callback_url' => self::webhookUrl() ?: throw new HttpException(500, 'رمز الإشعار غير مولَّد')], $isJson);
        $r = self::http($method, (string)self::get('create_url', ''), json_decode((string)self::get('create_headers', '{}'), true) ?: [], $body, $isJson);
        if ($r['error'] || $r['code'] < 200 || $r['code'] >= 300) { throw new HttpException(502, 'رمز استجابة ' . $r['code'] . ($r['error'] ? ' — ' . $r['error'] : '')); }
        $j = json_decode($r['body'], true); if (!is_array($j)) { throw new HttpException(502, 'رد المزود ليس JSON'); }
        $url = self::pathGet($j, (string)self::get('checkout_url_path', '')); if (!is_string($url) || !preg_match('#^https?://#i', $url) || (Env::isProduction() && !str_starts_with(strtolower($url), 'https://'))) { throw new HttpException(502, 'رابط الدفع غير موجود أو غير صالح في رد المزود'); }
        $prov = ($p = self::get('provider_ref_path')) ? self::pathGet($j, $p) : null;
        return [$url, is_scalar($prov) ? mb_substr((string)$prov, 0, 120) : null];
    }

    // ---------------- استقبال الإشعار (webhook) ----------------
    /** يُستدعى من مسار عام بلا جلسة. يعيد [httpStatus, message]. لا يقيّد أي مبلغ قبل التحقق. */
    public static function handleCallback(string $token, string $raw, array $headers, string $ip): array
    {
        $sid = DB::value("SELECT school_id FROM settings WHERE skey = 'pay.callback_token' AND svalue = ? AND school_id IS NOT NULL", [$token]);
        if (!$sid || !preg_match('/^[a-f0-9]{40}$/', $token)) { return [404, 'not found']; }
        Tenant::setForCli((int)$sid);
        try {
            if (self::driver() !== 'http') { return [404, 'not found']; }
            $headers = array_change_key_case($headers, CASE_LOWER); $secret = self::get('sig_secret'); $stUrl = self::get('st_url');
            if ($secret === null && !$stUrl) { self::event(null, 'callback', false, 'لا توجد وسيلة تحقق مضبوطة', $ip); return [403, 'unverifiable']; }
            if ($secret !== null) {
                $given = trim((string)($headers[strtolower((string)self::get('sig_header', 'X-Signature'))] ?? '')); if (str_contains($given, '=') && preg_match('/^[a-z0-9-]+=/i', $given)) { $given = substr($given, strpos($given, '=') + 1); }
                $bin = hash_hmac('sha256', $raw, $secret, true); $exp = self::get('sig_encoding', 'hex') === 'base64' ? base64_encode($bin) : bin2hex($bin);
                if ($given === '' || !hash_equals(strtolower($exp), strtolower($given))) { self::event(null, 'callback', false, 'توقيع غير صالح', $ip); return [401, 'invalid signature']; }
            }
            $p = json_decode($raw, true); if (!is_array($p)) { parse_str($raw, $p); }
            $ref = self::pathGet($p, (string)self::get('cb_reference_path', '')); $ref = is_scalar($ref) ? (string)$ref : '';
            $op = $ref !== '' ? DB::one('SELECT * FROM online_payments WHERE reference = ? AND school_id = ?', [$ref, $sid]) : null;
            if (!$op) { self::event(null, 'callback', false, 'مرجع غير معروف', $ip); return [404, 'unknown reference']; }
            $st = self::pathGet($p, (string)self::get('cb_status_path', '')); $st = strtolower(trim(is_scalar($st) ? (string)$st : ''));
            $okVals = array_map(fn($x) => strtolower(trim($x)), explode(',', (string)self::get('cb_success_values', '')));
            $failVals = array_filter(array_map(fn($x) => strtolower(trim($x)), explode(',', (string)self::get('cb_failed_values', ''))));
            if (!in_array($st, $okVals, true)) {
                if (in_array($st, $failVals, true) && $op['status'] === 'pending') { DB::query("UPDATE online_payments SET status = 'failed', failure_reason = ?, updated_at = ? WHERE id = ?", ['أبلغ المزود بالفشل (' . mb_substr($st, 0, 40) . ')', now(), $op['id']]); }
                self::event((int)$op['id'], 'callback', true, 'حالة غير ناجحة: ' . mb_substr($st, 0, 40), $ip); return [200, 'noted'];
            }
            $ac = ($ap = self::get('cb_amount_path')) ? self::pathGet($p, $ap) : null;
            if ($ac !== null && !self::sameAmount((string)$ac, $op['amount'])) { return self::flagReview($op, 'المبلغ في الإشعار (' . mb_substr((string)$ac, 0, 20) . ') لا يطابق المتوقع (' . $op['amount'] . ')', $ip); }
            if ($stUrl) {   // تحقق مباشر من المزود (يمنع انتحال الإشعار حتى لو سُرّب السر)
                $v = self::queryStatus($op); if ($v !== true) { self::event((int)$op['id'], 'status_check', false, (string)$v, $ip); return [202, 'not confirmed']; }
            }
            $prov = ($pp = self::get('provider_ref_path')) ? self::pathGet($p, $pp) : null;
            $res = self::markPaid((int)$op['id'], is_scalar($prov) ? (string)$prov : null, 'callback', $ip);
            return [200, $res];
        } finally { Tenant::setForCli(null); }
    }
    private static function sameAmount(string $reported, string $expected): bool { try { return Money::cents($reported) === Money::cents($expected); } catch (\InvalidArgumentException) { return false; } }
    private static function flagReview(array $op, string $why, ?string $ip): array
    {
        DB::query("UPDATE online_payments SET status = 'review', failure_reason = ?, updated_at = ? WHERE id = ? AND status <> 'paid'", [mb_substr($why, 0, 250), now(), $op['id']]);
        self::event((int)$op['id'], 'callback', false, $why, $ip); return [200, 'review'];
    }
    /** استعلام حالة العملية من المزود. يعيد true عند التأكد من النجاح، أو نص سبب عدم التأكيد. */
    private static function queryStatus(array $op): true|string
    {
        $tpl = (string)self::get('st_body', ''); $json = str_starts_with(ltrim($tpl), '{'); $method = (string)self::get('st_method', 'GET');
        $vars = ['reference' => $op['reference'], 'provider_ref' => (string)$op['provider_ref'], 'amount' => $op['amount'], 'currency' => $op['currency']];
        $url = self::fill((string)self::get('st_url', ''), $vars, false);   // في الرابط: ترميز URL
        $r = self::http($method, $url, json_decode((string)self::get('st_headers', '{}'), true) ?: [], $tpl !== '' ? self::fill($tpl, $vars, $json) : null, $json);
        if ($r['error'] || $r['code'] < 200 || $r['code'] >= 300) { return 'فشل الاستعلام: ' . $r['code'] . ($r['error'] ? ' ' . $r['error'] : ''); }
        $j = json_decode($r['body'], true); if (!is_array($j)) { return 'رد الاستعلام ليس JSON'; }
        $st = self::pathGet($j, (string)self::get('st_status_path', '')); $st = strtolower(trim(is_scalar($st) ? (string)$st : ''));
        $ok = array_map(fn($x) => strtolower(trim($x)), explode(',', (string)self::get('st_success_values', '')));
        if (!in_array($st, $ok, true)) { return 'المزود يقول: ' . mb_substr($st, 0, 40); }
        if (($ap = self::get('st_amount_path')) && ($a = self::pathGet($j, $ap)) !== null && !self::sameAmount((string)$a, $op['amount'])) { return 'مبلغ المزود لا يطابق'; }
        return true;
    }
    /** يقيّد الدفعة مرة واحدة (idempotent). يعيد 'paid' | 'already' | 'review'. */
    private static function markPaid(int $opId, ?string $providerRef, string $via, ?string $ip): string
    {
        return DB::transaction(function () use ($opId, $providerRef, $via, $ip) {
            $op = DB::one('SELECT * FROM online_payments WHERE id = ? AND school_id = ? FOR UPDATE', [$opId, Tenant::require()]) ?? throw new HttpException(404, 'غير موجودة');
            if ($op['status'] === 'paid') { self::event($opId, $via, true, 'إشعار مكرر — سبق القيد', $ip); return 'already'; }
            $prov = $providerRef ?: ($op['provider_ref'] ?: $op['reference']);
            try { $r = PaymentService::receiveVerifiedOnline((int)$op['student_fee_id'], $op['amount'], $op['currency'], (string)$prov); }
            catch (HttpException $e) {   // المال وصل لدى المزود لكن تعذّر قيده (مثلًا سُدد المبلغ نقدًا في الأثناء): يُترك لمراجعة بشرية ولا يضيع
                DB::query("UPDATE online_payments SET status = 'review', failure_reason = ?, updated_at = ? WHERE id = ?", ['تعذّر قيد الدفعة: ' . mb_substr($e->getMessage(), 0, 200), now(), $opId]);
                self::event($opId, $via, false, 'تعذّر القيد: ' . $e->getMessage(), $ip); return 'review';
            }
            DB::query("UPDATE online_payments SET status = 'paid', payment_id = ?, provider_ref = COALESCE(?, provider_ref), paid_at = ?, updated_at = ?, failure_reason = NULL WHERE id = ?", [$r['payment_id'], $providerRef, now(), now(), $opId]);
            self::event($opId, $via, true, 'قُيّدت الدفعة سند ' . $r['receipt_no'], $ip);
            AuditService::log('payments.online_paid', 'online_payment', $opId, null, ['amount' => $op['amount'], 'currency' => $op['currency'], 'receipt' => $r['receipt_no'], 'via' => $via]);
            try { if ($op['guardian_user_id']) { NotificationService::notify([(int)$op['guardian_user_id']], 'تم استلام دفعتك', 'سند القبض ' . $r['receipt_no'] . ' بمبلغ ' . Money::fmt($op['amount'], $op['currency']), url('portal')); } } catch (\Throwable) { /* الإشعار لا يمنع القيد */ }
            return 'paid';
        });
    }

    // ---------------- الوضع التجريبي ----------------
    private static function sandboxOp(string $reference): array
    {
        $op = DB::one('SELECT * FROM online_payments WHERE reference = ?', [$reference]);
        if (!$op || $op['driver'] !== 'sandbox' || Env::isProduction() || !preg_match('/^[a-f0-9]{24}$/', $reference)) { throw new HttpException(404, 'غير موجودة'); }
        return $op;
    }
    public static function sandboxInfo(string $reference): array
    {
        $op = self::sandboxOp($reference); Tenant::setForCli((int)$op['school_id']);
        try { return $op + ['school' => DB::value('SELECT name FROM schools WHERE id = ?', [$op['school_id']])]; } finally { Tenant::setForCli(null); }
    }
    public static function sandboxFinish(string $reference, bool $pay): string
    {
        $op = self::sandboxOp($reference); Tenant::setForCli((int)$op['school_id']);
        try {
            if (!$pay) { DB::query("UPDATE online_payments SET status = 'failed', failure_reason = 'أُلغيت في الصفحة التجريبية', updated_at = ? WHERE id = ? AND status = 'pending'", [now(), $op['id']]); return 'failed'; }
            return self::markPaid((int)$op['id'], null, 'sandbox', null);
        } finally { Tenant::setForCli(null); }
    }

    // ---------------- القراءة والمطابقة ----------------
    private static function expireOld(): void
    {
        $m = max(10, (int)(self::get('expire_minutes', '60') ?: 60));
        DB::query("UPDATE online_payments SET status = 'expired', updated_at = ? WHERE school_id = ? AND status = 'pending' AND created_at < NOW() - INTERVAL $m MINUTE", [now(), Tenant::require()]);
    }
    /** حالة عملية يملكها ولي الأمر الحالي (لصفحة العودة من المزود). لا تعتمد على أي معامل في الرابط سوى المرجع. */
    public static function ownStatus(string $reference): array
    {
        Auth::authorize('portal.view'); self::expireOld();
        $op = DB::one('SELECT op.*, (SELECT r.number FROM receipts r WHERE r.payment_id = op.payment_id) AS receipt_no, (SELECT e.student_id FROM student_fees sf JOIN student_enrollments e ON e.id = sf.enrollment_id WHERE sf.id = op.student_fee_id) AS student_id FROM online_payments op WHERE op.reference = ? AND op.school_id = ? AND op.guardian_user_id = ?', [$reference, Tenant::require(), Auth::id()]) ?? throw new HttpException(404, 'العملية غير موجودة');
        return $op;
    }
    public static function list(string $status = ''): array
    {
        Auth::authorize('fees.view'); self::expireOld(); $w = 'op.school_id = ?'; $p = [Tenant::require()]; if (isset(self::STATUSES[$status])) { $w .= ' AND op.status = ?'; $p[] = $status; }
        return DB::all("SELECT op.id, op.reference, op.amount, op.currency, op.status, op.driver, op.failure_reason, op.created_at, op.paid_at, s.full_name, s.student_no, u.full_name AS guardian
            FROM online_payments op JOIN student_fees sf ON sf.id = op.student_fee_id JOIN student_enrollments e ON e.id = sf.enrollment_id JOIN students s ON s.id = e.student_id LEFT JOIN users u ON u.id = op.guardian_user_id WHERE $w ORDER BY op.id DESC LIMIT 200", $p);
    }
    /** مطابقة يدوية: يستعلم من المزود عن عملية معلّقة/منتهية/تحتاج مراجعة ويقيّدها إن ثبت دفعها. */
    public static function reconcile(int $id): string
    {
        Auth::authorize('fees.collect'); $op = DB::one('SELECT * FROM online_payments WHERE id = ? AND school_id = ?', [$id, Tenant::require()]) ?? throw new HttpException(404, 'غير موجودة');
        if ($op['status'] === 'paid') { return 'مدفوعة مسبقًا'; }
        if ($op['driver'] !== 'http' || !self::get('st_url')) { throw new HttpException(422, 'لم يُضبط رابط استعلام الحالة لدى المزود'); }
        $v = self::queryStatus($op); self::event($id, 'status_check', $v === true, $v === true ? 'المزود أكّد الدفع' : (string)$v, null);
        if ($v !== true) { return 'لم يؤكد المزود الدفع: ' . $v; }
        return self::markPaid($id, null, 'reconcile', null) === 'paid' ? 'تم قيد الدفعة' : 'تعذّر القيد — راجع التفاصيل';
    }
    public static function events(int $id): array
    {
        Auth::authorize('fees.view'); return DB::all('SELECT kind, ok, detail, ip, created_at FROM online_payment_events WHERE online_payment_id = ? AND school_id = ? ORDER BY id DESC LIMIT 30', [$id, Tenant::require()]);
    }
}
