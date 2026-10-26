<?php

declare(strict_types=1);

namespace App\Core;

final class Auth
{
    public static function login(array $usuario, array $roles): void
    {
        Session::regenerate();
        Session::set('usuario_id', (int) $usuario['id']);
        Session::set('usuario_nombre', $usuario['nombre']);
        Session::set('usuario_correo', $usuario['correo']);
        Session::set('usuario_roles', $roles);
        Csrf::rotate();
    }

    public static function logout(): void
    {
        Session::destroy();
    }

    public static function check(): bool
    {
        return Session::get('usuario_id') !== null;
    }

    public static function id(): ?int
    {
        $id = Session::get('usuario_id');

        return $id === null ? null : (int) $id;
    }

    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }

        return [
            'id' => Session::get('usuario_id'),
            'nombre' => Session::get('usuario_nombre'),
            'correo' => Session::get('usuario_correo'),
            'roles' => Session::get('usuario_roles', []),
        ];
    }

    public static function hasRole(string $role): bool
    {
        $roles = Session::get('usuario_roles', []);

        return in_array($role, $roles, true);
    }

    public static function hasAnyRole(array $roles): bool
    {
        foreach ($roles as $role) {
            if (self::hasRole($role)) {
                return true;
            }
        }

        return false;
    }
}
