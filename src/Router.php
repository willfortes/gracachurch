<?php
declare(strict_types=1);

namespace App;

final class Router
{
    private array $routes = [];

    public function get(string $pattern, callable|array $handler): void
    {
        $this->routes[] = ['GET', $pattern, $handler];
    }

    public function post(string $pattern, callable|array $handler): void
    {
        $this->routes[] = ['POST', $pattern, $handler];
    }

    public function dispatch(string $method, string $path): void
    {
        $path = '/' . trim($path, '/');
        $allowed = false;
        foreach ($this->routes as [$m, $pattern, $handler]) {
            $regex = '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>[a-z0-9-]+)', $pattern) . '$#';
            if (!preg_match($regex, $path, $match)) {
                continue;
            }
            $allowed = true;
            if ($m !== $method && !($m === 'GET' && $method === 'HEAD')) {
                continue;
            }
            $params = array_filter($match, 'is_string', ARRAY_FILTER_USE_KEY);
            if (!is_callable($handler)) {
                View::notFound();
            }
            $handler(...$params);
            return;
        }
        if ($allowed) {
            http_response_code(405);
            exit('Método não permitido');
        }
        View::notFound();
    }
}
