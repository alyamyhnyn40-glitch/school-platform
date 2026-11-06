<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\{Env, HttpException};

/**
 * حماية SSRF لروابط يضبطها مديرو المدارس (بوابة الدفع): https فقط في الإنتاج، وحجب العناوين الداخلية/المحلية،
 * وتثبيت عنوان IP الذي فُحص (ضد DNS rebinding). في بيئات التطوير (APP_ENV != production) يُسمح بـ http والمضيف المحلي للتجربة.
 */
final class UrlGuard
{
    /** @return array{url:string, host:string, port:int, ip:string} */
    public static function check(string $url): array
    {
        $u = parse_url($url); $prod = Env::isProduction();
        $scheme = strtolower((string)($u['scheme'] ?? '')); $host = (string)($u['host'] ?? '');
        if ($host === '' || !in_array($scheme, $prod ? ['https'] : ['http', 'https'], true)) { throw new HttpException(422, $prod ? 'الرابط يجب أن يبدأ بـ https://' : 'رابط غير صالح'); }
        if (isset($u['user']) || isset($u['pass'])) { throw new HttpException(422, 'لا يُسمح ببيانات دخول داخل الرابط'); }
        $port = (int)($u['port'] ?? ($scheme === 'https' ? 443 : 80));
        $ips = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : (gethostbynamel($host) ?: []);
        if (!$ips) { throw new HttpException(422, 'تعذّر حل اسم المضيف'); }
        foreach ($ips as $ip) {
            if ($prod && !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) { throw new HttpException(422, 'لا يُسمح بعناوين داخلية أو محجوزة'); }
        }
        return ['url' => $url, 'host' => $host, 'port' => $port, 'ip' => $ips[0]];
    }
}
