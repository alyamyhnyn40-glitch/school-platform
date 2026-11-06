<?php
declare(strict_types=1);
namespace App\Services;

/** عميل S3 مبسّط (AWS Signature V4، نمط المسار) يعمل مع AWS وCloudflare R2 وBackblaze B2 وWasabi وDigitalOcean Spaces وMinIO. */
final class S3Client
{
    public static array $last = [];
    public function __construct(private string $endpoint, private string $region, private string $bucket, private string $key, private string $secret) {}

    /** يحسب ترويسة Authorization (دالة نقية لتسهيل الاختبار بمتجهات AWS الرسمية). */
    public static function authorization(string $method, string $uri, string $query, array $headers, string $payloadHash, string $amzDate, string $region, string $service, string $key, string $secret): string
    {
        $date = substr($amzDate, 0, 8); $h = []; foreach ($headers as $k => $v) { $h[strtolower($k)] = trim(preg_replace('/\s+/', ' ', (string)$v)); } ksort($h);
        $canonHeaders = ''; foreach ($h as $k => $v) { $canonHeaders .= "$k:$v\n"; } $signed = implode(';', array_keys($h));
        $canon = implode("\n", [$method, $uri, $query, $canonHeaders, $signed, $payloadHash]);
        $scope = "$date/$region/$service/aws4_request"; $sts = implode("\n", ['AWS4-HMAC-SHA256', $amzDate, $scope, hash('sha256', $canon)]);
        $k = hash_hmac('sha256', 'aws4_request', hash_hmac('sha256', $service, hash_hmac('sha256', $region, hash_hmac('sha256', $date, 'AWS4' . $secret, true), true), true), true);
        return "AWS4-HMAC-SHA256 Credential=$key/$scope, SignedHeaders=$signed, Signature=" . hash_hmac('sha256', $sts, $k);
    }
    private static function encPath(string $p): string { return implode('/', array_map('rawurlencode', explode('/', $p))); }
    /** @return array{code:int, body:string, error:?string} */
    private function request(string $method, string $objKey, string $query = '', ?string $file = null): array
    {
        $g = UrlGuard::check($this->endpoint); $u = parse_url($this->endpoint); $host = $u['host'] . (isset($u['port']) ? ':' . $u['port'] : '');
        $path = '/' . $this->bucket . ($objKey !== '' ? '/' . ltrim($objKey, '/') : ''); $uri = self::encPath($path);
        $hash = $file !== null ? hash_file('sha256', $file) : hash('sha256', ''); $amz = gmdate('Ymd\THis\Z');
        $hdr = ['host' => $host, 'x-amz-content-sha256' => $hash, 'x-amz-date' => $amz];
        $auth = self::authorization($method, $uri, $query, $hdr, $hash, $amz, $this->region, 's3', $this->key, $this->secret);
        $ch = curl_init(rtrim(($u['scheme'] ?? 'https') . '://' . $host, '/') . $uri . ($query !== '' ? '?' . $query : '')); $buf = '';
        $opts = [CURLOPT_CUSTOMREQUEST => $method, CURLOPT_TIMEOUT => 300, CURLOPT_CONNECTTIMEOUT => 10, CURLOPT_FOLLOWLOCATION => false, CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_RESOLVE => [$g['host'] . ':' . $g['port'] . ':' . $g['ip']],
            CURLOPT_HTTPHEADER => ['Authorization: ' . $auth, 'x-amz-content-sha256: ' . $hash, 'x-amz-date: ' . $amz, 'Expect:'],
            CURLOPT_WRITEFUNCTION => function ($c, $d) use (&$buf) { $buf .= $d; return strlen($buf) > 262144 ? 0 : strlen($d); }];
        $fh = null; if ($file !== null) { $fh = fopen($file, 'rb'); $opts += [CURLOPT_UPLOAD => true, CURLOPT_INFILE => $fh, CURLOPT_INFILESIZE => filesize($file)]; }
        curl_setopt_array($ch, $opts); curl_exec($ch); $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); $err = curl_error($ch); curl_close($ch); if ($fh) { fclose($fh); }
        return self::$last = ['code' => $code, 'body' => $buf, 'error' => $err !== '' ? $err : null];
    }
    private static function why(array $r): string
    {
        if ($r['error']) { return 'تعذّر الاتصال: ' . $r['error']; }
        $m = preg_match('#<Code>([^<]+)</Code>#', $r['body'], $x) ? ' (' . $x[1] . ')' : ''; return 'رمز استجابة ' . $r['code'] . $m;
    }
    public function put(string $objKey, string $file): void { $r = $this->request('PUT', $objKey, '', $file); if ($r['code'] < 200 || $r['code'] >= 300) { throw new \RuntimeException(self::why($r)); } }
    public function delete(string $objKey): void { $r = $this->request('DELETE', $objKey); if ($r['code'] < 200 || $r['code'] >= 300) { throw new \RuntimeException(self::why($r)); } }
    /** يتحقق من الوصول إلى الحاوية (قائمة بعنصر واحد). */
    public function check(): void { $r = $this->request('GET', '', 'list-type=2&max-keys=1'); if ($r['code'] !== 200) { throw new \RuntimeException(self::why($r)); } }
}
