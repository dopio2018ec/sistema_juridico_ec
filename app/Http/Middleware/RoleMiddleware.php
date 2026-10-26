<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;

/**
 * Middleware base de autorizacion por rol. Las rutas que requieren un
 * conjunto especifico de roles deben usar una subclase generada por
 * RoleMiddleware::for(['ADMINISTRADOR']) o un middleware concreto.
 *
 * La ocultacion de botones en la vista NO sustituye esta verificacion:
 * toda accion sensible pasa por este middleware o por una Policy explicita
 * dentro del controlador.
 */
class RoleMiddleware
{
    protected array $rolesPermitidos = [];

    public function handle(Request $request, callable $next): Response
    {
        if (!Auth::check()) {
            return Response::redirect('/login');
        }

        if ($this->rolesPermitidos !== [] && !Auth::hasAnyRole($this->rolesPermitidos)) {
            return Response::html(View::render('errores/403', [], 'layouts/app'), 403);
        }

        return $next($request);
    }

    public static function for(array $roles): callable
    {
        return function () use ($roles) {
            $instance = new self();
            $instance->rolesPermitidos = $roles;

            return $instance;
        };
    }
}
