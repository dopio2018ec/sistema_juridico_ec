<?php

declare(strict_types=1);

namespace App\Core;

final class View
{
    private static string $basePath;

    public static function setBasePath(string $path): void
    {
        self::$basePath = rtrim($path, '/\\');
    }

    public static function render(string $template, array $data = [], ?string $layout = 'layouts/app'): string
    {
        $content = self::renderTemplate($template, $data);

        if ($layout === null) {
            return $content;
        }

        $data['content'] = $content;

        return self::renderTemplate($layout, $data);
    }

    private static function renderTemplate(string $template, array $data): string
    {
        $file = self::$basePath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $template) . '.php';

        if (!is_file($file)) {
            throw new \RuntimeException("La vista '{$template}' no existe.");
        }

        extract($data, EXTR_SKIP);

        ob_start();
        include $file;

        return (string) ob_get_clean();
    }

    public static function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}
