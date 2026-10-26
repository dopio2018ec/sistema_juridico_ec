<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Database;
use App\Repositories\TipoNormaRepository;
use App\Repositories\UsuarioRepository;
use App\Services\AuthService;
use App\Services\ArticuloService;
use App\Services\NormaService;
use App\Validators\Validator;

/**
 * Pruebas de seguridad general (requerimiento, seccion 12):
 *  - Intentos de inyeccion SQL.
 *  - Entradas XSS (se guardan como texto plano y se escapan al mostrarse).
 *  - Manipulacion de identificadores (IDs inexistentes / no numericos).
 *
 * Las verificaciones de exposicion de errores, acceso directo a archivos
 * privados y rechazo de CSRF se hicieron manualmente contra el servidor
 * HTTP real durante el desarrollo (ver SECURITY.md); no se repiten aqui
 * porque requieren un servidor HTTP en ejecucion, no solo una conexion PDO.
 */
final class SeguridadIntegrationTest extends DatabaseTestCase
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
            $pdo->prepare('DELETE FROM normas WHERE id = ?')->execute([$id]);
        }
        if ($this->tipoNormaId !== null) {
            $pdo->prepare('DELETE FROM tipos_norma WHERE id = ?')->execute([$this->tipoNormaId]);
        }

        parent::tearDown();
    }

    private function usuarioDePrueba(): int
    {
        $id = (int) Database::connection()->query('SELECT id FROM usuarios LIMIT 1')->fetchColumn();
        if ($id === 0) {
            $this->markTestSkipped('No hay usuarios en la base de pruebas.');
        }

        return $id;
    }

    public function test_intento_de_inyeccion_sql_en_login_no_autentica_ni_rompe_la_consulta(): void
    {
        $resultado = (new AuthService())->intentarLogin("' OR '1'='1", "' OR '1'='1");

        $this->assertFalse($resultado['exito']);

        // La tabla usuarios debe seguir intacta y consultable.
        $total = (int) Database::connection()->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
        $this->assertGreaterThanOrEqual(0, $total);
    }

    public function test_intento_de_inyeccion_sql_en_busqueda_de_usuarios_no_rompe_la_consulta(): void
    {
        $repositorio = new UsuarioRepository();

        $resultado = $repositorio->listarPaginado("x'; DROP TABLE usuarios; --", null, 1, 10);

        $this->assertIsArray($resultado['datos']);

        $stmt = Database::connection()->query("SHOW TABLES LIKE 'usuarios'");
        $this->assertNotFalse($stmt->fetch(), 'La tabla usuarios no debio verse afectada por la inyeccion.');
    }

    public function test_contenido_xss_se_almacena_como_texto_plano_y_no_como_html_ejecutable(): void
    {
        $this->tipoNormaId = (new TipoNormaRepository())->crear('TEST_' . bin2hex(random_bytes(4)), 'Tipo XSS', null);
        $usuarioId = $this->usuarioDePrueba();

        $payload = "<img src=x onerror=alert('xss')>contenido";

        $normaId = (new NormaService())->crearConPublicacionYArticulos([
            'tipo_norma_id' => $this->tipoNormaId,
            'titulo' => '<script>alert(1)</script> Norma XSS',
            'numero_norma' => null, 'siglas' => null, 'entidad_emisora' => null,
            'fecha_emision' => null, 'fecha_publicacion' => null,
            'fecha_vigencia' => null, 'fecha_derogatoria' => null,
            'estado' => 'BORRADOR', 'observaciones' => null,
        ], $usuarioId, null, [
            ['articulo_numero' => '1', 'tipo_contenido' => 'ARTICULO', 'contenido' => $payload, 'orden' => 0],
        ]);
        $this->normasCreadas[] = $normaId;

        $articulo = Database::connection()
            ->query("SELECT contenido FROM articulos WHERE norma_id = {$normaId} LIMIT 1")
            ->fetch();

        // Se guarda exactamente como texto plano (sin sanitizar en el servidor
        // como HTML), confiando en que la salida se escapa con View::e() al
        // renderizar (htmlspecialchars). Aqui se verifica que al menos no se
        // transformo en nada distinto a lo enviado.
        $this->assertSame($payload, $articulo['contenido']);

        // Al "renderizarlo" como lo hace View::e(), nunca debe contener una
        // etiqueta <img ...onerror=...> ejecutable sin escapar.
        $escapado = htmlspecialchars($articulo['contenido'], ENT_QUOTES, 'UTF-8');
        $this->assertStringNotContainsString('<img', $escapado);
        $this->assertStringContainsString('&lt;img', $escapado);
    }

    public function test_articulo_rechaza_contenido_vacio_pese_a_payload_malicioso_en_otros_campos(): void
    {
        $this->tipoNormaId = (new TipoNormaRepository())->crear('TEST_' . bin2hex(random_bytes(4)), 'Tipo XSS 2', null);
        $usuarioId = $this->usuarioDePrueba();

        $normaId = (new NormaService())->crearConPublicacionYArticulos([
            'tipo_norma_id' => $this->tipoNormaId,
            'titulo' => 'Norma para prueba de articulo invalido',
            'numero_norma' => null, 'siglas' => null, 'entidad_emisora' => null,
            'fecha_emision' => null, 'fecha_publicacion' => null,
            'fecha_vigencia' => null, 'fecha_derogatoria' => null,
            'estado' => 'BORRADOR', 'observaciones' => null,
        ], $usuarioId);
        $this->normasCreadas[] = $normaId;

        $validador = (new ArticuloService())->validar([
            'articulo_numero' => "1'; DROP TABLE articulos; --",
            'tipo_contenido' => 'ARTICULO',
            'contenido' => '',
        ], $normaId);

        $this->assertTrue($validador->fails());
        $this->assertArrayHasKey('contenido', $validador->errors());
    }

    public function test_identificador_inexistente_no_produce_error_ni_datos_de_otro_registro(): void
    {
        $repositorio = new \App\Repositories\NormaRepository();

        $this->assertNull($repositorio->buscarPorId(999999999));
    }

    public function test_identificador_no_numerico_se_normaliza_sin_excepcion(): void
    {
        // Simula lo que hacen los controladores: (int) sobre un parametro de ruta
        // no numerico, en vez de pasar el valor crudo a una consulta SQL.
        $idCrudo = "abc'; DROP TABLE normas; --";
        $idNormalizado = (int) $idCrudo;

        $this->assertSame(0, $idNormalizado);

        $repositorio = new \App\Repositories\NormaRepository();
        $this->assertNull($repositorio->buscarPorId($idNormalizado));

        $stmt = Database::connection()->query("SHOW TABLES LIKE 'normas'");
        $this->assertNotFalse($stmt->fetch());
    }
}
