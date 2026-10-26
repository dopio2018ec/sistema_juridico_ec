<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

final class BusquedaService
{
    private PDO $pdo;

    private const COLUMNAS_ORDEN_PERMITIDAS = ['relevancia', 'fecha_publicacion', 'titulo'];

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::connection();
    }

    public function buscar(array $filtros, string $ordenarPor, int $pagina, int $porPagina): array
    {
        $condiciones = [];
        $params = [];
        $selectRelevancia = '0 AS relevancia';

        if (!empty($filtros['texto'])) {
            $condiciones[] = '(MATCH(a.contenido) AGAINST (:texto_bool IN BOOLEAN MODE) OR n.titulo LIKE :texto_like_titulo OR a.articulo_numero LIKE :texto_like_articulo)';
            $params['texto_bool'] = $filtros['texto'] . '*';
            $params['texto_like_titulo'] = '%' . $filtros['texto'] . '%';
            $params['texto_like_articulo'] = '%' . $filtros['texto'] . '%';
            $selectRelevancia = 'MATCH(a.contenido) AGAINST (:texto_bool_select IN BOOLEAN MODE) AS relevancia';
            $params['texto_bool_select'] = $filtros['texto'] . '*';
        }

        if (!empty($filtros['titulo_norma'])) {
            $condiciones[] = 'n.titulo LIKE :titulo_norma';
            $params['titulo_norma'] = '%' . $filtros['titulo_norma'] . '%';
        }
        if (!empty($filtros['tipo_norma_id'])) {
            $condiciones[] = 'n.tipo_norma_id = :tipo_norma_id';
            $params['tipo_norma_id'] = $filtros['tipo_norma_id'];
        }
        if (!empty($filtros['numero_norma'])) {
            $condiciones[] = 'n.numero_norma LIKE :numero_norma';
            $params['numero_norma'] = '%' . $filtros['numero_norma'] . '%';
        }
        if (!empty($filtros['articulo_numero'])) {
            $condiciones[] = 'a.articulo_numero LIKE :articulo_numero';
            $params['articulo_numero'] = '%' . $filtros['articulo_numero'] . '%';
        }
        if (!empty($filtros['entidad_emisora'])) {
            $condiciones[] = 'n.entidad_emisora LIKE :entidad_emisora';
            $params['entidad_emisora'] = '%' . $filtros['entidad_emisora'] . '%';
        }
        if (!empty($filtros['fecha_publicacion'])) {
            $condiciones[] = 'n.fecha_publicacion = :fecha_publicacion';
            $params['fecha_publicacion'] = $filtros['fecha_publicacion'];
        }
        if (!empty($filtros['estado'])) {
            $condiciones[] = 'n.estado = :estado';
            $params['estado'] = $filtros['estado'];
        }

        $condiciones[] = 'a.estado = 1';
        $where = 'WHERE ' . implode(' AND ', $condiciones);

        $orden = in_array($ordenarPor, self::COLUMNAS_ORDEN_PERMITIDAS, true) ? $ordenarPor : 'relevancia';
        $ordenSql = $orden === 'relevancia' ? 'relevancia DESC' : "n.{$orden} DESC";

        $sqlBase = "FROM articulos a JOIN normas n ON n.id = a.norma_id {$where}";

        $totalStmt = $this->pdo->prepare("SELECT COUNT(*) {$sqlBase}");
        $this->bindParams($totalStmt, $params, ['texto_bool_select']);
        $totalStmt->execute();
        $total = (int) $totalStmt->fetchColumn();

        $offset = max(0, ($pagina - 1) * $porPagina);
        $sql = "SELECT a.id AS articulo_id, a.articulo_numero, a.titulo AS articulo_titulo,
                       SUBSTRING(a.contenido, 1, 220) AS fragmento,
                       n.id AS norma_id, n.titulo AS norma_titulo, n.numero_norma, n.estado,
                       {$selectRelevancia}
                {$sqlBase}
                ORDER BY {$ordenSql}
                LIMIT :limite OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);
        $this->bindParams($stmt, $params, []);
        $stmt->bindValue(':limite', $porPagina, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return ['total' => $total, 'datos' => $stmt->fetchAll()];
    }

    private function bindParams(\PDOStatement $stmt, array $params, array $omitir): void
    {
        foreach ($params as $key => $value) {
            if (in_array($key, $omitir, true)) {
                continue;
            }
            $stmt->bindValue(':' . $key, $value);
        }
    }
}
