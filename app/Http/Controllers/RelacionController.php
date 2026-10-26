<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\NormaRepository;
use App\Services\RelacionService;

final class RelacionController extends Controller
{
    public function crear(Request $request, array $params): Response
    {
        $normaOrigenId = (int) $params['norma_id'];
        $normaRepositorio = new NormaRepository();
        if ($normaRepositorio->buscarPorId($normaOrigenId) === null) {
            return $this->redirectWithError('/normas', 'La norma de origen no existe.');
        }

        $datos = [
            'norma_origen_id' => $normaOrigenId,
            'norma_destino_id' => (int) $request->input('norma_destino_id', 0),
            'tipo_relacion' => $request->input('tipo_relacion', ''),
            'articulo_origen_id' => $request->input('articulo_origen_id', ''),
            'articulo_destino_id' => $request->input('articulo_destino_id', ''),
            'fecha_efecto' => trim((string) $request->input('fecha_efecto', '')),
            'descripcion' => trim((string) $request->input('descripcion', '')),
        ];

        $servicio = new RelacionService();
        $validador = $servicio->validar($datos);

        if ($normaRepositorio->buscarPorId($datos['norma_destino_id']) === null) {
            return $this->redirectWithError("/normas/{$normaOrigenId}", 'La norma afectada seleccionada no existe.');
        }

        if ($validador->fails()) {
            return $this->redirectWithError("/normas/{$normaOrigenId}", implode(' ', $validador->firstErrorsFlat()));
        }

        $servicio->crear($datos);

        return $this->redirectWithSuccess(
            "/normas/{$normaOrigenId}",
            'Relacion normativa registrada. Verifique el alcance antes de asumir derogacion.'
        );
    }
}
