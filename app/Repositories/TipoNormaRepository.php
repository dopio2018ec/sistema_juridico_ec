<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class TipoNormaRepository
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

    public function listarTodos(): array
    {
        $stmt = $this->pdo()->query('SELECT id, codigo, nombre, descripcion, estado FROM tipos_norma ORDER BY nombre');

        return $stmt->fetchAll();
    }

    public function listarActivos(): array
    {
        $stmt = $this->pdo()->query(
            'SELECT id, codigo, nombre FROM tipos_norma WHERE estado = 1 ORDER BY nombre'
        );

        return $stmt->fetchAll();
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM tipos_norma WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $fila = $stmt->fetch();

        return $fila === false ? null : $fila;
    }

    public function codigoExiste(string $codigo, ?int $excluirId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM tipos_norma WHERE codigo = :codigo';
        $params = ['codigo' => $codigo];
        if ($excluirId !== null) {
            $sql .= ' AND id <> :id';
            $params['id'] = $excluirId;
        }
        $stmt = $this->pdo()->prepare($sql);
        $stmt->execute($params);

        return ((int) $stmt->fetchColumn()) > 0;
    }

    public function crear(string $codigo, string $nombre, ?string $descripcion): int
    {
        $stmt = $this->pdo()->prepare(
            'INSERT INTO tipos_norma (codigo, nombre, descripcion, estado)
             VALUES (:codigo, :nombre, :descripcion, 1)'
        );
        $stmt->execute(['codigo' => $codigo, 'nombre' => $nombre, 'descripcion' => $descripcion]);

        return (int) $this->pdo()->lastInsertId();
    }

    public function actualizar(int $id, string $nombre, ?string $descripcion): void
    {
        $stmt = $this->pdo()->prepare(
            'UPDATE tipos_norma SET nombre = :nombre, descripcion = :descripcion WHERE id = :id'
        );
        $stmt->execute(['nombre' => $nombre, 'descripcion' => $descripcion, 'id' => $id]);
    }

    public function cambiarEstado(int $id, bool $activo): void
    {
        $stmt = $this->pdo()->prepare('UPDATE tipos_norma SET estado = :estado WHERE id = :id');
        $stmt->execute(['estado' => $activo ? 1 : 0, 'id' => $id]);
    }

    public function tieneNormasAsociadas(int $id): bool
    {
        $stmt = $this->pdo()->prepare('SELECT COUNT(*) FROM normas WHERE tipo_norma_id = :id');
        $stmt->execute(['id' => $id]);

        return ((int) $stmt->fetchColumn()) > 0;
    }
}
