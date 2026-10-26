<?php

declare(strict_types=1);

use App\Core\Request;
use App\Core\Router;
use App\Core\Session;
use App\Http\Middleware\SecurityHeadersMiddleware;

require dirname(__DIR__) . '/app/bootstrap.php';

Session::start();

$router = new Router();
(require dirname(__DIR__) . '/routes/web.php')($router);

$request = Request::fromGlobals();

$pipeline = new SecurityHeadersMiddleware();
$response = $pipeline->handle($request, function (Request $request) use ($router) {
    return $router->dispatch($request);
});

$response->send();
