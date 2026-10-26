<?php

declare(strict_types=1);

namespace App\Core;

final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $secure = Env::bool('SESSION_SECURE_COOKIE', false);
        $lifetimeMinutes = Env::int('SESSION_LIFETIME_MINUTES', 20);

        session_name(Env::get('SESSION_NAME', 'sjec_session'));

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.gc_maxlifetime', (string) ($lifetimeMinutes * 60));

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();

        self::enforceExpiration();
    }

    private static function enforceExpiration(): void
    {
        $now = time();
        $inactivityLimit = Env::int('SESSION_LIFETIME_MINUTES', 20) * 60;
        $absoluteLimit = Env::int('SESSION_ABSOLUTE_LIFETIME_MINUTES', 480) * 60;

        $lastActivity = $_SESSION['_last_activity'] ?? $now;
        $startedAt = $_SESSION['_started_at'] ?? $now;

        if (($now - $lastActivity) > $inactivityLimit || ($now - $startedAt) > $absoluteLimit) {
            self::destroy();
            session_start();
            $_SESSION['_started_at'] = $now;
        }

        if (!isset($_SESSION['_started_at'])) {
            $_SESSION['_started_at'] = $now;
        }

        $_SESSION['_last_activity'] = $now;
    }

    public static function regenerate(): void
    {
        session_regenerate_id(true);
        $_SESSION['_started_at'] = time();
        $_SESSION['_last_activity'] = time();
    }

    public static function destroy(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $params['path'],
                'domain' => $params['domain'],
                'secure' => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'] ?? 'Lax',
            ]);
        }

        session_destroy();
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function flash(string $key, mixed $value): void
    {
        $_SESSION['_flash'][$key] = $value;
    }

    public static function getFlash(string $key, mixed $default = null): mixed
    {
        $value = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);

        return $value;
    }
}
