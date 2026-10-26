<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Database;
use App\Repositories\RolRepository;
use App\Services\AuthService;
use App\Services\UsuarioService;

final class AuthServiceIntegrationTest extends DatabaseTestCase
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

    private function crearUsuarioDePrueba(string $password): array
    {
        $roles = (new RolRepository())->listarActivos();
        $rolId = (int) ($roles[0]['id'] ?? 0);
        if ($rolId === 0) {
            $this->markTestSkipped('No hay roles en la base de pruebas.');
        }

        $correo = 'login_' . bin2hex(random_bytes(4)) . '@example.test';
        $id = (new UsuarioService())->crear('Usuario login', $correo, $password, [$rolId]);
        $this->usuariosCreados[] = $id;

        return ['id' => $id, 'correo' => $correo];
    }

    public function test_login_correcto_con_credenciales_validas(): void
    {
        $usuario = $this->crearUsuarioDePrueba('ContrasenaSegura123');

        $resultado = (new AuthService())->intentarLogin($usuario['correo'], 'ContrasenaSegura123');

        $this->assertTrue($resultado['exito']);
    }

    public function test_login_incorrecto_con_contrasena_invalida(): void
    {
        $usuario = $this->crearUsuarioDePrueba('ContrasenaSegura123');

        $resultado = (new AuthService())->intentarLogin($usuario['correo'], 'ContrasenaIncorrecta');

        $this->assertFalse($resultado['exito']);
    }

    public function test_bloqueo_temporal_tras_intentos_fallidos(): void
    {
        $usuario = $this->crearUsuarioDePrueba('ContrasenaSegura123');
        $servicio = new AuthService();

        for ($i = 0; $i < 5; $i++) {
            $servicio->intentarLogin($usuario['correo'], 'ContrasenaIncorrecta');
        }

        // El intento numero 6, incluso con la contrasena correcta, debe
        // ser rechazado porque la cuenta quedo bloqueada temporalmente.
        $resultado = $servicio->intentarLogin($usuario['correo'], 'ContrasenaSegura123');

        $this->assertFalse($resultado['exito']);
        $this->assertStringContainsString('bloqueada', $resultado['mensaje']);
    }
}
