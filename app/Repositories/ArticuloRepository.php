<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class ArticuloRepository
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

    public function crear(int $normaId, array $datos, int $usuarioId): int
    {
        $stmt = $this->pdo()->prepare(
            'INSERT INTO articulos (
                norma_id, articulo_padre_id, articulo_numero, titulo, tipo_contenido,
                contenido, orden, estado, creado_por, actualizado_por
             ) VALUES (
                :norma_id, :articulo_padre_id, :articulo_numero, :titulo, :tipo_contenido,
                :contenido, :orden, 1, :creado_por, :actualizado_por
             )'
        );
        $stmt->execute([
            'norma_id' => $normaId,
            'articulo_padre_id' => ($datos['articulo_padre_id'] ?? '') ?: null,
            'articulo_numero' => $datos['articulo_numero'],
            'titulo' => ($datos['titulo'] ?? '') ?: null,
            'tipo_contenido' => $datos['tipo_contenido'],
            'contenido' => $datos['contenido'],
            'orden' => $datos['orden'] ?? 0,
            'creado_por' => $usuarioId,
            'actualizado_por' => $usuarioId,
        ]);

        return (int) $this->pdo()->lastInsertId();
    }

    public function actualizarContenido(int $id, array $datos, int $usuarioId): void
    {
        $stmt = $this->pdo()->prepare(
            'UPDATE articulos SET
                articulo_numero = :articulo_numero,
                titulo = :titulo,
                tipo_contenido = :tipo_contenido,
                contenido = :contenido,
                orden = :orden,
                actualizado_por = :actualizado_por
             WHERE id = :id'
        );
        $stmt->execute([
            'articulo_numero' => $datos['articulo_numero'],
            'titulo' => ($datos['titulo'] ?? '') ?: null,
            'tipo_contenido' => $datos['tipo_contenido'],
            'contenido' => $datos['contenido'],
            'orden' => $datos['orden'] ?? 0,
            'actualizado_por' => $usuarioId,
            'id' => $id,
        ]);
    }

    public function cambiarEstado(int $id, bool $activo, int $usuarioId): void
    {
        $stmt = $this->pdo()->prepare(
            'UPDATE articulos SET estado = :estado, actualizado_por = :usuario WHERE id = :id'
        );
        $stmt->execute(['estado' => $activo ? 1 : 0, 'usuario' => $usuarioId, 'id' => $id]);
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM articulos WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $fila = $stmt->fetch();

        return $fila === false ? null : $fila;
    }

    public function listarPorNorma(int $normaId, bool $soloActivos = false): array
    {
        $sql = 'SELECT * FROM articulos WHERE norma_id = :norma_id';
        if ($soloActivos) {
            $sql .= ' AND estado = 1';
        }
        $sql .= ' ORDER BY orden ASC, id ASC';

        $stmt = $this->pdo()->prepare($sql);
        $stmt->execute(['norma_id' => $normaId]);

        return $stmt->fetchAll();
    }

    public function existeNumeroEnNorma(int $normaId, string $numero, ?int $excluirId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM articulos WHERE norma_id = :norma_id AND articulo_numero = :numero';
        $params = ['norma_id' => $normaId, 'numero' => $numero];
        if ($excluirId !== null) {
            $sql .= ' AND id <> :id';
            $params['id'] = $excluirId;
        }
        $stmt = $this->pdo()->prepare($sql);
        $stmt->execute($params);

        return ((int) $stmt->fetchColumn()) > 0;
    }

    public function buscarPorTextoYNorma(string $texto, int $pagina, int $porPagina): array
    {
        $offset = max(0, ($pagina - 1) * $porPagina);

        $totalStmt = $this->pdo()->prepare(
            'SELECT COUNT(*) FROM articulos a
             WHERE a.estado = 1 AND (
                a.articulo_numero LIKE :like
                OR MATCH(a.contenido) AGAINST (:texto IN BOOLEAN MODE)
             )'
        );
        $totalStmt->execute(['like' => '%' . $texto . '%', 'texto' => $texto . '*']);
        $total = (int) $totalStmt->fetchColumn();

        $stmt = $this->pdo()->prepare(
            'SELECT a.id, a.articulo_numero, a.titulo, a.contenido, a.norma_id,
                    n.titulo AS norma_titulo
             FROM articulos a
             JOIN normas n ON n.id = a.norma_id
             WHERE a.estado = 1 AND (
                a.articulo_numero LIKE :like
                OR MATCH(a.contenido) AGAINST (:texto IN BOOLEAN MODE)
             )
             ORDER BY a.norma_id, a.orden
             LIMIT :limite OFFSET :offset'
        );
        $stmt->bindValue(':like', '%' . $texto . '%');
        $stmt->bindValue(':texto', $texto . '*');
        $stmt->bindValue(':limite', $porPagina, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return ['total' => $total, 'datos' => $stmt->fetchAll()];
    }
}
