<?php
declare(strict_types=1);
namespace App\Services\Sms;

/** مزود للتطوير والاختبار: يكتب الرسالة في storage/logs/sms.log (أو whatsapp.log لواتساب). */
final class LogSmsGateway implements SmsGateway
{
    public function __construct(private string $file = 'sms.log') {}
    public function send(string $to, string $message): bool
    {
        @file_put_contents(storage_path('logs/' . $this->file), sprintf("[%s] to=%s msg=%s\n", date('c'), $to, $message), FILE_APPEND | LOCK_EX);
        return true;
    }
}
