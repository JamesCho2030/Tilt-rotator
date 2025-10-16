<?php
namespace App\Core;

use Closure;
use Exception;

/**
 * Simple Router for RESTful endpoints supporting middleware and dynamic parameters.
 */
class Router
{
    private array $routes = [];

    public function add(string $method, string $path, callable $handler, array $middlewares = []): void
    {
        $method = strtoupper($method);
        $this->routes[$method][] = [
            'path' => $path,
            'handler' => $handler,
            'middlewares' => $middlewares,
            'pattern' => $this->convertPathToRegex($path),
            'parameters' => $this->extractParameters($path),
        ];
    }

    public function dispatch(Request $request): void
    {
        $method = $request->method();
        $uri = rtrim($request->uri(), '/') ?: '/';
        $routeCandidates = $this->routes[$method] ?? [];

        foreach ($routeCandidates as $route) {
            if (preg_match($route['pattern'], $uri, $matches)) {
                $params = [];
                foreach ($route['parameters'] as $index => $name) {
                    $params[$name] = $matches[$index + 1] ?? null;
                }

                $handler = $route['handler'];
                $middlewares = $route['middlewares'];

                $middlewareChain = array_reduce(
                    array_reverse($middlewares),
                    function ($next, $middleware) {
                        return function (Request $request) use ($middleware, $next) {
                            return $middleware->handle($request, $next);
                        };
                    },
                    function (Request $request) use ($handler, $params) {
                        return call_user_func_array($handler, array_merge([$request], array_values($params)));
                    }
                );

                try {
                    $middlewareChain($request);
                } catch (Exception $e) {
                    Response::error($e->getMessage(), 500);
                }
                return;
            }
        }

        Response::error('Route not found', 404);
    }

    private function convertPathToRegex(string $path): string
    {
        $pattern = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '([^/]+)', $path);
        return '#^' . rtrim($pattern, '/') . '$#';
    }

    private function extractParameters(string $path): array
    {
        preg_match_all('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', $path, $matches);
        return $matches[1] ?? [];
    }
}
