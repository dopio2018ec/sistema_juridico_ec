<?php

declare(strict_types=1);

namespace App\Core;

final class Url
{
    private static string $basePath = '';

    public static function setBasePath(string $path): void
    {
        self::$basePath = rtrim($path, '/');
    }

    public static function basePath(): string
    {
        return self::$basePath;
    }

    public static function to(string $path): string
    {
        if (!str_starts_with($path, '/')) {
            return $path;
        }

        return self::$basePath . $path;
    }
}
