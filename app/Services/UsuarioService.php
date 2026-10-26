<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Repositories\RolRepository;
use App\Repositories\UsuarioRepository;
use App\Validators\Validator;
use InvalidArgumentException;

final class UsuarioService
{
    private UsuarioRepository $usuarios;
    private RolRepository $roles;
    private AuditoriaService $auditoria;

    public function __construct(
        ?UsuarioRepository $usuarios = null,
        ?RolRepository $roles = null,
        ?AuditoriaService $auditoria = null
    ) {
        $this->usuarios = $usuarios ?? new UsuarioRepository();
        $this->roles = $roles ?? new RolRepository();
        $this->auditoria = $auditoria ?? new AuditoriaService();
    }

    public function validarDatosBase(array $datos, ?int $idExcluido = null): Validator
    {
        $validador = Validator::make($datos)
            ->required('nombre', 'El nombre')
            ->maxLength('nombre', 150, 'El nombre')
            ->required('correo', 'El correo')
            ->email('correo', 'El correo')
            ->maxLength('correo', 190, 'El correo');

        $correo = trim((string) ($datos['correo'] ?? ''));
        if ($correo !== '' && $this->usuarios->correoExiste($correo, $idExcluido)) {
            $validador->custom('correo', fn () => false, 'El correo ya esta registrado.');
        }

        return $validador;
    }

    /**
     * @throws InvalidArgumentException si los roles no existen
     */
    public function crear(string $nombre, string $correo, string $password, array $rolIds): int
    {
        if (!$this->roles->existenTodos($rolIds)) {
            throw new InvalidArgumentException('Uno o mas roles seleccionados no existen.');
        }

        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $id = $this->usuarios->crear($nombre, $correo, $hash);
            $this->usuarios->sincronizarRoles($id, $rolIds);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        $this->auditoria->registrar('usuarios', $id, 'INSERT', ['nombre' => $nombre, 'correo' => $correo]);

        return $id;
    }

    public function actualizarDatos(int $id, string $nombre, string $correo, array $rolIds): void
    {
        if (!$this->roles->existenTodos($rolIds)) {
            throw new InvalidArgumentException('Uno o mas roles seleccionados no existen.');
        }

        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            $this->usuarios->actualizarDatos($id, $nombre, $correo);
            $this->usuarios->sincronizarRoles($id, $rolIds);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        $this->auditoria->registrar('usuarios', $id, 'UPDATE', ['nombre' => $nombre, 'correo' => $correo]);
    }

    public function cambiarPassword(int $id, string $nuevaPassword): void
    {
        $hash = password_hash($nuevaPassword, PASSWORD_DEFAULT);
        $this->usuarios->actualizarPassword($id, $hash);
        $this->auditoria->registrar('usuarios', $id, 'UPDATE', ['evento' => 'cambio_password']);
    }

    public function cambiarEstado(int $id, bool $activo): void
    {
        $this->usuarios->cambiarEstado($id, $activo);
        $this->auditoria->registrar('usuarios', $id, 'UPDATE', [
            'evento' => $activo ? 'activacion' : 'desactivacion',
        ]);
    }
}
