<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Database;
use PDO;
use PDOException;
use PHPUnit\Framework\TestCase;

/**
 * Base para pruebas de integracion que requieren una base de datos
 * MariaDB/MySQL real con el esquema de sistema_juridico_ec cargado
 * (vease database/migrations). Si no hay conexion disponible, la
 * prueba se omite explicitamente en lugar de fallar o darse por
 * buena sin ejecutarse.
 */
abstract class DatabaseTestCase extends TestCase
{
    protected static ?PDO $pdo = null;

    protected function setUp(): void
    {
        parent::setUp();

        if (self::$pdo === null) {
            try {
                self::$pdo = Database::connection();
                self::$pdo->query('SELECT 1');
            } catch (\Throwable $e) {
                self::$pdo = null;
            }
        }

        if (self::$pdo === null) {
            $this->markTestSkipped(
                'No hay una base de datos MariaDB/MySQL disponible en este entorno. '
                . 'Configure .env.testing con una instancia real y vuelva a ejecutar phpunit.'
            );
        }

        // Evita que un login exitoso de una prueba anterior deje un
        // usuario_id en $_SESSION que ya no existe en la base de datos
        // (Auth::id() se usa para la auditoria en varios servicios).
        $_SESSION = [];
    }

    /**
     * Los servicios bajo prueba gestionan sus propias transacciones
     * (ver NormaService), por lo que esta base no envuelve cada prueba
     * en una transaccion externa (MySQL/MariaDB no soporta transacciones
     * anidadas de forma transparente). Cada prueba debe limpiar los
     * registros que crea, normalmente dentro de su propio tearDown().
     */
}
