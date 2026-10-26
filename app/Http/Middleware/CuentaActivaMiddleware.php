<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\UsuarioRepository;

/**
 * Revoca la sesion si el usuario fue desactivado despues de haber iniciado
 * sesion (por ejemplo, un administrador lo desactiva mientras esta conectado).
 */
final class CuentaActivaMiddleware
{
    public function handle(Request $request, callable $next): Response
    {
        if (Auth::check()) {
            $usuario = (new UsuarioRepository())->buscarPorId((int) Auth::id());

            if ($usuario === null || (int) $usuario['estado'] !== 1) {
                Auth::logout();

                return Response::redirect('/login');
            }
        }

        return $next($request);
    }
}
