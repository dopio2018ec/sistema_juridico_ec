<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class NormaRepository
{
    private ?PDO $pdoInyectado;

    private const COLUMNAS_ORDEN_PERMITIDAS = [
        'fecha_publicacion', 'titulo', 'fecha_creacion', 'numero_norma',
    ];

    public function __construct(?PDO $pdo = null)
    {
        $this->pdoInyectado = $pdo;
    }

    private function pdo(): PDO
    {
        return $this->pdoInyectado ??= Database::connection();
    }

    public function crear(array $datos, int $usuarioId): int
    {
        $stmt = $this->pdo()->prepare(
            'INSERT INTO normas (
                tipo_norma_id, titulo, numero_norma, siglas, entidad_emisora,
                fecha_emision, fecha_publicacion, fecha_vigencia, fecha_derogatoria,
                estado, observaciones, creado_por
             ) VALUES (
                :tipo_norma_id, :titulo, :numero_norma, :siglas, :entidad_emisora,
                :fecha_emision, :fecha_publicacion, :fecha_vigencia, :fecha_derogatoria,
                :estado, :observaciones, :creado_por
             )'
        );
        $stmt->execute([
            'tipo_norma_id' => $datos['tipo_norma_id'],
            'titulo' => $datos['titulo'],
            'numero_norma' => ($datos['numero_norma'] ?? '') ?: null,
            'siglas' => ($datos['siglas'] ?? '') ?: null,
            'entidad_emisora' => ($datos['entidad_emisora'] ?? '') ?: null,
            'fecha_emision' => ($datos['fecha_emision'] ?? '') ?: null,
            'fecha_publicacion' => ($datos['fecha_publicacion'] ?? '') ?: null,
            'fecha_vigencia' => ($datos['fecha_vigencia'] ?? '') ?: null,
            'fecha_derogatoria' => ($datos['fecha_derogatoria'] ?? '') ?: null,
            'estado' => $datos['estado'],
            'observaciones' => ($datos['observaciones'] ?? '') ?: null,
            'creado_por' => $usuarioId,
        ]);

        return (int) $this->pdo()->lastInsertId();
    }

    public function actualizar(int $id, array $datos): void
    {
        $stmt = $this->pdo()->prepare(
            'UPDATE normas SET
                tipo_norma_id = :tipo_norma_id,
                titulo = :titulo,
                numero_norma = :numero_norma,
                siglas = :siglas,
                entidad_emisora = :entidad_emisora,
                fecha_emision = :fecha_emision,
                fecha_publicacion = :fecha_publicacion,
                fecha_vigencia = :fecha_vigencia,
                fecha_derogatoria = :fecha_derogatoria,
                estado = :estado,
                observaciones = :observaciones
             WHERE id = :id'
        );
        $stmt->execute([
            'tipo_norma_id' => $datos['tipo_norma_id'],
            'titulo' => $datos['titulo'],
            'numero_norma' => ($datos['numero_norma'] ?? '') ?: null,
            'siglas' => ($datos['siglas'] ?? '') ?: null,
            'entidad_emisora' => ($datos['entidad_emisora'] ?? '') ?: null,
            'fecha_emision' => ($datos['fecha_emision'] ?? '') ?: null,
            'fecha_publicacion' => ($datos['fecha_publicacion'] ?? '') ?: null,
            'fecha_vigencia' => ($datos['fecha_vigencia'] ?? '') ?: null,
            'fecha_derogatoria' => ($datos['fecha_derogatoria'] ?? '') ?: null,
            'estado' => $datos['estado'],
            'observaciones' => ($datos['observaciones'] ?? '') ?: null,
            'id' => $id,
        ]);
    }

    public function marcarRevisada(int $id, int $usuarioRevisorId): void
    {
        $stmt = $this->pdo()->prepare(
            'UPDATE normas SET revisado_por = :revisor, fecha_revision = NOW() WHERE id = :id'
        );
        $stmt->execute(['revisor' => $usuarioRevisorId, 'id' => $id]);
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->pdo()->prepare(
            'SELECT n.*, tn.nombre AS tipo_norma_nombre, tn.codigo AS tipo_norma_codigo
             FROM normas n
             JOIN tipos_norma tn ON tn.id = n.tipo_norma_id
             WHERE n.id = :id'
        );
        $stmt->execute(['id' => $id]);
        $fila = $stmt->fetch();

        return $fila === false ? null : $fila;
    }

    public function existeDuplicado(int $tipoNormaId, ?string $numeroNorma, ?int $excluirId = null): bool
    {
        if ($numeroNorma === null || $numeroNorma === '') {
            return false;
        }

        $sql = 'SELECT COUNT(*) FROM normas WHERE tipo_norma_id = :tipo AND numero_norma = :numero';
        $params = ['tipo' => $tipoNormaId, 'numero' => $numeroNorma];

        if ($excluirId !== null) {
            $sql .= ' AND id <> :id';
            $params['id'] = $excluirId;
        }

        $stmt = $this->pdo()->prepare($sql);
        $stmt->execute($params);

        return ((int) $stmt->fetchColumn()) > 0;
    }

    public function buscarPaginado(array $filtros, string $ordenarPor, string $direccion, int $pagina, int $porPagina): array
    {
        $condiciones = [];
        $params = [];

        if (!empty($filtros['texto'])) {
            $condiciones[] = '(n.titulo LIKE :texto_titulo OR n.numero_norma LIKE :texto_numero OR n.siglas LIKE :texto_siglas)';
            $params['texto_titulo'] = '%' . $filtros['texto'] . '%';
            $params['texto_numero'] = '%' . $filtros['texto'] . '%';
            $params['texto_siglas'] = '%' . $filtros['texto'] . '%';
        }
        if (!empty($filtros['tipo_norma_id'])) {
            $condiciones[] = 'n.tipo_norma_id = :tipo_norma_id';
            $params['tipo_norma_id'] = $filtros['tipo_norma_id'];
        }
        if (!empty($filtros['estado'])) {
            $condiciones[] = 'n.estado = :estado';
            $params['estado'] = $filtros['estado'];
        }
        if (!empty($filtros['entidad_emisora'])) {
            $condiciones[] = 'n.entidad_emisora LIKE :entidad_emisora';
            $params['entidad_emisora'] = '%' . $filtros['entidad_emisora'] . '%';
        }
        if (!empty($filtros['fecha_publicacion_desde'])) {
            $condiciones[] = 'n.fecha_publicacion >= :fecha_desde';
            $params['fecha_desde'] = $filtros['fecha_publicacion_desde'];
        }
        if (!empty($filtros['fecha_publicacion_hasta'])) {
            $condiciones[] = 'n.fecha_publicacion <= :fecha_hasta';
            $params['fecha_hasta'] = $filtros['fecha_publicacion_hasta'];
        }

        $where = $condiciones === [] ? '' : 'WHERE ' . implode(' AND ', $condiciones);

        $ordenColumna = in_array($ordenarPor, self::COLUMNAS_ORDEN_PERMITIDAS, true) ? $ordenarPor : 'fecha_publicacion';
        $ordenDireccion = strtoupper($direccion) === 'ASC' ? 'ASC' : 'DESC';

        $totalStmt = $this->pdo()->prepare("SELECT COUNT(*) FROM normas n {$where}");
        $totalStmt->execute($params);
        $total = (int) $totalStmt->fetchColumn();

        $offset = max(0, ($pagina - 1) * $porPagina);
        $sql = "SELECT n.id, n.titulo, n.numero_norma, n.siglas, n.entidad_emisora,
                       n.fecha_publicacion, n.estado, tn.nombre AS tipo_norma_nombre
                FROM normas n
                JOIN tipos_norma tn ON tn.id = n.tipo_norma_id
                {$where}
                ORDER BY n.{$ordenColumna} {$ordenDireccion}
                LIMIT :limite OFFSET :offset";
        $stmt = $this->pdo()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limite', $porPagina, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return ['total' => $total, 'datos' => $stmt->fetchAll()];
    }
}
