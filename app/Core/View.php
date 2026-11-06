<?php
declare(strict_types=1);
namespace App\Core;

final class View
{
    public static function render(string $view, array $data = [], ?string $layout = 'main'): string
    {
        $content = self::capture(base_path("app/Views/$view.php"), $data);
        if ($layout === null) { return $content; }
        return self::capture(base_path("app/Views/layouts/$layout.php"), $data + ['content' => $content]);
    }
    public static function partial(string $name, array $data = []): string { return self::capture(base_path("app/Views/partials/$name.php"), $data); }
    private static function capture(string $file, array $data): string
    {
        extract($data, EXTR_SKIP);
        ob_start();
        try { require $file; } catch (\Throwable $e) { ob_end_clean(); throw $e; }
        return (string)ob_get_clean();
    }
}
