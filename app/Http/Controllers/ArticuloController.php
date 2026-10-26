<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\ArticuloRepository;
use App\Repositories\NormaRepository;
use App\Services\ArticuloService;

final class ArticuloController extends Controller
{
    public function crear(Request $request, array $params): Response
    {
        $normaId = (int) $params['norma_id'];
        if ((new NormaRepository())->buscarPorId($normaId) === null) {
            return $this->redirectWithError('/normas', 'La norma solicitada no existe.');
        }

        $datos = $this->datosDesdeRequest($request);

        $servicio = new ArticuloService();
        $validador = $servicio->validar($datos, $normaId);

        if ($validador->fails()) {
            return $this->redirectWithError("/normas/{$normaId}", implode(' ', $validador->firstErrorsFlat()));
        }

        $servicio->crear($normaId, $datos, (int) Auth::id());

        return $this->redirectWithSuccess("/normas/{$normaId}", 'Articulo/disposicion registrado correctamente.');
    }

    public function editarFormulario(Request $request, array $params): Response
    {
        $articulo = (new ArticuloRepository())->buscarPorId((int) $params['id']);
        if ($articulo === null) {
            return $this->redirectWithError('/normas', 'El articulo solicitado no existe.');
        }

        return $this->view('articulos/editar', ['articulo' => $articulo, 'errores' => []]);
    }

    public function actualizar(Request $request, array $params): Response
    {
        $id = (int) $params['id'];
        $repositorio = new ArticuloRepository();
        $articuloActual = $repositorio->buscarPorId($id);
        if ($articuloActual === null) {
            return $this->redirectWithError('/normas', 'El articulo solicitado no existe.');
        }

        $datos = $this->datosDesdeRequest($request);

        $servicio = new ArticuloService();
        $validador = $servicio->validar($datos, (int) $articuloActual['norma_id'], $id);

        if ($validador->fails()) {
            return $this->view('articulos/editar', [
                'articulo' => array_merge($articuloActual, $datos),
                'errores' => $validador->errors(),
            ]);
        }

        $servicio->actualizar($id, $datos, (int) Auth::id());

        return $this->redirectWithSuccess("/normas/{$articuloActual['norma_id']}", 'Texto vigente actualizado. El texto anterior ya no esta disponible.');
    }

    public function cambiarEstado(Request $request, array $params): Response
    {
        $id = (int) $params['id'];
        $repositorio = new ArticuloRepository();
        $articulo = $repositorio->buscarPorId($id);
        if ($articulo === null) {
            return $this->redirectWithError('/normas', 'El articulo solicitado no existe.');
        }

        $activo = $request->input('activo') === '1';
        (new ArticuloService())->cambiarEstado($id, $activo, (int) Auth::id());

        return $this->redirectWithSuccess("/normas/{$articulo['norma_id']}", $activo ? 'Articulo activado.' : 'Articulo desactivado.');
    }

    private function datosDesdeRequest(Request $request): array
    {
        return [
            'articulo_padre_id' => $request->input('articulo_padre_id', ''),
            'articulo_numero' => trim((string) $request->input('articulo_numero', '')),
            'titulo' => trim((string) $request->input('titulo', '')),
            'tipo_contenido' => $request->input('tipo_contenido', 'ARTICULO'),
            'contenido' => (string) $request->input('contenido', ''),
            'orden' => (int) $request->input('orden', 0),
        ];
    }
}
