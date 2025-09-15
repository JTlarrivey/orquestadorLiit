<?php

declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__)); // /var/www/html

spl_autoload_register(function (string $class): void {
    $prefix  = 'App\\';
    $baseDir = APP_ROOT . '/src/';     // <<--- mapea App\* a src/

    $len = strlen($prefix);
    if (strncmp($class, $prefix, $len) !== 0) return;

    $relative = substr($class, $len);                          // p.ej. 'Auth\AuthenticationMiddleware'
    $file     = $baseDir . str_replace('\\', '/', $relative) . '.php'; // src/Auth/AuthenticationMiddleware.php

    if (is_file($file)) require $file;
});
