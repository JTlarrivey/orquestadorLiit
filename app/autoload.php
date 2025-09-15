<?php

declare(strict_types=1);

if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__)); // /var/www/html
}

spl_autoload_register(function (string $class): void {
    $prefix  = 'App\\';
    $baseDir = APP_ROOT . '/src/';  // ← mapear App\* a src/

    if (strncmp($class, $prefix, strlen($prefix)) !== 0) return;
    $relative = substr($class, strlen($prefix));                // p.ej. Router\Router
    $file     = $baseDir . str_replace('\\', '/', $relative) . '.php'; // src/Router/Router.php
    if (is_file($file)) require $file;
});
