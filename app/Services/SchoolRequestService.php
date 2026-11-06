<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\{Auth, DB, Env, HttpException};
use App\Middleware\RateLimitMiddleware;

/**
 * طلبات تسجيل المدارس: نموذج عام في صفحة الهبوط (محمي: honeypot + سؤال حسابي موقَّع + حدود معدل + تحقق الشعار) ثم مراجعة المشغّل.
 * عند القبول تُنشأ المدرسة بحساب مديرها وتُرسل رسالة للمتقدّم؛ عند الرفض يُكتب السبب ويُبلَّغ المتقدّم.
 */
final class SchoolRequestService
{
    public const STATUSES = ['pending' => 'قيد المراجعة', 'approved' => 'مقبول', 'rejected' => 'مرفوض'];
    private const LOGO_DIR = 'uploads/requests'; private const CHALLENGE_TTL = 3600;

    // ---------- سؤال التحقق (بلا خدمات خارجية) ----------
    /** @return array{question:string, token:string} */
    public static function challenge(): array
    {
        $a = random_int(2, 9); $b = random_int(2, 9); $exp = time() + self::CHALLENGE_TTL; $p = "$exp|" . ($a + $b);
        return ['question' => "$a + $b", 'token' => $p . '|' . hash_hmac('sha256', $p, (string)Env::get('APP_ENC_KEY', 'k'))];
    }
    private static function challengeOk(string $token, string $answer): bool
    {
        $parts = explode('|', $token); if (count($parts) !== 3 || !ctype_digit($parts[0]) || !ctype_digit($parts[1])) { return false; }
        if ((int)$parts[0] < time() || !hash_equals(hash_hmac('sha256', "$parts[0]|$parts[1]", (string)Env::get('APP_ENC_KEY', 'k')), $parts[2])) { return false; }
        return trim($answer) === $parts[1];
    }

    // ---------- التقديم (عام) ----------
    public static function submit(array $d, ?array $logo, string $ip): int
    {
        if (!Brand::enabled('allow_school_requests')) { throw new HttpException(403, 'استقبال الطلبات متوقف حاليًا'); }
        if (trim((string)($d['website_url'] ?? '')) !== '') { throw new HttpException(422, 'تعذّر إرسال الطلب'); }   // honeypot: الروبوتات تملؤه
        if (!self::challengeOk((string)($d['challenge_token'] ?? ''), (string)($d['challenge_answer'] ?? ''))) { throw new HttpException(422, 'إجابة سؤال التحقق غير صحيحة أو منتهية', ['challenge_answer' => 'غير صحيحة']); }
        if (!RateLimitMiddleware::hit("schoolreq:$ip", 3, 3600)) { throw new HttpException(429, 'تجاوزت عدد الطلبات المسموح بها. حاول لاحقًا.'); }
        $t = fn(string $k, int $max) => mb_substr(trim((string)($d[$k] ?? '')), 0, $max);
        $name = $t('school_name', 200); if (mb_strlen($name) < 3) { throw new HttpException(422, 'اسم المدرسة مطلوب', ['school_name' => 'مطلوب']); }
        $cname = $t('contact_name', 200); if (mb_strlen($cname) < 3) { throw new HttpException(422, 'اسم المسؤول مطلوب', ['contact_name' => 'مطلوب']); }
        $email = mb_strtolower($t('contact_email', 190)); if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { throw new HttpException(422, 'بريد غير صالح', ['contact_email' => 'غير صالح']); }
        $phone = $t('contact_phone', 30); if ($phone !== '') { $phone = Phone::normalize($phone); if (!preg_match('/^\+\d{9,15}$/', $phone)) { throw new HttpException(422, 'هاتف غير صالح', ['contact_phone' => 'غير صالح']); } }
        $gp = in_array($d['gender_policy'] ?? 'mixed', ['mixed', 'boys', 'girls'], true) ? $d['gender_policy'] : 'mixed';
        $est = ($d['students_estimate'] ?? '') === '' ? null : (int)$d['students_estimate']; if ($est !== null && ($est < 1 || $est > 100000)) { throw new HttpException(422, 'عدد الطلاب غير صالح', ['students_estimate' => 'غير صالح']); }
        if (DB::value("SELECT 1 FROM school_requests WHERE contact_email = ? AND status = 'pending'", [$email])) { throw new HttpException(409, 'لديكم طلب قيد المراجعة بهذا البريد. سنتواصل معكم قريبًا.'); }
        if (DB::value('SELECT 1 FROM users WHERE email = ? AND deleted_at IS NULL', [$email])) { throw new HttpException(409, 'هذا البريد مستخدم في حساب قائم. استخدموا بريدًا آخر أو تواصلوا معنا.'); }
        $file = $logo ? ImageUpload::storeSafe($logo, storage_path(self::LOGO_DIR), 1048576, 512) : null;
        DB::query('INSERT INTO school_requests (school_name, city, governorate, address, gender_policy, stages, students_estimate, contact_name, contact_email, contact_phone, notes, logo_file, ip) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)',
            [$name, $t('city', 100) ?: null, $t('governorate', 100) ?: null, $t('address', 255) ?: null, $gp, $t('stages', 200) ?: null, $est, $cname, $email, $phone ?: null, $t('notes', 1000) ?: null, $file, $ip]);
        $id = DB::lastId();
        EmailService::send($email, 'استلمنا طلب تسجيل مدرستكم — ' . Brand::name(), "مرحبًا {$cname}،\n\nاستلمنا طلب تسجيل مدرسة «{$name}» في " . Brand::name() . " وسيراجعه فريقنا ونتواصل معكم عبر هذا البريد.\n\nرقم الطلب: $id");
        if (($to = Brand::get('contact_email')) !== '') { EmailService::send($to, 'طلب تسجيل مدرسة جديد #' . $id, "طلب جديد من «{$name}» ({$cname} - {$email}). راجعه من لوحة المشغّل: " . rtrim((string)Env::get('APP_URL', ''), '/') . url('platform/requests')); }
        return $id;
    }

