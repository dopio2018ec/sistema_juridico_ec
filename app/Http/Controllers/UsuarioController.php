<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\RolRepository;
use App\Repositories\UsuarioRepository;
use App\Services\UsuarioService;
use App\Validators\Validator;
use InvalidArgumentException;

final class UsuarioController extends Controller
{
    private const POR_PAGINA = 15;

    public function index(Request $request): Response
    {
        $busqueda = trim((string) $request->query('q', ''));
        $estadoParam = $request->query('estado', '');
        $estado = $estadoParam === '' ? null : (int) $estadoParam;
        $pagina = max(1, (int) $request->query('pagina', 1));

        $repositorio = new UsuarioRepository();
        $resultado = $repositorio->listarPaginado($busqueda ?: null, $estado, $pagina, self::POR_PAGINA);

        return $this->view('usuarios/index', [
            'usuarios' => $resultado['datos'],
            'total' => $resultado['total'],
            'pagina' => $pagina,
            'porPagina' => self::POR_PAGINA,
            'busqueda' => $busqueda,
            'estado' => $estadoParam,
        ]);
    }

    public function crearFormulario(Request $request): Response
    {
        return $this->view('usuarios/crear', [
            'roles' => (new RolRepository())->listarActivos(),
            'errores' => [],
            'valores' => [],
        ]);
    }

    public function crear(Request $request): Response
    {
        $nombre = trim((string) $request->input('nombre', ''));
        $correo = trim((string) $request->input('correo', ''));
        $password = (string) $request->input('password', '');
        $rolIds = array_map('intval', (array) $request->input('roles', []));

        $servicio = new UsuarioService();
        $validador = $servicio->validarDatosBase(['nombre' => $nombre, 'correo' => $correo, 'password' => $password])
            ->required('password', 'La contrasena')
            ->minLength('password', 8, 'La contrasena');

        if ($rolIds === []) {
            $validador->custom('roles', fn () => false, 'Debe asignar al menos un rol.');
        }

        if ($validador->fails()) {
            return $this->view('usuarios/crear', [
                'roles' => (new RolRepository())->listarActivos(),
                'errores' => $validador->errors(),
                'valores' => ['nombre' => $nombre, 'correo' => $correo],
            ]);
        }

        try {
            $servicio->crear($nombre, $correo, $password, $rolIds);
        } catch (InvalidArgumentException $e) {
            return $this->redirectWithError('/usuarios/crear', $e->getMessage());
        }

        return $this->redirectWithSuccess('/usuarios', 'Usuario creado correctamente.');
    }

    public function editarFormulario(Request $request, array $params): Response
    {
        $id = (int) $params['id'];
        $repositorio = new UsuarioRepository();
        $usuario = $repositorio->buscarPorId($id);

        if ($usuario === null) {
            return $this->redirectWithError('/usuarios', 'El usuario solicitado no existe.');
        }

        $rolesAsignados = array_column($repositorio->rolesDeUsuario($id), 'id');

        return $this->view('usuarios/editar', [
            'usuario' => $usuario,
            'roles' => (new RolRepository())->listarActivos(),
            'rolesAsignados' => $rolesAsignados,
            'errores' => [],
        ]);
    }

    public function actualizar(Request $request, array $params): Response
    {
        $id = (int) $params['id'];
        $repositorio = new UsuarioRepository();
        $usuario = $repositorio->buscarPorId($id);

        if ($usuario === null) {
            return $this->redirectWithError('/usuarios', 'El usuario solicitado no existe.');
        }

        $nombre = trim((string) $request->input('nombre', ''));
        $correo = trim((string) $request->input('correo', ''));
        $rolIds = array_map('intval', (array) $request->input('roles', []));

        $servicio = new UsuarioService();
        $validador = $servicio->validarDatosBase(['nombre' => $nombre, 'correo' => $correo], $id);

        if ($rolIds === []) {
            $validador->custom('roles', fn () => false, 'Debe asignar al menos un rol.');
        }

        if ($validador->fails()) {
            return $this->view('usuarios/editar', [
                'usuario' => array_merge($usuario, ['nombre' => $nombre, 'correo' => $correo]),
                'roles' => (new RolRepository())->listarActivos(),
                'rolesAsignados' => $rolIds,
                'errores' => $validador->errors(),
            ]);
        }

        try {
            $servicio->actualizarDatos($id, $nombre, $correo, $rolIds);
        } catch (InvalidArgumentException $e) {
            return $this->redirectWithError("/usuarios/{$id}/editar", $e->getMessage());
        }

        return $this->redirectWithSuccess('/usuarios', 'Usuario actualizado correctamente.');
    }

    public function cambiarEstado(Request $request, array $params): Response
    {
        $id = (int) $params['id'];
        $activo = $request->input('activo') === '1';

        $repositorio = new UsuarioRepository();
        if ($repositorio->buscarPorId($id) === null) {
            return $this->redirectWithError('/usuarios', 'El usuario solicitado no existe.');
        }

        (new UsuarioService())->cambiarEstado($id, $activo);

        return $this->redirectWithSuccess('/usuarios', $activo ? 'Usuario activado.' : 'Usuario desactivado.');
    }

    public function restablecerPassword(Request $request, array $params): Response
    {
        $id = (int) $params['id'];
        $nueva = (string) $request->input('password', '');

        $validador = Validator::make(['password' => $nueva])
            ->required('password', 'La contrasena')
            ->minLength('password', 8, 'La contrasena');

        if ($validador->fails()) {
            return $this->redirectWithError("/usuarios/{$id}/editar", 'La nueva contrasena debe tener al menos 8 caracteres.');
        }

        $repositorio = new UsuarioRepository();
        if ($repositorio->buscarPorId($id) === null) {
            return $this->redirectWithError('/usuarios', 'El usuario solicitado no existe.');
        }

        (new UsuarioService())->cambiarPassword($id, $nueva);

        return $this->redirectWithSuccess('/usuarios', 'Contrasena restablecida correctamente.');
    }
}
