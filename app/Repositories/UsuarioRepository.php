<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class UsuarioRepository
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

    public function buscarPorCorreo(string $correo): ?array
    {
        $stmt = $this->pdo()->prepare(
            'SELECT id, nombre, correo, password_hash, estado,
                    intentos_fallidos, bloqueado_hasta, ultimo_acceso
             FROM usuarios
             WHERE correo = :correo'
        );
        $stmt->execute(['correo' => $correo]);
        $usuario = $stmt->fetch();

        return $usuario === false ? null : $usuario;
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->pdo()->prepare(
            'SELECT id, nombre, correo, estado, ultimo_acceso, fecha_creacion
             FROM usuarios WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
        $usuario = $stmt->fetch();

        return $usuario === false ? null : $usuario;
    }

    public function correoExiste(string $correo, ?int $excluirId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM usuarios WHERE correo = :correo';
        $params = ['correo' => $correo];

        if ($excluirId !== null) {
            $sql .= ' AND id <> :id';
            $params['id'] = $excluirId;
        }

        $stmt = $this->pdo()->prepare($sql);
        $stmt->execute($params);

        return ((int) $stmt->fetchColumn()) > 0;
    }

    public function crear(string $nombre, string $correo, string $passwordHash): int
    {
        $stmt = $this->pdo()->prepare(
            'INSERT INTO usuarios (nombre, correo, password_hash, estado)
             VALUES (:nombre, :correo, :password_hash, 1)'
        );
        $stmt->execute([
            'nombre' => $nombre,
            'correo' => $correo,
            'password_hash' => $passwordHash,
        ]);

        return (int) $this->pdo()->lastInsertId();
    }

    public function actualizarDatos(int $id, string $nombre, string $correo): void
    {
        $stmt = $this->pdo()->prepare(
            'UPDATE usuarios SET nombre = :nombre, correo = :correo WHERE id = :id'
        );
        $stmt->execute(['nombre' => $nombre, 'correo' => $correo, 'id' => $id]);
    }

    public function actualizarPassword(int $id, string $passwordHash): void
    {
        $stmt = $this->pdo()->prepare(
            'UPDATE usuarios SET password_hash = :password_hash WHERE id = :id'
        );
        $stmt->execute(['password_hash' => $passwordHash, 'id' => $id]);
    }

    public function cambiarEstado(int $id, bool $activo): void
    {
        $stmt = $this->pdo()->prepare('UPDATE usuarios SET estado = :estado WHERE id = :id');
        $stmt->execute(['estado' => $activo ? 1 : 0, 'id' => $id]);
    }

    public function registrarIntentoFallido(int $id, int $intentos, ?string $bloqueadoHasta): void
    {
        $stmt = $this->pdo()->prepare(
            'UPDATE usuarios SET intentos_fallidos = :intentos, bloqueado_hasta = :bloqueado_hasta WHERE id = :id'
        );
        $stmt->execute(['intentos' => $intentos, 'bloqueado_hasta' => $bloqueadoHasta, 'id' => $id]);
    }

    public function registrarAccesoExitoso(int $id): void
    {
        $stmt = $this->pdo()->prepare(
            'UPDATE usuarios
             SET intentos_fallidos = 0, bloqueado_hasta = NULL, ultimo_acceso = NOW()
             WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
    }

    public function listarPaginado(?string $busqueda, ?int $estado, int $pagina, int $porPagina): array
    {
        $offset = max(0, ($pagina - 1) * $porPagina);
        $condiciones = [];
        $params = [];

        if ($busqueda !== null && $busqueda !== '') {
            $condiciones[] = '(nombre LIKE :busqueda_nombre OR correo LIKE :busqueda_correo)';
            $params['busqueda_nombre'] = '%' . $busqueda . '%';
            $params['busqueda_correo'] = '%' . $busqueda . '%';
        }

        if ($estado !== null) {
            $condiciones[] = 'estado = :estado';
            $params['estado'] = $estado;
        }

        $where = $condiciones === [] ? '' : 'WHERE ' . implode(' AND ', $condiciones);

        $stmtTotal = $this->pdo()->prepare("SELECT COUNT(*) FROM usuarios {$where}");
        $stmtTotal->execute($params);
        $total = (int) $stmtTotal->fetchColumn();

        $sql = "SELECT id, nombre, correo, estado, ultimo_acceso, fecha_creacion
                FROM usuarios {$where}
                ORDER BY nombre ASC
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

    public function rolesDeUsuario(int $usuarioId): array
    {
        $stmt = $this->pdo()->prepare(
            'SELECT r.id, r.nombre
             FROM roles r
             JOIN usuarios_roles ur ON ur.rol_id = r.id
             WHERE ur.usuario_id = :usuario_id'
        );
        $stmt->execute(['usuario_id' => $usuarioId]);

        return $stmt->fetchAll();
    }

    public function sincronizarRoles(int $usuarioId, array $rolIds): void
    {
        $delete = $this->pdo()->prepare('DELETE FROM usuarios_roles WHERE usuario_id = :usuario_id');
        $delete->execute(['usuario_id' => $usuarioId]);

        $insert = $this->pdo()->prepare(
            'INSERT INTO usuarios_roles (usuario_id, rol_id) VALUES (:usuario_id, :rol_id)'
        );

        foreach ($rolIds as $rolId) {
            $insert->execute(['usuario_id' => $usuarioId, 'rol_id' => (int) $rolId]);
        }
    }
}
