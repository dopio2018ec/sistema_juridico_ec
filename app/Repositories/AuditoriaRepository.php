<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class AuditoriaRepository
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

    public function registrar(
        ?int $usuarioId,
        string $tablaAfectada,
        int $registroId,
        string $accion,
        array $detalle = []
    ): void {
        $stmt = $this->pdo()->prepare(
            'INSERT INTO auditoria (usuario_id, tabla_afectada, registro_id, accion, detalle)
             VALUES (:usuario_id, :tabla_afectada, :registro_id, :accion, :detalle)'
        );
        $stmt->execute([
            'usuario_id' => $usuarioId,
            'tabla_afectada' => $tablaAfectada,
            'registro_id' => $registroId,
            'accion' => $accion,
            'detalle' => $detalle === [] ? null : json_encode($detalle, JSON_UNESCAPED_UNICODE),
        ]);
    }

    public function listarPaginado(int $pagina, int $porPagina): array
    {
        $offset = max(0, ($pagina - 1) * $porPagina);

        $total = (int) $this->pdo()->query('SELECT COUNT(*) FROM auditoria')->fetchColumn();

        $stmt = $this->pdo()->prepare(
            'SELECT a.id, a.usuario_id, u.nombre AS usuario_nombre, a.tabla_afectada,
                    a.registro_id, a.accion, a.detalle, a.fecha_evento
             FROM auditoria a
             LEFT JOIN usuarios u ON u.id = a.usuario_id
             ORDER BY a.fecha_evento DESC
             LIMIT :limite OFFSET :offset'
        );
        $stmt->bindValue(':limite', $porPagina, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return ['total' => $total, 'datos' => $stmt->fetchAll()];
    }
}
