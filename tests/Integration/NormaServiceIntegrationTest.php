<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Database;
use App\Repositories\ArticuloRepository;
use App\Repositories\NormaRepository;
use App\Repositories\PublicacionRepository;
use App\Repositories\TipoNormaRepository;
use App\Services\NormaService;

/**
 * Cubre, contra una base de datos real:
 *  - Crear una norma con publicacion y articulos en una sola transaccion.
 *  - Que un fallo a mitad de la transaccion no deje datos incompletos.
 *  - Prevencion de duplicados (mismo tipo + numero de norma).
 *  - Rechazo de referencias a tipos de norma inexistentes.
 */
final class NormaServiceIntegrationTest extends DatabaseTestCase
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
        foreach ($this->normasCreadas as $id) {
            $pdo->prepare('DELETE FROM articulos WHERE norma_id = ?')->execute([$id]);
            $pdo->prepare('DELETE FROM publicaciones_oficiales WHERE norma_id = ?')->execute([$id]);
            $pdo->prepare('DELETE FROM normas WHERE id = ?')->execute([$id]);
        }
        if ($this->tipoNormaId !== null) {
            $pdo->prepare('DELETE FROM tipos_norma WHERE id = ?')->execute([$this->tipoNormaId]);
        }

        parent::tearDown();
    }

    private function crearTipoNormaDePrueba(): int
    {
        $codigo = 'TEST_' . bin2hex(random_bytes(4));
        $this->tipoNormaId = (new TipoNormaRepository())->crear($codigo, 'Tipo de prueba ' . $codigo, null);

        return $this->tipoNormaId;
    }

    private function usuarioDePrueba(): int
    {
        $stmt = Database::connection()->query('SELECT id FROM usuarios LIMIT 1');
        $id = $stmt->fetchColumn();

        if ($id === false) {
            $this->markTestSkipped('No hay usuarios en la base de datos de pruebas para asociar como creador.');
        }

        return (int) $id;
    }

    public function test_crea_norma_con_publicacion_y_articulos_en_una_transaccion(): void
    {
        $tipoId = $this->crearTipoNormaDePrueba();
        $usuarioId = $this->usuarioDePrueba();

        $servicio = new NormaService();
        $id = $servicio->crearConPublicacionYArticulos(
            [
                'tipo_norma_id' => $tipoId,
                'titulo' => 'Norma de prueba de integracion',
                'numero_norma' => 'TEST-001',
                'siglas' => null, 'entidad_emisora' => null,
                'fecha_emision' => null, 'fecha_publicacion' => null,
                'fecha_vigencia' => null, 'fecha_derogatoria' => null,
                'estado' => 'BORRADOR', 'observaciones' => null,
            ],
            $usuarioId,
            ['nombre_publicacion' => 'Registro Oficial', 'numero' => '100', 'url_fuente' => 'https://example.gob.ec/ro/100'],
            [['articulo_numero' => '1', 'tipo_contenido' => 'ARTICULO', 'contenido' => 'Contenido de prueba', 'orden' => 0]]
        );
        $this->normasCreadas[] = $id;

        $norma = (new NormaRepository())->buscarPorId($id);
        $this->assertNotNull($norma);
        $this->assertCount(1, (new ArticuloRepository())->listarPorNorma($id));
        $this->assertCount(1, (new PublicacionRepository())->listarPorNorma($id));
    }

    public function test_fallo_en_transaccion_no_deja_datos_incompletos(): void
    {
        $tipoId = $this->crearTipoNormaDePrueba();
        $usuarioId = $this->usuarioDePrueba();

        $servicio = new NormaService();

        try {
            $servicio->crearConPublicacionYArticulos(
                [
                    'tipo_norma_id' => $tipoId,
                    'titulo' => 'Norma que debe fallar',
                    'numero_norma' => 'TEST-002',
                    'siglas' => null, 'entidad_emisora' => null,
                    'fecha_emision' => null, 'fecha_publicacion' => null,
                    'fecha_vigencia' => null, 'fecha_derogatoria' => null,
                    'estado' => 'BORRADOR', 'observaciones' => null,
                ],
                $usuarioId,
                null,
                // tipo_contenido invalido a nivel de ENUM: debe hacer fallar el INSERT
                [['articulo_numero' => '1', 'tipo_contenido' => 'TIPO_INVALIDO_NO_EXISTE', 'contenido' => 'x', 'orden' => 0]]
            );
            $this->fail('Se esperaba una excepcion por tipo_contenido invalido.');
        } catch (\Throwable $e) {
            // esperado
        }

        $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM normas WHERE numero_norma = ?');
        $stmt->execute(['TEST-002']);
        $this->assertSame(0, (int) $stmt->fetchColumn(), 'La norma no debio quedar registrada tras el fallo transaccional.');
    }

    public function test_previene_duplicado_mismo_tipo_y_numero(): void
    {
        $tipoId = $this->crearTipoNormaDePrueba();
        $usuarioId = $this->usuarioDePrueba();
        $servicio = new NormaService();

        $id = $servicio->crearConPublicacionYArticulos([
            'tipo_norma_id' => $tipoId, 'titulo' => 'Original', 'numero_norma' => 'DUP-1',
            'siglas' => null, 'entidad_emisora' => null, 'fecha_emision' => null,
            'fecha_publicacion' => null, 'fecha_vigencia' => null, 'fecha_derogatoria' => null,
            'estado' => 'BORRADOR', 'observaciones' => null,
        ], $usuarioId);
        $this->normasCreadas[] = $id;

        $validador = $servicio->validar([
            'tipo_norma_id' => $tipoId, 'titulo' => 'Duplicada', 'numero_norma' => 'DUP-1',
            'estado' => 'BORRADOR',
        ]);

        $this->assertTrue($validador->fails());
    }

    public function test_rechaza_tipo_de_norma_inexistente(): void
    {
        $servicio = new NormaService();
        $validador = $servicio->validar([
            'tipo_norma_id' => 999999999, 'titulo' => 'Norma con tipo inexistente', 'estado' => 'BORRADOR',
        ]);

        $this->assertTrue($validador->fails());
        $this->assertArrayHasKey('tipo_norma_id', $validador->errors());
    }
}
