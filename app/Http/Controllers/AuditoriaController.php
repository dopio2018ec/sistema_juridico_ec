<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Repositories\AuditoriaRepository;

final class AuditoriaController extends Controller
{
    private const POR_PAGINA = 20;

    public function index(Request $request): Response
    {
        $pagina = max(1, (int) $request->query('pagina', 1));
        $resultado = (new AuditoriaRepository())->listarPaginado($pagina, self::POR_PAGINA);

        return $this->view('dashboard/auditoria', [
            'eventos' => $resultado['datos'],
            'total' => $resultado['total'],
            'pagina' => $pagina,
            'porPagina' => self::POR_PAGINA,
        ]);
    }
}
