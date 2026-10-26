<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

final class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $pdo = Database::connection();

        $totalNormas = (int) $pdo->query('SELECT COUNT(*) FROM normas')->fetchColumn();
        $totalArticulos = (int) $pdo->query('SELECT COUNT(*) FROM articulos WHERE estado = 1')->fetchColumn();

        $normasPorTipo = $pdo->query(
            'SELECT tn.nombre, COUNT(n.id) AS total
             FROM tipos_norma tn
             LEFT JOIN normas n ON n.tipo_norma_id = tn.id
             GROUP BY tn.id, tn.nombre
             ORDER BY tn.nombre'
        )->fetchAll();

        $normasPorEstado = $pdo->query(
            'SELECT estado, COUNT(*) AS total FROM normas GROUP BY estado'
        )->fetchAll();

        $ultimasNormas = $pdo->query(
            'SELECT n.id, n.titulo, n.fecha_creacion
             FROM normas n ORDER BY n.fecha_creacion DESC LIMIT 5'
        )->fetchAll();

        $ultimosArticulos = $pdo->query(
            'SELECT a.id, a.articulo_numero, a.norma_id, n.titulo AS norma_titulo, a.fecha_actualizacion
             FROM articulos a
             JOIN normas n ON n.id = a.norma_id
             ORDER BY a.fecha_actualizacion DESC LIMIT 5'
        )->fetchAll();

        return $this->view('dashboard/index', [
            'totalNormas' => $totalNormas,
            'totalArticulos' => $totalArticulos,
            'normasPorTipo' => $normasPorTipo,
            'normasPorEstado' => $normasPorEstado,
            'ultimasNormas' => $ultimasNormas,
            'ultimosArticulos' => $ultimosArticulos,
        ]);
    }
}
