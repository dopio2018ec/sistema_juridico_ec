<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\NormaRepository;
use App\Services\NormaService;
use RuntimeException;

final class PublicacionController extends Controller
{
    public function crear(Request $request, array $params): Response
    {
        $normaId = (int) $params['norma_id'];
        if ((new NormaRepository())->buscarPorId($normaId) === null) {
            return $this->redirectWithError('/normas', 'La norma solicitada no existe.');
        }

        $datos = [
            'nombre_publicacion' => trim((string) $request->input('nombre_publicacion', 'Registro Oficial')),
            'numero' => trim((string) $request->input('numero', '')),
            'suplemento' => trim((string) $request->input('suplemento', '')),
            'fecha_publicacion' => trim((string) $request->input('fecha_publicacion', '')),
            'pagina_inicial' => trim((string) $request->input('pagina_inicial', '')),
            'pagina_final' => trim((string) $request->input('pagina_final', '')),
            'url_fuente' => trim((string) $request->input('url_fuente', '')),
            'observaciones' => trim((string) $request->input('observaciones', '')),
        ];

        try {
            (new NormaService())->agregarPublicacion($normaId, $datos);
        } catch (RuntimeException $e) {
            return $this->redirectWithError("/normas/{$normaId}", $e->getMessage());
        }

        return $this->redirectWithSuccess("/normas/{$normaId}", 'Publicacion oficial registrada.');
    }
}
