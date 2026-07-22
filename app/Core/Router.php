<?php

declare(strict_types=1);

namespace App\Core;

use App\Shared\Exceptions\NotFoundException;

final class Router
{
    /** @var list<array{method:string,path:string,handler:callable|array,middleware:list<callable>}> */
    private array $routes = [];

    /** @param callable|array $handler @param list<callable> $middleware */
    public function get(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    /** @param callable|array $handler @param list<callable> $middleware */
    public function post(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    /** @param callable|array $handler @param list<callable> $middleware */
    public function put(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->add('PUT', $path, $handler, $middleware);
    }

    /** @param callable|array $handler @param list<callable> $middleware */
    public function delete(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->add('DELETE', $path, $handler, $middleware);
    }

    /** @param callable|array $handler @param list<callable> $middleware */
    public function add(string $method, string $path, callable|array $handler, array $middleware = []): void
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => '/' . trim($path, '/'),
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }

    public function dispatch(Request $request): Response
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $request->method()) {
                continue;
            }

            $parameters = $this->match($route['path'], $request->path());
            if ($parameters === null) {
                continue;
            }

            $request->setRouteParameters($parameters);
            $destination = static fn (Request $current): mixed => call_user_func($route['handler'], $current);

            foreach (array_reverse($route['middleware']) as $middleware) {
                $next = $destination;
                $destination = static fn (Request $current): mixed => $middleware($current, $next);
            }

            $response = $destination($request);
            if (!$response instanceof Response) {
                throw new \RuntimeException('La ruta debe devolver una instancia de Response.');
            }

            return $response;
        }

        throw new NotFoundException();
    }

    /** @return array<string, string>|null */
    private function match(string $routePath, string $requestPath): ?array
    {
        if ($routePath === '/' && $requestPath === '/') {
            return [];
        }

        $names = [];
        $pattern = preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', static function (array $matches) use (&$names): string {
            $names[] = $matches[1];
            return '([^/]+)';
        }, $routePath);

        if ($pattern === null || !preg_match('#^' . $pattern . '$#', $requestPath, $matches)) {
            return null;
        }

        array_shift($matches);
        $values = array_map('rawurldecode', $matches);
        return array_combine($names, $values) ?: [];
    }
}
