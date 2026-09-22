<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    /**
     * @param callable(string, array): mixed $handler
     */
    public function __construct(private readonly \Closure $handler)
    {
    }

    /**
     * Parse request path: prefers PATH_INFO (Apache rewrite / nginx try_files),
     * falls back to ?route= for hosts without rewrite rules.
     */
    public static function path(): string
    {
        $info = $_SERVER['PATH_INFO'] ?? $_SERVER['REDIRECT_URL'] ?? '';
        if ($info !== '' && $info !== null) {
            $path = $info;
        } else {
            $script = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
            $script = rtrim(str_replace('\\', '/', $script), '/');
            $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
            if ($uri === $script || $uri === $script . '.php') {
                $path = '/';
            } elseif (str_starts_with($uri, $script . '/')) {
                $path = substr($uri, strlen($script));
            } elseif (str_starts_with($script, $uri) || $uri === '/') {
                $path = '/';
            } else {
                $path = $uri;
            }
        }
        $path = preg_replace('#/+#', '/', $path) ?? '/';
        $route = $_GET['route'] ?? null;
        if (is_string($route) && $route !== '') {
            $path = '/' . trim($route, '/');
        }
        return $path === '' ? '/' : $path;
    }

    public function dispatch(string $method, string $urlPath, array $query): void
    {
        $segments = array_values(array_filter(explode('/', $urlPath), 'strlen'));

        if (count($segments) === 0) {
            ($this->handler)('home', []);
            return;
        }

        ($this->handler)($segments[0], array_slice($segments, 1));
    }
}