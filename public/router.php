<?php

declare(strict_types=1);

/**
 * Router for PHP built-in server: `php -S 127.0.0.1:8090 -t public public/router.php`
 * Serves static files directly and funnels everything else to index.php.
 */

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
$file = __DIR__ . ($uri === '/' ? '/index.php' : $uri);

if ($uri !== '/' && is_file($file)) {
    return false;
}

$_SERVER['PATH_INFO'] = $uri;
require __DIR__ . '/index.php';