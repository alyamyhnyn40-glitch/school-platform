<?php
declare(strict_types=1);
namespace App\Services\Sms;

use App\Core\Logger;

/**
 * مزود HTTP عام يناسب أغلب مزودي الرسائل المحليين. الإعداد من لوحة المشغّل (أو .env احتياطيًا).
 * القالب يدعم: {to} الرقم الدولي مع +، {to_digits} الرقم بأرقام فقط بلا +، {message} نص الرسالة،
 * وبيانات المزود: {sender} {api_key} {username} {password} {base64_auth} (تُملأ من حقول الإعداد فلا تُكتب في القالب).
 */
final class HttpSmsGateway implements SmsGateway
{
    public function __construct(private string $channel = 'sms') {}
    /** نتيجة آخر إرسال (للتشخيص في صفحة الاختبار): code, error, response */
    public static array $last = [];

    public function send(string $to, string $message): bool
    {
        self::$last = ['code' => 0, 'error' => null, 'response' => ''];
        $tok = SmsConfig::tokens($this->channel); $url = strtr(SmsConfig::url($this->channel), array_map('rawurlencode', $tok));
        if ($url === '' || !preg_match('#^https?://#i', $url)) { Logger::error('رابط مزود الرسائل غير مضبوط أو غير صالح', ['channel' => $this->channel]); self::$last['error'] = 'رابط المزود غير مضبوط'; return false; }
        $method = SmsConfig::method($this->channel); $tpl = SmsConfig::body($this->channel);
        $jsonEsc = fn(string $s) => substr(json_encode($s, JSON_UNESCAPED_UNICODE), 1, -1);
        $isJson = str_starts_with(ltrim($tpl), '{') || str_starts_with(ltrim($tpl), '[');
        $enc = fn(string $s) => $isJson ? $jsonEsc($s) : urlencode($s);
        $body = strtr($tpl, ['{to}' => $enc($to), '{to_digits}' => $enc(ltrim($to, '+')), '{message}' => $enc($message)] + array_map($enc, $tok));
        $h = [];
        foreach (SmsConfig::headers($this->channel) as $k => $v) { $h[] = $k . ': ' . strtr((string)$v, $tok); }
        if ($isJson) { $h[] = 'Content-Type: application/json'; }
        $ch = curl_init($method === 'GET' ? $url . (str_contains($url, '?') ? '&' : '?') . $body : $url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15, CURLOPT_CONNECTTIMEOUT => 8, CURLOPT_HTTPHEADER => $h, CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS]);
        if ($method === 'POST') { curl_setopt($ch, CURLOPT_POST, true); curl_setopt($ch, CURLOPT_POSTFIELDS, $body); }
        $resp = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); $err = curl_error($ch);
        curl_close($ch);
        $resp = is_string($resp) ? $resp : '';
        $ok = $code === SmsConfig::successCode($this->channel);
        if ($ok && ($m = SmsConfig::successMatch($this->channel)) !== '' && !str_contains($resp, $m)) { $ok = false; $err = 'الاستجابة لا تحوي النص المتوقع للنجاح'; }
        self::$last = ['code' => $code, 'error' => $err !== '' ? $err : null, 'response' => mb_substr($resp, 0, 300)];
        if (!$ok) { Logger::error('فشل إرسال رسالة', ['channel' => $this->channel, 'code' => $code, 'err' => $err]); }
        return $ok;
    }
}
