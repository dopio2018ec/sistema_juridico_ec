<?php

declare(strict_types=1);

use App\Core\Env;
use App\Core\ErrorHandler;
use App\Core\View;

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

require BASE_PATH . '/vendor/autoload.php';

Env::load(BASE_PATH . '/.env');

$appConfig = require BASE_PATH . '/config/app.php';

date_default_timezone_set($appConfig['timezone']);

ini_set('display_errors', $appConfig['debug'] ? '1' : '0');
error_reporting(E_ALL);

ErrorHandler::register();

View::setBasePath(BASE_PATH . '/app/Views');

return $appConfig;
