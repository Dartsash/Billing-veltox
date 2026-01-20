<?php

declare(strict_types=1);

final class App
{
    /** @var array<string, array<string, callable>> */
    private array $routes = [
        'GET' => [],
        'POST' => [],
    ];

    public function get(string $path, callable $handler): void
    {
        $this->routes['GET'][$path] = $handler;
    }

    public function post(string $path, callable $handler): void
    {
        $this->routes['POST'][$path] = $handler;
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $method = strtoupper($method);
        $requestPath = path();

        $handler = $this->routes[$method][$requestPath] ?? null;
        if (!$handler) {
            http_response_code(404);
            echo '404 Not Found';
            return;
        }

        $handler();
    }
}
