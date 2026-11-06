<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\{Env, Logger};
use PHPMailer\PHPMailer\PHPMailer;

/**
 * إرسال البريد: تجريبي (ملف سجل) أو SMTP عبر PHPMailer أو mail() من PHP.
 * الإعداد من لوحة المشغّل (mail.*) وكلمة المرور مشفّرة؛ وملف .env (MAIL_DRIVER=mail) احتياطي قديم.
 * يمنع حقن الترويسات (CR/LF) ويتحقق من العناوين.
 */
final class EmailService
{
    /** نتيجة آخر محاولة (للتشخيص في صفحة الاختبار) */
    public static array $last = [];

    private static function cfg(string $k, ?string $d = null, bool $secret = false): ?string { try { return PlatformSettings::get('mail.' . $k, $d, $secret); } catch (\Throwable) { return $d; } }
    public static function driver(): string { return self::cfg('driver') ?? ((string)Env::get('MAIL_DRIVER', 'log') === 'mail' ? 'mail' : 'log'); }
    public static function enabled(): bool { return in_array(self::driver(), ['smtp', 'mail'], true); }
    public static function ready(): bool
    {
        $from = self::fromEmail();
        if (!filter_var($from, FILTER_VALIDATE_EMAIL)) { return false; }
        if (self::driver() === 'smtp') {
            return self::cfg('host', '') !== ''
                && self::cfg('username', '') !== ''
                && self::cfg('password', '', true) !== '';
        }
        return self::driver() === 'mail';
    }
    public static function fromEmail(): string { return (string)(self::cfg('from_email') ?: (Brand::get('contact_email') ?: 'noreply@localhost')); }

    public static function send(string $to, string $subject, string $text): bool
    {
        self::$last = ['ok' => false, 'error' => null, 'driver' => self::driver()];
        $to = trim($to); if (!filter_var($to, FILTER_VALIDATE_EMAIL) || preg_match('/[\r\n]/', $to)) { self::$last['error'] = 'عنوان المستلم غير صالح'; return false; }
        $subject = trim(preg_replace('/[\r\n]+/', ' ', $subject)); $driver = self::driver();
        try {
            if ($driver === 'smtp') { self::smtp($to, $subject, $text); }
            elseif ($driver === 'mail') {
                $ok = @mail($to, self::encHeader($subject), $text, implode("\r\n", ['From: ' . self::fromHeader(), 'MIME-Version: 1.0', 'Content-Type: text/plain; charset=UTF-8', 'Content-Transfer-Encoding: base64']) . "\r\n", ''); if (!$ok) { throw new \RuntimeException('فشل mail()'); }
            } else { @file_put_contents(storage_path('logs/mail.log'), sprintf("[%s] to=%s subject=%s\n%s\n---\n", date('c'), $to, $subject, $text), FILE_APPEND | LOCK_EX); }
            self::$last['ok'] = true; return true;
        } catch (\Throwable $e) { self::$last['error'] = $e->getMessage(); Logger::error('فشل إرسال بريد', ['driver' => $driver, 'e' => $e->getMessage()]); return false; }
    }
    private static function encHeader(string $s): string { return '=?UTF-8?B?' . base64_encode($s) . '?='; }
    private static function fromHeader(): string
    {
        $name = trim(preg_replace('/[\r\n<>"]+/', ' ', (string)(self::cfg('from_name') ?: Brand::name())));
        return self::encHeader($name) . ' <' . self::fromEmail() . '>';
    }
    private static function smtp(string $to, string $subject, string $text): void
    {
        $host = (string)self::cfg('host', ''); $port = (int)self::cfg('port', '587'); $enc = (string)self::cfg('encryption', 'tls'); $user = (string)self::cfg('username', ''); $pass = (string)self::cfg('password', '', true);
        if ($host === '') { throw new \RuntimeException('خادم SMTP غير مضبوط'); }
        $self = self::cfg('allow_self_signed') === '1';
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $host;
        $mail->Port = $port;
        $mail->Timeout = 20;
        $mail->SMTPDebug = 0;
        $mail->SMTPAuth = $user !== '';
        $mail->Username = $user;
        $mail->Password = $pass;
        $mail->SMTPSecure = match ($enc) {
            'ssl' => PHPMailer::ENCRYPTION_SMTPS,
            'tls' => PHPMailer::ENCRYPTION_STARTTLS,
            default => '',
        };
        $mail->SMTPAutoTLS = $enc === 'none' && !in_array($host, ['127.0.0.1', 'localhost', '::1'], true);
        $mail->SMTPOptions = ['ssl' => [
            'verify_peer' => !$self,
            'verify_peer_name' => !$self,
            'allow_self_signed' => $self,
            'peer_name' => $host,
        ]];
        $mail->CharSet = PHPMailer::CHARSET_UTF8;
        $mail->setFrom(self::fromEmail(), (string)(self::cfg('from_name') ?: Brand::name()));
        $mail->addAddress($to);
        $mail->Subject = $subject;
        $mail->Body = $text;
        try {
            $mail->send();
        } catch (\Throwable $e) {
            $smtpError = $mail->getSMTPInstance()->getError();
            $code = (int)($smtpError['smtp_code'] ?? 0);
            $codeEx = (string)($smtpError['smtp_code_ex'] ?? '');
            $diagnostic = implode(' ', [
                (string)($smtpError['error'] ?? ''),
                (string)($smtpError['detail'] ?? ''),
                $mail->ErrorInfo,
            ]);
            throw new \RuntimeException(self::smtpFailureMessage($code, $codeEx, $host, $port, $enc, $e, $diagnostic), 0, $e);
        }
    }

