<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Database;
use App\Repositories\RolRepository;
use App\Repositories\UsuarioRepository;
use App\Services\UsuarioService;
use InvalidArgumentException;

final class UsuarioServiceIntegrationTest extends DatabaseTestCase
{
    private array $usuariosCreados = [];

    protected function tearDown(): void
    {
        if (self::$pdo === null) {
            parent::tearDown();

            return;
        }

        $pdo = Database::connection();
        foreach ($this->usuariosCreados as $id) {
            $pdo->prepare('DELETE FROM usuarios_roles WHERE usuario_id = ?')->execute([$id]);
            $pdo->prepare('DELETE FROM auditoria WHERE usuario_id = ?')->execute([$id]);
            $pdo->prepare('DELETE FROM usuarios WHERE id = ?')->execute([$id]);
        }

        parent::tearDown();
    }

    private function rolConsultaId(): int
    {
        $roles = (new RolRepository())->listarActivos();
        foreach ($roles as $rol) {
            if ($rol['nombre'] === 'CONSULTA') {
                return (int) $rol['id'];
            }
        }
        $this->markTestSkipped('El rol CONSULTA no existe en la base de pruebas.');
    }

    public function test_crear_usuario_con_rol_valido(): void
    {
        $servicio = new UsuarioService();
        $correo = 'test_' . bin2hex(random_bytes(4)) . '@example.test';

        $id = $servicio->crear('Usuario de prueba', $correo, 'ContrasenaSegura123', [$this->rolConsultaId()]);
        $this->usuariosCreados[] = $id;

        $usuario = (new UsuarioRepository())->buscarPorCorreo($correo);
        $this->assertNotNull($usuario);
        $this->assertNotSame('ContrasenaSegura123', $usuario['password_hash']);
    }

    public function test_rechaza_correo_duplicado(): void
    {
        $servicio = new UsuarioService();
        $correo = 'dup_' . bin2hex(random_bytes(4)) . '@example.test';
        $id = $servicio->crear('Original', $correo, 'ContrasenaSegura123', [$this->rolConsultaId()]);
        $this->usuariosCreados[] = $id;

        $validador = $servicio->validarDatosBase(['nombre' => 'Otro', 'correo' => $correo]);

        $this->assertTrue($validador->fails());
        $this->assertArrayHasKey('correo', $validador->errors());
    }

    public function test_rechaza_rol_inexistente(): void
    {
        $servicio = new UsuarioService();
        $this->expectException(InvalidArgumentException::class);

        $servicio->crear('Usuario invalido', 'rol_invalido_' . bin2hex(random_bytes(4)) . '@example.test', 'ContrasenaSegura123', [999999999]);
    }
}
