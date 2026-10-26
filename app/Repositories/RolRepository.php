<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class RolRepository
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

    public function listarActivos(): array
    {
        $stmt = $this->pdo()->query(
            'SELECT id, nombre, descripcion FROM roles WHERE estado = 1 ORDER BY nombre'
        );

        return $stmt->fetchAll();
    }

    public function existenTodos(array $ids): bool
    {
        if ($ids === []) {
            return true;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->pdo()->prepare("SELECT COUNT(*) FROM roles WHERE id IN ({$placeholders})");
        $stmt->execute(array_map('intval', $ids));

        return ((int) $stmt->fetchColumn()) === count(array_unique($ids));
    }
}
