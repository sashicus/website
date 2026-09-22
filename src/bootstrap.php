<?php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

define('APP_ROOT', dirname(__DIR__));

// ---------- Autoloader ----------
spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $file = APP_ROOT . '/src/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

// ---------- Config ----------
$config = require APP_ROOT . '/src/config.php';

// ---------- Base URL ----------
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
$scriptDir = str_replace('\\', '/', dirname($scriptName));
$base = rtrim($scriptDir === '/' || $scriptDir === '.' ? '' : $scriptDir, '/');

// ---------- Environment ----------
date_default_timezone_set($config['site']['timezone'] ?? 'Europe/Moscow');
mb_internal_encoding('UTF-8');

// ---------- Session (admin auth, CSRF) ----------
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('hwshop_sid');
    @session_start();
}

$app = [
    'config' => $config,
    'base'   => $base,
    'db'     => null,
    'view'   => [],
];

function app(?string $key = null, mixed $default = null): mixed
{
    global $app;
    if ($key === null) {
        return $app;
    }
    return $app[$key] ?? $default;
}

function config(string $key, mixed $default = null): mixed
{
    $value = app('config');
    foreach (explode('.', $key) as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }
        $value = $value[$segment];
    }
    return $value;
}

require APP_ROOT . '/src/functions.php';

$app['db'] = \App\Core\Database::instance($config['db']);

function db(): \App\Core\Database
{
    return app('db');
}

return $app;