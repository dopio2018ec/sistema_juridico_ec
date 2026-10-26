<?php

declare(strict_types=1);

use App\Core\Env;

return [
    'name' => Env::get('APP_NAME', 'Sistema Juridico Ecuador'),
    'env' => Env::get('APP_ENV', 'production'),
    'debug' => Env::bool('APP_DEBUG', false),
    'url' => Env::get('APP_URL', 'http://localhost'),
    'timezone' => Env::get('APP_TIMEZONE', 'America/Guayaquil'),
    'key' => Env::get('APP_KEY', ''),
    'login_max_intentos' => Env::int('LOGIN_MAX_INTENTOS', 5),
    'login_bloqueo_minutos' => Env::int('LOGIN_BLOQUEO_MINUTOS', 15),
];
