<?php

declare(strict_types=1);

namespace App\Core;

final class Logger
{
    public static function log(string $level, string $message, array $context = []): void
    {
        $path = Env::get('LOG_PATH', 'storage/logs/app.log');
        $fullPath = self::resolvePath($path);

        $dir = dirname($fullPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $safeContext = self::stripSensitive($context);
        $line = sprintf(
            '[%s] %s: %s %s%s',
            date('Y-m-d H:i:s'),
            strtoupper($level),
            $message,
            $safeContext === [] ? '' : json_encode($safeContext, JSON_UNESCAPED_UNICODE),
            PHP_EOL
        );

        error_log($line, 3, $fullPath);
    }

    public static function info(string $message, array $context = []): void
    {
        self::log('info', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::log('warning', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::log('error', $message, $context);
    }

    private static function resolvePath(string $path): string
    {
        if (str_starts_with($path, '/') || preg_match('/^[A-Za-z]:\\\\/', $path)) {
            return $path;
        }

        return rtrim(BASE_PATH, '/\\') . DIRECTORY_SEPARATOR . $path;
    }

    private static function stripSensitive(array $context): array
    {
        $blocked = ['password', 'password_hash', 'csrf_token', 'token', 'secret', 'authorization'];

        foreach ($context as $key => $value) {
            if (in_array(strtolower((string) $key), $blocked, true)) {
                $context[$key] = '[redactado]';
            } elseif (is_array($value)) {
                $context[$key] = self::stripSensitive($value);
            }
        }

        return $context;
    }
}
