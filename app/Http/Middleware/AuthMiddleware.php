<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

final class AuthMiddleware
{
    public function handle(Request $request, callable $next): Response
    {
        if (!Auth::check()) {
            Session::flash('error', 'Debe iniciar sesion para continuar.');

            return Response::redirect('/login');
        }

        return $next($request);
    }
}
