<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;

final class CsrfMiddleware
{
    private const METODOS_PROTEGIDOS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    public function handle(Request $request, callable $next): Response
    {
        if (in_array($request->method(), self::METODOS_PROTEGIDOS, true)) {
            $token = $request->input('csrf_token');

            if (!Csrf::verify(is_string($token) ? $token : null)) {
                return Response::html('Token CSRF invalido o ausente.', 419);
            }
        }

        return $next($request);
    }
}