    // ---------- المراجعة (المشغّل) ----------
    public static function pendingCount(): int { return Auth::can('platform.schools') ? (int)DB::value("SELECT COUNT(*) FROM school_requests WHERE status = 'pending'") : 0; }
    public static function list(string $status = ''): array
    {
        Auth::authorize('platform.schools'); $w = '1=1'; $p = []; if (isset(self::STATUSES[$status])) { $w = 'r.status = ?'; $p[] = $status; }
        return DB::all("SELECT r.*, u.full_name AS decided_name FROM school_requests r LEFT JOIN users u ON u.id = r.decided_by WHERE $w ORDER BY (r.status = 'pending') DESC, r.id DESC LIMIT 200", $p);
    }
    public static function find(int $id): array { Auth::authorize('platform.schools'); return DB::one('SELECT * FROM school_requests WHERE id = ?', [$id]) ?? throw new HttpException(404, 'الطلب غير موجود'); }
    public static function logoPath(int $id): ?string
    {
        $r = self::find($id); if (!$r['logo_file'] || !preg_match('/^[a-f0-9]{32}\.(png|jpg|webp)$/', $r['logo_file'])) { return null; }
        $p = storage_path(self::LOGO_DIR . '/' . $r['logo_file']); return is_file($p) ? $p : null;
    }
    /** @return array{school_id:int, temp_password:string} */
    public static function approve(int $id, int $planId, array $over = []): array
    {
        Auth::authorize('platform.schools'); $r = self::find($id); if ($r['status'] !== 'pending') { throw new HttpException(422, 'الطلب سبقت معالجته'); }
        return DB::transaction(function () use ($r, $id, $planId, $over) {
            [$sid, $temp] = SchoolService::create(['name' => trim((string)($over['school_name'] ?? '')) ?: $r['school_name'], 'plan_id' => $planId, 'admin_name' => $r['contact_name'], 'admin_email' => $r['contact_email'], 'admin_phone' => $r['contact_phone'] ?? '',
                'city' => $r['city'], 'governorate' => $r['governorate'], 'address' => $r['address'], 'phone' => $r['contact_phone'], 'email' => $r['contact_email'], 'gender_policy' => $r['gender_policy']]);
            if ($r['logo_file'] && ($src = self::logoPath($id))) {   // شعار المدرسة المعتمد
                $dir = storage_path('uploads/school-logos'); if (!is_dir($dir)) { @mkdir($dir, 0775, true); } $ext = pathinfo($src, PATHINFO_EXTENSION); $dest = "$dir/$sid.$ext";
                if (@copy($src, $dest)) { \App\Core\Tenant::setForCli($sid); try { SettingsService::set('logo_file', "$sid.$ext"); } finally { \App\Core\Tenant::setForCli(null); } }
            }
            DB::query("UPDATE school_requests SET status = 'approved', decided_by = ?, decided_at = ?, school_id = ?, plan_id = ? WHERE id = ?", [Auth::id(), now(), $sid, $planId, $id]);
            AuditService::log('school_request.approve', 'school_request', $id, null, ['school_id' => $sid, 'plan_id' => $planId]);
            $login = rtrim((string)Env::get('APP_URL', ''), '/') . url('login');
            EmailService::send($r['contact_email'], 'تم قبول طلب مدرستكم — ' . Brand::name(), "مرحبًا {$r['contact_name']}،\n\nيسرّنا إبلاغكم بقبول طلب تسجيل مدرسة «{$r['school_name']}».\n\nللدخول: $login\nاسم المستخدم: {$r['contact_email']}\n\nلتعيين كلمة المرور اختاروا «نسيت كلمة المرور» وأدخلوا هذا البريد لتصلكم رمز تحقق، أو تواصلوا معنا للحصول على كلمة مرور مؤقتة.");
            return ['school_id' => $sid, 'temp_password' => $temp];
        });
    }
    public static function reject(int $id, string $reason): void
    {
        Auth::authorize('platform.schools'); $r = self::find($id); if ($r['status'] !== 'pending') { throw new HttpException(422, 'الطلب سبقت معالجته'); }
        if (mb_strlen(trim($reason)) < 5) { throw new HttpException(422, 'اكتب سبب الرفض (5 أحرف على الأقل)', ['note' => 'مطلوب']); }
        DB::query("UPDATE school_requests SET status = 'rejected', decided_by = ?, decided_at = ?, decision_note = ? WHERE id = ?", [Auth::id(), now(), mb_substr(trim($reason), 0, 500), $id]);
        AuditService::log('school_request.reject', 'school_request', $id, null, ['reason' => $reason]);
        EmailService::send($r['contact_email'], 'بشأن طلب تسجيل مدرستكم — ' . Brand::name(), "مرحبًا {$r['contact_name']}،\n\nنأسف لإبلاغكم بتعذّر قبول طلب تسجيل مدرسة «{$r['school_name']}» حاليًا.\nالسبب: " . trim($reason) . "\n\nيمكنكم التواصل معنا لأي استفسار أو إعادة التقديم بعد استكمال المتطلبات.");
    }
}
