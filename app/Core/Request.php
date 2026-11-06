<?php
declare(strict_types=1);
namespace App\Core;

final class Request
{
    public array $params = [];
    private ?array $json = null;

    public function method(): string { return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'); }
    public function path(): string
    {
        $p = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $base = rtrim((string)Env::get('APP_BASE_PATH', ''), '/');
        if ($base !== '' && str_starts_with($p, $base)) { $p = substr($p, strlen($base)); }
        return '/' . trim($p, '/');
    }
    public function ip(): string { return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'; }
    public function ua(): string { return substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255); }
    public function isAjax(): bool
    {
        return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest'
            || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
            || str_starts_with($this->path(), '/api/');
    }
    public function json(): array
    {
        if ($this->json === null) {
            $raw = file_get_contents('php://input') ?: '';
            $d = json_decode($raw, true);
            $this->json = is_array($d) ? $d : [];
        }
        return $this->json;
    }
    /** مدخل من POST/JSON ثم GET. ملاحظة: school_id لا يُقرأ منه أبدًا (يُحذف). */
    public function input(string $k, mixed $d = null): mixed
    {
        $src = $_POST + $this->json() + $_GET;
        return $src[$k] ?? $d;
    }
    public function all(): array
    {
        $a = $_POST + $this->json() + $_GET;
        unset($a['school_id'], $a['_csrf']);
        return $a;
    }
    public function query(string $k, mixed $d = null): mixed { return $_GET[$k] ?? $d; }
    public function header(string $name): ?string { return $_SERVER['HTTP_' . strtoupper(str_replace('-', '_', $name))] ?? null; }
    public function csrfToken(): ?string { return $_POST['_csrf'] ?? $this->header('X-CSRF-Token') ?? ($this->json()['_csrf'] ?? null); }
}
