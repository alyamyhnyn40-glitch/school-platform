<?php
declare(strict_types=1);
namespace App\Core;

final class Router
{
    private array $routes = [];
    private array $globalMw = [];

    public function use(string $mw): void { $this->globalMw[] = $mw; }
    public function add(string $method, string $pattern, array $handler, array $mw = []): void
    {
        $regex = '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $pattern) . '$#';
        $this->routes[] = [$method, $regex, $handler, $mw];
    }
    public function get(string $p, array $h, array $mw = []): void { $this->add('GET', $p, $h, $mw); }
    public function post(string $p, array $h, array $mw = []): void { $this->add('POST', $p, $h, $mw); }
    public function put(string $p, array $h, array $mw = []): void { $this->add('PUT', $p, $h, $mw); }
    public function delete(string $p, array $h, array $mw = []): void { $this->add('DELETE', $p, $h, $mw); }

    public function dispatch(Request $req): Response
    {
        $method = $req->method();
        if ($method === 'POST' && isset($_POST['_method'])) { $method = strtoupper($_POST['_method']); }
        $path = $req->path();
        $allowed = false;
        foreach ($this->routes as [$m, $regex, $handler, $mw]) {
            if (!preg_match($regex, $path, $mt)) { continue; }
            if ($m !== $method) { $allowed = true; continue; }
            $req->params = array_filter($mt, 'is_string', ARRAY_FILTER_USE_KEY);
            $core = function (Request $r) use ($handler) {
                [$class, $fn] = $handler;
                $res = (new $class())->$fn($r);
                return $res instanceof Response ? $res : Response::html((string)$res);
            };
            $stack = array_merge($this->globalMw, $mw);
            $next = array_reduce(array_reverse($stack), function ($next, $mwSpec) {
                return function (Request $r) use ($next, $mwSpec) {
                    [$cls, $arg] = array_pad(explode(':', $mwSpec, 2), 2, null);
                    return (new $cls())->handle($r, $next, $arg);
                };
            }, $core);
            return $next($req);
        }
        throw new HttpException($allowed ? 405 : 404, $allowed ? 'الطريقة غير مسموحة' : 'الصفحة غير موجودة');
    }
}
