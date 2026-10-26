<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\ArticuloRepository;
use App\Repositories\NormaRepository;
use App\Repositories\PublicacionRepository;
use App\Repositories\RelacionRepository;
use App\Repositories\TipoNormaRepository;
use App\Services\NormaService;
use App\Services\VigenciaService;
use Throwable;

final class NormaController extends Controller
{
    private const POR_PAGINA = 15;

    public function index(Request $request): Response
    {
        $filtros = [
            'texto' => trim((string) $request->query('q', '')),
            'tipo_norma_id' => $request->query('tipo_norma_id', ''),
            'estado' => $request->query('estado', ''),
        ];
        $pagina = max(1, (int) $request->query('pagina', 1));

        $resultado = (new NormaRepository())->buscarPaginado($filtros, 'fecha_publicacion', 'DESC', $pagina, self::POR_PAGINA);

        return $this->view('normas/index', [
            'normas' => $resultado['datos'],
            'total' => $resultado['total'],
            'pagina' => $pagina,
            'porPagina' => self::POR_PAGINA,
            'filtros' => $filtros,
            'tipos' => (new TipoNormaRepository())->listarActivos(),
        ]);
    }

    public function crearFormulario(Request $request): Response
    {
        return $this->view('normas/crear', [
            'tipos' => (new TipoNormaRepository())->listarActivos(),
            'errores' => [],
            'valores' => [],
        ]);
    }

    public function crear(Request $request): Response
    {
        $datos = $this->datosNormaDesdeRequest($request);

        $servicio = new NormaService();
        $validador = $servicio->validar($datos);

        if ($validador->fails()) {
            return $this->view('normas/crear', [
                'tipos' => (new TipoNormaRepository())->listarActivos(),
                'errores' => $validador->errors(),
                'valores' => $datos,
            ]);
        }

        $datosPublicacion = null;
        if (trim((string) $request->input('publicacion_numero', '')) !== ''
            || trim((string) $request->input('publicacion_url', '')) !== '') {
            $datosPublicacion = [
                'nombre_publicacion' => trim((string) $request->input('publicacion_nombre', 'Registro Oficial')),
                'numero' => trim((string) $request->input('publicacion_numero', '')),
                'suplemento' => trim((string) $request->input('publicacion_suplemento', '')),
                'fecha_publicacion' => trim((string) $request->input('publicacion_fecha', '')),
                'pagina_inicial' => trim((string) $request->input('publicacion_pagina_inicial', '')),
                'pagina_final' => trim((string) $request->input('publicacion_pagina_final', '')),
                'url_fuente' => trim((string) $request->input('publicacion_url', '')),
                'observaciones' => null,
            ];
        }

        try {
            $id = $servicio->crearConPublicacionYArticulos($datos, (int) Auth::id(), $datosPublicacion, []);
        } catch (Throwable $e) {
            return $this->redirectWithError('/normas/crear', 'No fue posible registrar la norma: ' . $e->getMessage());
        }

        return $this->redirectWithSuccess("/normas/{$id}", 'Norma registrada correctamente.');
    }

    public function mostrar(Request $request, array $params): Response
    {
        $id = (int) $params['id'];
        $norma = (new NormaRepository())->buscarPorId($id);

        if ($norma === null) {
            return $this->redirectWithError('/normas', 'La norma solicitada no existe.');
        }

        $vigencia = (new VigenciaService())->evaluar($norma);

        return $this->view('normas/mostrar', [
            'norma' => $norma,
            'vigencia' => $vigencia,
            'publicaciones' => (new PublicacionRepository())->listarPorNorma($id),
            'articulos' => (new ArticuloRepository())->listarPorNorma($id),
            'relacionesOrigen' => (new RelacionRepository())->listarPorNormaOrigen($id),
            'relacionesDestino' => (new RelacionRepository())->listarPorNormaDestino($id),
            'normasParaRelacionar' => $this->normasDistintasDe($id),
        ]);
    }

    public function editarFormulario(Request $request, array $params): Response
    {
        $norma = (new NormaRepository())->buscarPorId((int) $params['id']);
        if ($norma === null) {
            return $this->redirectWithError('/normas', 'La norma solicitada no existe.');
        }

        return $this->view('normas/editar', [
            'norma' => $norma,
            'tipos' => (new TipoNormaRepository())->listarActivos(),
            'errores' => [],
        ]);
    }

    public function actualizar(Request $request, array $params): Response
    {
        $id = (int) $params['id'];
        $repositorio = new NormaRepository();
        $normaActual = $repositorio->buscarPorId($id);
        if ($normaActual === null) {
            return $this->redirectWithError('/normas', 'La norma solicitada no existe.');
        }

        $datos = $this->datosNormaDesdeRequest($request);

        $servicio = new NormaService();
        $validador = $servicio->validar($datos, $id);

        if ($validador->fails()) {
            return $this->view('normas/editar', [
                'norma' => array_merge($normaActual, $datos),
                'tipos' => (new TipoNormaRepository())->listarActivos(),
                'errores' => $validador->errors(),
            ]);
        }

        $servicio->actualizar($id, $datos);

        return $this->redirectWithSuccess("/normas/{$id}", 'Norma actualizada correctamente.');
    }

    public function marcarRevisada(Request $request, array $params): Response
    {
        $id = (int) $params['id'];
        if ((new NormaRepository())->buscarPorId($id) === null) {
            return $this->redirectWithError('/normas', 'La norma solicitada no existe.');
        }

        (new NormaService())->marcarRevisada($id, (int) Auth::id());

        return $this->redirectWithSuccess("/normas/{$id}", 'Norma marcada como revisada por un REVISOR.');
    }

    private function datosNormaDesdeRequest(Request $request): array
    {
        return [
            'tipo_norma_id' => $request->input('tipo_norma_id', ''),
            'titulo' => trim((string) $request->input('titulo', '')),
            'numero_norma' => trim((string) $request->input('numero_norma', '')),
            'siglas' => trim((string) $request->input('siglas', '')),
            'entidad_emisora' => trim((string) $request->input('entidad_emisora', '')),
            'fecha_emision' => trim((string) $request->input('fecha_emision', '')),
            'fecha_publicacion' => trim((string) $request->input('fecha_publicacion', '')),
            'fecha_vigencia' => trim((string) $request->input('fecha_vigencia', '')),
            'fecha_derogatoria' => trim((string) $request->input('fecha_derogatoria', '')),
            'estado' => $request->input('estado', 'BORRADOR'),
            'observaciones' => trim((string) $request->input('observaciones', '')),
        ];
    }

    private function normasDistintasDe(int $id): array
    {
        $resultado = (new NormaRepository())->buscarPaginado([], 'titulo', 'ASC', 1, 200);

        return array_values(array_filter($resultado['datos'], fn ($n) => (int) $n['id'] !== $id));
    }
}
