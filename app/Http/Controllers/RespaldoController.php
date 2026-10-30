<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Services\BackupService;
use Throwable;

final class RespaldoController extends Controller
{
    public function generar(Request $request): Response
    {
        try {
            $sql = (new BackupService())->generarVolcado();
        } catch (Throwable $e) {
            return $this->redirectWithError('/', 'No fue posible generar el respaldo: ' . $e->getMessage());
        }

        $nombreArchivo = 'respaldo_sistema_juridico_ec_' . date('Ymd_His') . '.sql';

        return Response::archivoDescargable($sql, $nombreArchivo, 'application/sql; charset=utf-8');
    }
}
