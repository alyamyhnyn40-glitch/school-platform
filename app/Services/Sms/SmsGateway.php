<?php
declare(strict_types=1);
namespace App\Services\Sms;

interface SmsGateway
{
    /** يعيد true عند نجاح التسليم لمزود الرسائل. */
    public function send(string $to, string $message): bool;
}
