<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Env;
use App\Repositories\UsuarioRepository;

final class AuthService
{
    private UsuarioRepository $usuarios;
    private AuditoriaService $auditoria;

    public function __construct(?UsuarioRepository $usuarios = null, ?AuditoriaService $auditoria = null)
    {
        $this->usuarios = $usuarios ?? new UsuarioRepository();
        $this->auditoria = $auditoria ?? new AuditoriaService();
    }

    /**
     * @return array{exito: bool, mensaje: string}
     */
    public function intentarLogin(string $correo, string $password): array
    {
        $usuario = $this->usuarios->buscarPorCorreo($correo);

        if ($usuario === null) {
            // No revelar si el correo existe o no.
            return ['exito' => false, 'mensaje' => 'Credenciales invalidas.'];
        }

        if ($this->estaBloqueado($usuario)) {
            return ['exito' => false, 'mensaje' => 'Cuenta bloqueada temporalmente por intentos fallidos. Intente mas tarde.'];
        }

        if ((int) $usuario['estado'] !== 1) {
            return ['exito' => false, 'mensaje' => 'La cuenta esta desactivada.'];
        }

        if (!password_verify($password, $usuario['password_hash'])) {
            $this->registrarIntentoFallido($usuario);

            return ['exito' => false, 'mensaje' => 'Credenciales invalidas.'];
        }

        if (password_needs_rehash($usuario['password_hash'], PASSWORD_DEFAULT)) {
            $this->usuarios->actualizarPassword((int) $usuario['id'], password_hash($password, PASSWORD_DEFAULT));
        }

        $this->usuarios->registrarAccesoExitoso((int) $usuario['id']);
        $roles = array_column($this->usuarios->rolesDeUsuario((int) $usuario['id']), 'nombre');

        Auth::login($usuario, $roles);
        $this->auditoria->registrar('usuarios', (int) $usuario['id'], 'UPDATE', ['evento' => 'login_exitoso']);

        return ['exito' => true, 'mensaje' => 'Bienvenido.'];
    }

    public function cerrarSesion(): void
    {
        $id = Auth::id();
        Auth::logout();
        if ($id !== null) {
            $this->auditoria->registrar('usuarios', $id, 'UPDATE', ['evento' => 'logout']);
        }
    }

    private function estaBloqueado(array $usuario): bool
    {
        if (empty($usuario['bloqueado_hasta'])) {
            return false;
        }

        return strtotime($usuario['bloqueado_hasta']) > time();
    }

    private function registrarIntentoFallido(array $usuario): void
    {
        $maxIntentos = Env::int('LOGIN_MAX_INTENTOS', 5);
        $minutosBloqueo = Env::int('LOGIN_BLOQUEO_MINUTOS', 15);

        $intentos = ((int) $usuario['intentos_fallidos']) + 1;
        $bloqueadoHasta = null;

        if ($intentos >= $maxIntentos) {
            $bloqueadoHasta = date('Y-m-d H:i:s', time() + ($minutosBloqueo * 60));
            $intentos = 0;
        }

        $this->usuarios->registrarIntentoFallido((int) $usuario['id'], $intentos, $bloqueadoHasta);
    }
}
