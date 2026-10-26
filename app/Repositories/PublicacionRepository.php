<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class PublicacionRepository
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

    public function crear(int $normaId, array $datos): int
    {
        $stmt = $this->pdo()->prepare(
            'INSERT INTO publicaciones_oficiales (
                norma_id, nombre_publicacion, numero, suplemento, fecha_publicacion,
                pagina_inicial, pagina_final, url_fuente, observaciones
             ) VALUES (
                :norma_id, :nombre_publicacion, :numero, :suplemento, :fecha_publicacion,
                :pagina_inicial, :pagina_final, :url_fuente, :observaciones
             )'
        );
        $stmt->execute([
            'norma_id' => $normaId,
            'nombre_publicacion' => ($datos['nombre_publicacion'] ?? '') ?: 'Registro Oficial',
            'numero' => ($datos['numero'] ?? '') ?: null,
            'suplemento' => ($datos['suplemento'] ?? '') ?: null,
            'fecha_publicacion' => ($datos['fecha_publicacion'] ?? '') ?: null,
            'pagina_inicial' => ($datos['pagina_inicial'] ?? '') ?: null,
            'pagina_final' => ($datos['pagina_final'] ?? '') ?: null,
            'url_fuente' => ($datos['url_fuente'] ?? '') ?: null,
            'observaciones' => ($datos['observaciones'] ?? '') ?: null,
        ]);

        return (int) $this->pdo()->lastInsertId();
    }

    public function listarPorNorma(int $normaId): array
    {
        $stmt = $this->pdo()->prepare(
            'SELECT * FROM publicaciones_oficiales WHERE norma_id = :norma_id ORDER BY fecha_publicacion DESC'
        );
        $stmt->execute(['norma_id' => $normaId]);

        return $stmt->fetchAll();
    }

    public function tienePublicaciones(int $normaId): bool
    {
        $stmt = $this->pdo()->prepare('SELECT COUNT(*) FROM publicaciones_oficiales WHERE norma_id = :norma_id');
        $stmt->execute(['norma_id' => $normaId]);

        return ((int) $stmt->fetchColumn()) > 0;
    }
}
