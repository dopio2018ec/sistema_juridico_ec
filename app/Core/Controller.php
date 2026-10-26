<?php

declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    protected function view(string $template, array $data = [], ?string $layout = 'layouts/app'): Response
    {
        $data['csrf_field'] = Csrf::field();
        $data['usuario_actual'] = Auth::user();
        $data['flash_exito'] = Session::getFlash('exito');
        $data['flash_error'] = Session::getFlash('error');

        return Response::html(View::render($template, $data, $layout));
    }

    protected function redirect(string $to): Response
    {
        return Response::redirect($to);
    }

    protected function redirectWithSuccess(string $to, string $message): Response
    {
        Session::flash('exito', $message);

        return Response::redirect($to);
    }

    protected function redirectWithError(string $to, string $message): Response
    {
        Session::flash('error', $message);

        return Response::redirect($to);
    }

    protected function json(array $data, int $status = 200): Response
    {
        return Response::json($data, $status);
    }
}
