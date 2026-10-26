<?php

declare(strict_types=1);

/**
 * Crea el primer usuario administrador. Debe ejecutarse unicamente desde
 * la linea de comandos (CLI), nunca como un instalador web publico.
 *
 * Uso:
 *   php bin/create-admin.php "Nombre Completo" correo@dominio.ec "ContrasenaSegura123"
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Este script solo puede ejecutarse desde la linea de comandos.');
}

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\Database;
use App\Core\Env;
use App\Repositories\RolRepository;
use App\Services\UsuarioService;

define('BASE_PATH', dirname(__DIR__));
Env::load(BASE_PATH . '/.env');

$nombre = $argv[1] ?? null;
$correo = $argv[2] ?? null;
$password = $argv[3] ?? null;

if ($nombre === null || $correo === null || $password === null) {
    fwrite(STDERR, "Uso: php bin/create-admin.php \"Nombre Completo\" correo@dominio.ec \"ContrasenaSegura123\"\n");
    exit(1);
}

if (filter_var($correo, FILTER_VALIDATE_EMAIL) === false) {
    fwrite(STDERR, "El correo proporcionado no es valido.\n");
    exit(1);
}

if (mb_strlen($password) < 12) {
    fwrite(STDERR, "La contrasena del administrador debe tener al menos 12 caracteres.\n");
    exit(1);
}

try {
    $pdo = Database::connection();
} catch (\Throwable $e) {
    fwrite(STDERR, 'No fue posible conectar a la base de datos: ' . $e->getMessage() . "\n");
    exit(1);
}

$rolRepositorio = new RolRepository();
$roles = $rolRepositorio->listarActivos();
$rolAdmin = null;
foreach ($roles as $rol) {
    if ($rol['nombre'] === 'ADMINISTRADOR') {
        $rolAdmin = $rol;
        break;
    }
}

if ($rolAdmin === null) {
    fwrite(STDERR, "No se encontro el rol ADMINISTRADOR. Ejecute primero el esquema de base de datos.\n");
    exit(1);
}

$servicio = new UsuarioService();

try {
    $id = $servicio->crear($nombre, $correo, $password, [(int) $rolAdmin['id']]);
} catch (\Throwable $e) {
    fwrite(STDERR, 'No fue posible crear el administrador: ' . $e->getMessage() . "\n");
    exit(1);
}

echo "Administrador creado correctamente con id {$id}.\n";
