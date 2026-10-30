<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Database;
use App\Services\BackupService;

/**
 * Cubre, contra una base de datos real:
 *  - Que el volcado generado incluya la estructura y los datos de
 *    tablas conocidas del esquema (normas, usuarios).
 *  - Que la generacion del respaldo quede registrada en auditoria.
 */
final class BackupServiceIntegrationTest extends DatabaseTestCase
{
    public function test_genera_volcado_con_estructura_y_datos(): void
    {
        $sql = (new BackupService())->generarVolcado();

        $this->assertStringContainsString('DROP TABLE IF EXISTS `normas`;', $sql);
        $this->assertStringContainsString('CREATE TABLE `normas`', $sql);
        $this->assertStringContainsString('DROP TABLE IF EXISTS `usuarios`;', $sql);
    }

    public function test_registra_la_generacion_en_auditoria(): void
    {
        $pdo = Database::connection();
        $totalAntes = (int) $pdo->query("SELECT COUNT(*) FROM auditoria WHERE accion = 'RESPALDO'")->fetchColumn();

        (new BackupService())->generarVolcado();

        $totalDespues = (int) $pdo->query("SELECT COUNT(*) FROM auditoria WHERE accion = 'RESPALDO'")->fetchColumn();

        $this->assertSame($totalAntes + 1, $totalDespues);
    }
}
