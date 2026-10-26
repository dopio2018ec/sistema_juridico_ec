<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class RelacionRepository
{
    private ?PDO $pdoInyectado;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdoInyectado = $pdo;
    }

    private function pdo(): PDO
    {
        return $this->pdoInyectado ??= Database::connection();
    }

    public function crear(array $datos): int
    {
        $stmt = $this->pdo()->prepare(
            'INSERT INTO relaciones_normativas (
                norma_origen_id, norma_destino_id, tipo_relacion,
                articulo_origen_id, articulo_destino_id, fecha_efecto, descripcion
             ) VALUES (
                :norma_origen_id, :norma_destino_id, :tipo_relacion,
                :articulo_origen_id, :articulo_destino_id, :fecha_efecto, :descripcion
             )'
        );
        $stmt->execute([
            'norma_origen_id' => $datos['norma_origen_id'],
            'norma_destino_id' => $datos['norma_destino_id'],
            'tipo_relacion' => $datos['tipo_relacion'],
            'articulo_origen_id' => ($datos['articulo_origen_id'] ?? '') ?: null,
            'articulo_destino_id' => ($datos['articulo_destino_id'] ?? '') ?: null,
            'fecha_efecto' => ($datos['fecha_efecto'] ?? '') ?: null,
            'descripcion' => ($datos['descripcion'] ?? '') ?: null,
        ]);

        return (int) $this->pdo()->lastInsertId();
    }

    public function listarPorNormaOrigen(int $normaId): array
    {
        $stmt = $this->pdo()->prepare(
            'SELECT rn.*, nd.titulo AS norma_destino_titulo
             FROM relaciones_normativas rn
             JOIN normas nd ON nd.id = rn.norma_destino_id
             WHERE rn.norma_origen_id = :id
             ORDER BY rn.fecha_creacion DESC'
        );
        $stmt->execute(['id' => $normaId]);

        return $stmt->fetchAll();
    }

    public function listarPorNormaDestino(int $normaId): array
    {
        $stmt = $this->pdo()->prepare(
            'SELECT rn.*, no.titulo AS norma_origen_titulo
             FROM relaciones_normativas rn
             JOIN normas no ON no.id = rn.norma_origen_id
             WHERE rn.norma_destino_id = :id
             ORDER BY rn.fecha_creacion DESC'
        );
        $stmt->execute(['id' => $normaId]);

        return $stmt->fetchAll();
    }

    public function existeDerogacionVigente(int $normaDestinoId, \DateTimeImmutable $hoy): bool
    {
        $stmt = $this->pdo()->prepare(
            "SELECT COUNT(*) FROM relaciones_normativas
             WHERE norma_destino_id = :id
               AND tipo_relacion IN ('DEROGACION_TOTAL', 'SUSTITUCION')
               AND (fecha_efecto IS NULL OR fecha_efecto <= :hoy)"
        );
        $stmt->execute(['id' => $normaDestinoId, 'hoy' => $hoy->format('Y-m-d')]);

        return ((int) $stmt->fetchColumn()) > 0;
    }
}
