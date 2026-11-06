<?php
declare(strict_types=1);
namespace App\Services\Sms;

final class SmsFactory
{
    private static ?SmsGateway $override = null; private static array $overrideBy = [];
    public static function set(?SmsGateway $g): void { self::$override = $g; }
    public static function setFor(string $channel, ?SmsGateway $g): void { if ($g) { self::$overrideBy[$channel] = $g; } else { unset(self::$overrideBy[$channel]); } }
    public static function make(): SmsGateway { return self::makeFor('sms'); }
    /** مزود قناة (sms | whatsapp). */
    public static function makeFor(string $channel): SmsGateway
    {
        if (isset(self::$overrideBy[$channel])) { return self::$overrideBy[$channel]; }
        if ($channel === 'sms' && self::$override) { return self::$override; }
        return SmsConfig::driver($channel) === 'http' ? new HttpSmsGateway($channel) : new LogSmsGateway($channel === 'whatsapp' ? 'whatsapp.log' : 'sms.log');
    }
}
