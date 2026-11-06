<?php
declare(strict_types=1);
namespace App\Core;

final class Response
{
    public function __construct(public string $body = '', public int $status = 200, public array $headers = []) {}

    public static function json(array $data, int $status = 200): self
    {
        return new self(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $status, ['Content-Type' => 'application/json; charset=utf-8']);
    }
    public static function ok(mixed $data = null, array $meta = []): self
    {
        return self::json(['ok' => true, 'data' => $data] + ($meta ? ['meta' => $meta] : []));
    }
    public static function fail(string $code, string $message, int $status = 400, array $fields = []): self
    {
        return self::json(['ok' => false, 'error' => ['code' => $code, 'message' => $message] + ($fields ? ['fields' => $fields] : [])], $status);
    }
    public static function redirect(string $to, int $status = 302): self { return new self('', $status, ['Location' => str_starts_with($to, 'http') ? $to : url($to)]); }
    public static function html(string $html, int $status = 200): self { return new self($html, $status, ['Content-Type' => 'text/html; charset=utf-8']); }

    public function send(): void
    {
        http_response_code($this->status);
        foreach ($this->headers as $k => $v) { header("$k: $v"); }
        echo $this->body;
    }
}
