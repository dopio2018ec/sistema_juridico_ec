<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\TipoNormaRepository;
use App\Services\BusquedaService;

final class BusquedaController extends Controller
{
    private const POR_PAGINA = 15;

    public function index(Request $request): Response
    {
        $filtros = [
            'texto' => trim((string) $request->query('texto', '')),
            'titulo_norma' => trim((string) $request->query('titulo_norma', '')),
            'tipo_norma_id' => $request->query('tipo_norma_id', ''),
            'numero_norma' => trim((string) $request->query('numero_norma', '')),
            'articulo_numero' => trim((string) $request->query('articulo_numero', '')),
            'entidad_emisora' => trim((string) $request->query('entidad_emisora', '')),
            'fecha_publicacion' => trim((string) $request->query('fecha_publicacion', '')),
            'estado' => $request->query('estado', ''),
        ];

        $hayFiltros = array_filter($filtros) !== [];
        $pagina = max(1, (int) $request->query('pagina', 1));

        $resultado = ['total' => 0, 'datos' => []];
        if ($hayFiltros) {
            $resultado = (new BusquedaService())->buscar($filtros, 'relevancia', $pagina, self::POR_PAGINA);
        }

        return $this->view('busqueda/index', [
            'resultados' => $resultado['datos'],
            'total' => $resultado['total'],
            'pagina' => $pagina,
            'porPagina' => self::POR_PAGINA,
            'filtros' => $filtros,
            'tipos' => (new TipoNormaRepository())->listarActivos(),
            'hayFiltros' => $hayFiltros,
        ]);
    }
}
