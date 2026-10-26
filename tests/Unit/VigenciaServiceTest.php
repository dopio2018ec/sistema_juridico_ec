<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\VigenciaService;
use PHPUnit\Framework\TestCase;

/**
 * Cubre las ramas de VigenciaService que no requieren consultar relaciones
 * normativas en base de datos (retorno anticipado). Las ramas que dependen
 * de RelacionRepository requieren una instancia de MariaDB y se documentan
 * como prueba de integracion pendiente (ver tests/Integration).
 */
final class VigenciaServiceTest extends TestCase
{
    public function test_norma_derogada_por_estado_administrativo(): void
    {
        $resultado = (new VigenciaService())->evaluar([
            'id' => 1,
            'estado' => 'DEROGADA',
            'fecha_derogatoria' => null,
            'fecha_vigencia' => null,
            'revisado_por' => null,
        ]);

        $this->assertSame('Derogada', $resultado['etiqueta']);
        $this->assertTrue($resultado['verificada']);
    }

    public function test_norma_derogada_por_fecha_derogatoria_pasada(): void
    {
        $resultado = (new VigenciaService())->evaluar([
            'id' => 1,
            'estado' => 'VIGENTE',
            'fecha_derogatoria' => '2000-01-01',
            'fecha_vigencia' => null,
            'revisado_por' => null,
        ]);

        $this->assertStringContainsString('Derogada', $resultado['etiqueta']);
        $this->assertTrue($resultado['verificada']);
    }

    // El caso BORRADOR/ARCHIVADA tambien pasa por existeDerogacionVigente()
    // antes de evaluar el estado, por lo que requiere base de datos real.
    // Ver tests/Integration/VigenciaServiceIntegrationTest.php.
}