    private static function smtpFailureMessage(int $code, string $codeEx, string $host, int $port, string $encryption, \Throwable $cause, string $diagnostic = ''): string
    {
        $context = strtolower($cause->getMessage() . ' ' . $diagnostic);
        if ($code === 0 && preg_match('/\b([245]\d{2})\b/', $diagnostic, $matches)) {
            $code = (int)$matches[1];
        }
        if ($codeEx === '' && preg_match('/\b([245]\.\d{1,3}\.\d{1,3})\b/', $diagnostic, $matches)) {
            $codeEx = $matches[1];
        }
        $status = $code > 0 ? ' (SMTP ' . $code . ($codeEx !== '' ? ' / ' . $codeEx : '') . ')' : '';
        $message = match (true) {
            $code === 535 => 'رفض خادم البريد اسم المستخدم أو كلمة المرور. تحقق من البريد الكامل وكلمة المرور، ثم احفظ الإعدادات. في Gmail استخدم كلمة مرور تطبيق بعد تفعيل التحقق بخطوتين، وليس كلمة مرور الحساب.',
            $code === 534 => 'رفض مزود البريد طريقة المصادقة أو طلب تحققًا إضافيًا. راجع متطلبات الأمان لدى المزود؛ في Gmail استخدم كلمة مرور تطبيق.',
            $code === 530 || $code === 538 => 'خادم البريد يتطلب مصادقة أو اتصالًا مشفرًا. تحقق من اسم المستخدم وكلمة المرور، واختر TLS مع المنفذ 587 أو SSL مع المنفذ 465 وفق إعداد المزود.',
            in_array($code, [421, 450, 451, 452, 454], true) => 'خادم البريد غير متاح مؤقتًا أو أوقف الطلب مؤقتًا. انتظر قليلًا ثم أعد المحاولة، وتحقق من حدود الإرسال لدى المزود.',
            in_array($code, [550, 551, 552, 553], true) => 'رفض الخادم المرسل أو المستلم أو الرسالة. تحقق من صلاحية بريد المرسل، وأن الحساب مخوّل بالإرسال بهذا العنوان.',
            $code >= 500 => 'رفض خادم البريد الطلب. راجع إعدادات الحساب وسياسة الإرسال لدى مزود البريد.',
            str_contains($context, 'authenticat') || str_contains($context, 'authentication') => 'فشلت مصادقة SMTP. تحقق من اسم المستخدم وكلمة المرور المحفوظين، وأن SMTP AUTH مفعّل لدى المزود. في Gmail استخدم البريد الكامل وكلمة مرور تطبيق بعد تفعيل التحقق بخطوتين.',
            str_contains($context, 'connect') || str_contains($context, 'connection') => 'تعذّر إنشاء اتصال بخادم SMTP. تحقق من اسم الخادم والمنفذ والاتصال بالإنترنت، ومن أن الجدار الناري يسمح بالاتصال.',
            str_contains($context, 'certificate') || str_contains($context, 'crypto') || str_contains($context, 'starttls') || str_contains($context, 'tls') => 'تعذّر تأمين اتصال TLS. تحقق من إعداد التشفير والمنفذ ووقت النظام، ولا تعطّل التحقق من الشهادة إلا للاختبار المؤقت.',
            default => 'تعذّر إكمال إرسال SMTP. تحقق من إعدادات الخادم والمنفذ والتشفير وبيانات الدخول، ثم راجع سجل التطبيق لمزيد من التشخيص.',
        };

        return 'فشل SMTP إلى ' . $host . ':' . $port . ' (' . strtoupper($encryption) . ')' . $status . '. ' . $message;
    }
}
