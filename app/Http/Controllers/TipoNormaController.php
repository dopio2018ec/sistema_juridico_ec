<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\TipoNormaRepository;
use App\Services\TipoNormaService;
use RuntimeException;

final class TipoNormaController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->view('tipos_norma/index', [
            'tipos' => (new TipoNormaRepository())->listarTodos(),
        ]);
    }

    public function crearFormulario(Request $request): Response
    {
        return $this->view('tipos_norma/crear', ['errores' => [], 'valores' => []]);
    }

    public function crear(Request $request): Response
    {
        $datos = [
            'codigo' => trim((string) $request->input('codigo', '')),
            'nombre' => trim((string) $request->input('nombre', '')),
            'descripcion' => trim((string) $request->input('descripcion', '')),
        ];

        $servicio = new TipoNormaService();
        $validador = $servicio->validar($datos);

        if ($validador->fails()) {
            return $this->view('tipos_norma/crear', ['errores' => $validador->errors(), 'valores' => $datos]);
        }

        $servicio->crear($datos['codigo'], $datos['nombre'], $datos['descripcion'] ?: null);

        return $this->redirectWithSuccess('/tipos-norma', 'Tipo de norma creado correctamente.');
    }

    public function editarFormulario(Request $request, array $params): Response
    {
        $tipo = (new TipoNormaRepository())->buscarPorId((int) $params['id']);
        if ($tipo === null) {
            return $this->redirectWithError('/tipos-norma', 'El tipo de norma no existe.');
        }

        return $this->view('tipos_norma/editar', ['tipo' => $tipo, 'errores' => []]);
    }

    public function actualizar(Request $request, array $params): Response
    {
        $id = (int) $params['id'];
        $repositorio = new TipoNormaRepository();
        $tipo = $repositorio->buscarPorId($id);
        if ($tipo === null) {
            return $this->redirectWithError('/tipos-norma', 'El tipo de norma no existe.');
        }

        $nombre = trim((string) $request->input('nombre', ''));
        $descripcion = trim((string) $request->input('descripcion', ''));

        $servicio = new TipoNormaService();
        $validador = $servicio->validar(['codigo' => $tipo['codigo'], 'nombre' => $nombre, 'descripcion' => $descripcion], $id);

        if ($validador->fails()) {
            return $this->view('tipos_norma/editar', [
                'tipo' => array_merge($tipo, ['nombre' => $nombre, 'descripcion' => $descripcion]),
                'errores' => $validador->errors(),
            ]);
        }

        $servicio->actualizar($id, $nombre, $descripcion ?: null);

        return $this->redirectWithSuccess('/tipos-norma', 'Tipo de norma actualizado.');
    }

    public function cambiarEstado(Request $request, array $params): Response
    {
        $id = (int) $params['id'];
        $activo = $request->input('activo') === '1';

        try {
            (new TipoNormaService())->cambiarEstado($id, $activo);
        } catch (RuntimeException $e) {
            return $this->redirectWithError('/tipos-norma', $e->getMessage());
        }

        return $this->redirectWithSuccess('/tipos-norma', $activo ? 'Tipo activado.' : 'Tipo desactivado.');
    }
}
