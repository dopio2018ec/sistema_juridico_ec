<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Database;
use App\Repositories\RelacionRepository;
use App\Repositories\TipoNormaRepository;
use App\Services\NormaService;
use App\Services\VigenciaService;

final class VigenciaServiceIntegrationTest extends DatabaseTestCase
{
    private array $normasCreadas = [];
    private ?int $tipoNormaId = null;

    protected function tearDown(): void
    {
        if (self::$pdo === null) {
            parent::tearDown();

            return;
        }

        $pdo = Database::connection();
        $pdo->prepare('DELETE FROM relaciones_normativas WHERE norma_origen_id IN (' . implode(',', array_map('intval', $this->normasCreadas ?: [0])) . ') OR norma_destino_id IN (' . implode(',', array_map('intval', $this->normasCreadas ?: [0])) . ')')->execute();
        foreach ($this->normasCreadas as $id) {
            $pdo->prepare('DELETE FROM normas WHERE id = ?')->execute([$id]);
        }
        if ($this->tipoNormaId !== null) {
            $pdo->prepare('DELETE FROM tipos_norma WHERE id = ?')->execute([$this->tipoNormaId]);
        }

        parent::tearDown();
    }

    private function crearNorma(array $overrides = []): int
    {
        if ($this->tipoNormaId === null) {
            $this->tipoNormaId = (new TipoNormaRepository())->crear('TEST_' . bin2hex(random_bytes(4)), 'Tipo de prueba', null);
        }

        $usuarioId = (int) Database::connection()->query('SELECT id FROM usuarios LIMIT 1')->fetchColumn();
        if ($usuarioId === 0) {
            $this->markTestSkipped('No hay usuarios en la base de pruebas.');
        }

        $datos = array_merge([
            'tipo_norma_id' => $this->tipoNormaId,
            'titulo' => 'Norma vigencia ' . bin2hex(random_bytes(3)),
            'numero_norma' => null, 'siglas' => null, 'entidad_emisora' => null,
            'fecha_emision' => null, 'fecha_publicacion' => null,
            'fecha_vigencia' => null, 'fecha_derogatoria' => null,
            'estado' => 'VIGENTE', 'observaciones' => null,
        ], $overrides);

        $id = (new NormaService())->crearConPublicacionYArticulos($datos, $usuarioId);
        $this->normasCreadas[] = $id;

        return $id;
    }

    public function test_norma_vigente_sin_revisar_queda_pendiente_de_verificacion(): void
    {
        $id = $this->crearNorma(['fecha_vigencia' => '2020-01-01', 'estado' => 'VIGENTE']);
        $norma = (new \App\Repositories\NormaRepository())->buscarPorId($id);

        $resultado = (new VigenciaService())->evaluar($norma);

        $this->assertFalse($resultado['verificada']);
        $this->assertStringContainsString('pendiente', $resultado['etiqueta']);
    }

    public function test_norma_vigente_revisada_se_muestra_como_verificada(): void
    {
        $id = $this->crearNorma(['fecha_vigencia' => '2020-01-01', 'estado' => 'VIGENTE']);
        $usuarioId = (int) Database::connection()->query('SELECT id FROM usuarios LIMIT 1')->fetchColumn();
        (new NormaService())->marcarRevisada($id, $usuarioId);

        $norma = (new \App\Repositories\NormaRepository())->buscarPorId($id);
        $resultado = (new VigenciaService())->evaluar($norma);

        $this->assertTrue($resultado['verificada']);
        $this->assertStringContainsString('Vigente', $resultado['etiqueta']);
    }

    public function test_norma_con_relacion_de_derogacion_no_se_declara_vigente(): void
    {
        $origen = $this->crearNorma(['estado' => 'VIGENTE']);
        $destino = $this->crearNorma(['fecha_vigencia' => '2020-01-01', 'estado' => 'VIGENTE']);

        (new RelacionRepository())->crear([
            'norma_origen_id' => $origen,
            'norma_destino_id' => $destino,
            'tipo_relacion' => 'DEROGACION_TOTAL',
            'articulo_origen_id' => null, 'articulo_destino_id' => null,
            'fecha_efecto' => '2021-01-01',
            'descripcion' => 'Prueba de integracion',
        ]);

        $norma = (new \App\Repositories\NormaRepository())->buscarPorId($destino);
        $resultado = (new VigenciaService())->evaluar($norma);

        $this->assertFalse($resultado['verificada']);
    }
}
