<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Services\AuthService;

final class AuthController extends Controller
{
    public function mostrarLogin(Request $request): Response
    {
        if (Auth::check()) {
            return $this->redirect('/');
        }

        return $this->view('auth/login', [], 'layouts/guest');
    }

    public function login(Request $request): Response
    {
        $correo = trim((string) $request->input('correo', ''));
        $password = (string) $request->input('password', '');

        if ($correo === '' || $password === '') {
            return $this->redirectWithError('/login', 'Debe ingresar correo y contrasena.');
        }

        $resultado = (new AuthService())->intentarLogin($correo, $password);

        if (!$resultado['exito']) {
            return $this->redirectWithError('/login', $resultado['mensaje']);
        }

        return $this->redirect('/');
    }

    public function logout(Request $request): Response
    {
        (new AuthService())->cerrarSesion();

        return $this->redirectWithSuccess('/login', 'Sesion cerrada correctamente.');
    }
}
