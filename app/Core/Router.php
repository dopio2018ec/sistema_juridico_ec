<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    /** @var array<int, array{method:string, pattern:string, paramNames:array, handler:callable|array, middleware:array}> */
    private array $routes = [];

    public function get(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    public function post(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    public function put(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->add('PUT', $path, $handler, $middleware);
    }

    public function delete(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->add('DELETE', $path, $handler, $middleware);
    }

    private function add(string $method, string $path, callable|array $handler, array $middleware): void
    {
        $paramNames = [];
        $pattern = preg_replace_callback('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', function ($matches) use (&$paramNames) {
            $paramNames[] = $matches[1];

            return '([^/]+)';
        }, $path);

        $this->routes[] = [
            'method' => $method,
            'pattern' => '#^' . $pattern . '$#',
            'paramNames' => $paramNames,
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }

    public function dispatch(Request $request): Response
    {
        $method = $request->method();
        $path = $request->path();

        $allowedMethods = [];

        foreach ($this->routes as $route) {
            if (!preg_match($route['pattern'], $path, $matches)) {
                continue;
            }

            if ($route['method'] !== $method) {
                $allowedMethods[] = $route['method'];
                continue;
            }

            array_shift($matches);
            $params = array_combine($route['paramNames'], $matches);

            return $this->runPipeline($route['middleware'], $route['handler'], $request, $params);
        }

        if ($allowedMethods !== []) {
            return Response::html('Metodo no permitido.', 405);
        }

        return Response::html(View::render('errores/404', [], 'layouts/guest'), 404);
    }

    private function runPipeline(array $middleware, callable|array $handler, Request $request, array $params): Response
    {
        $next = function (Request $request) use ($handler, $params): Response {
            if (is_array($handler)) {
                [$class, $action] = $handler;
                $controller = new $class();

                return $controller->$action($request, $params);
            }

            return $handler($request, $params);
        };

        foreach (array_reverse($middleware) as $middlewareEntry) {
            $current = $next;
            $next = function (Request $request) use ($middlewareEntry, $current): Response {
                $instance = is_string($middlewareEntry) ? new $middlewareEntry() : $middlewareEntry();

                return $instance->handle($request, $current);
            };
        }

        return $next($request);
    }
}
