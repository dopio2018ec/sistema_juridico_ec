<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

/**
 * Genera un volcado SQL completo de la base de datos (estructura +
 * datos) usando unicamente PDO, sin invocar binarios externos
 * (mysqldump) ni shell_exec: evita exponer la contrasena de la base
 * de datos en la lista de procesos y mantiene el mismo modelo de
 * acceso (PDO con consultas preparadas) que el resto de la aplicacion.
 */
final class BackupService
{
    private const FILAS_POR_LOTE = 500;

    private AuditoriaService $auditoria;

    public function __construct(?AuditoriaService $auditoria = null)
    {
        $this->auditoria = $auditoria ?? new AuditoriaService();
    }

    public function generarVolcado(): string
    {
        $pdo = Database::connection();
        $baseDatos = (string) $pdo->query('SELECT DATABASE()')->fetchColumn();
        $tablas = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);

        $sql = "-- Respaldo de '{$baseDatos}' generado el " . date('Y-m-d H:i:s')
            . " desde el panel de administracion\n"
            . "SET NAMES utf8mb4;\n"
            . "SET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ($tablas as $tabla) {
            $sql .= $this->volcarEstructura($pdo, $tabla);
            $sql .= $this->volcarDatos($pdo, $tabla);
        }

        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

        $this->auditoria->registrar('sistema', 0, 'RESPALDO', [
            'base_datos' => $baseDatos,
            'tablas' => count($tablas),
        ]);

        return $sql;
    }

    private function volcarEstructura(PDO $pdo, string $tabla): string
    {
        $fila = $pdo->query('SHOW CREATE TABLE `' . $tabla . '`')->fetch(PDO::FETCH_ASSOC);
        $create = $fila['Create Table'] ?? '';

        return "DROP TABLE IF EXISTS `{$tabla}`;\n{$create};\n\n";
    }

    private function volcarDatos(PDO $pdo, string $tabla): string
    {
        $stmt = $pdo->query('SELECT * FROM `' . $tabla . '`');

        $sql = '';
        $columnas = [];
        $lote = [];

        foreach ($stmt as $fila) {
            if ($columnas === []) {
                $columnas = array_keys($fila);
            }

            $valores = array_map(
                static fn ($valor) => $valor === null ? 'NULL' : $pdo->quote((string) $valor),
                $fila
            );
            $lote[] = '(' . implode(', ', $valores) . ')';

            if (count($lote) >= self::FILAS_POR_LOTE) {
                $sql .= $this->construirInsert($tabla, $columnas, $lote);
                $lote = [];
            }
        }

        if ($lote !== []) {
            $sql .= $this->construirInsert($tabla, $columnas, $lote);
        }

        return $sql . "\n";
    }

    private function construirInsert(string $tabla, array $columnas, array $lote): string
    {
        if ($columnas === [] || $lote === []) {
            return '';
        }

        $columnasSql = '`' . implode('`, `', $columnas) . '`';

        return "INSERT INTO `{$tabla}` ({$columnasSql}) VALUES\n" . implode(",\n", $lote) . ";\n";
    }
}
