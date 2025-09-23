<?php

declare(strict_types=1);

if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}
require APP_ROOT . '/app/autoload.php';

// CORS básico (ajustá orígenes si querés)

$frontOrigin = cfg('FRONT_ORIGIN_PROD', 'FRONT_ORIGIN_LOCAL', 'http://converse.local');

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true');
}
header('Access-Control-Allow-Headers: Authorization, Content-Type');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Vary: Origin');

// Preflight
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// En prod, errores al log (no a la salida)
if ((getenv('APP_ENV') ?: 'prod') === 'prod') {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}

$router = new \App\Router\Router();

// Si existe dispatch(), usalo; si no, handle()
if (method_exists($router, 'dispatch')) {
    $router->dispatch();
    exit;
}
$router->handle();
exit;
