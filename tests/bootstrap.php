<?php

declare(strict_types=1);

use App\Core\Env;

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

require BASE_PATH . '/vendor/autoload.php';

Env::load(BASE_PATH . '/.env.testing');

// Permite que App\Core\Session (usada por AuthService::intentarLogin) funcione
// en las pruebas de integracion que ejercen el login completo desde CLI.
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}
