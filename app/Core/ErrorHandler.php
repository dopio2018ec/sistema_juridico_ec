<?php

declare(strict_types=1);

namespace App\Core;

use Throwable;

final class ErrorHandler
{
    public static function register(): void
    {
        set_exception_handler([self::class, 'handleException']);
        set_error_handler([self::class, 'handleError']);
    }

    public static function handleError(int $severity, string $message, string $file, int $line): bool
    {
        if (!(error_reporting() & $severity)) {
            return false;
        }

        self::handleException(new \ErrorException($message, 0, $severity, $file, $line));

        return true;
    }

    public static function handleException(Throwable $exception): void
    {
        Logger::error($exception->getMessage(), [
            'exception' => get_class($exception),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ]);

        $debug = Env::bool('APP_DEBUG', false);

        http_response_code(500);
        header('Content-Type: text/html; charset=utf-8');

        if ($debug) {
            echo '<h1>Error interno</h1><pre>' . htmlspecialchars(
                $exception->getMessage() . "\n" . $exception->getTraceAsString(),
                ENT_QUOTES,
                'UTF-8'
            ) . '</pre>';

            return;
        }

        try {
            echo View::render('errores/500', [], 'layouts/guest');
        } catch (Throwable) {
            echo 'Ocurrio un error interno. Intente nuevamente mas tarde.';
        }
    }
}
