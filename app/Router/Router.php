<?php

declare(strict_types=1);

if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}
require APP_ROOT . '/app/autoload.php';

// Preflight
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// En prod no mostrar errores (evita romper headers con warnings)
if ((getenv('APP_ENV') ?: 'prod') === 'prod') {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}

header('Content-Type: application/json; charset=utf-8');

// Si tenés Router con método handle(), usalo
if (class_exists(\App\Router\Router::class) && method_exists(\App\Router\Router::class, 'handle')) {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
    $path = ltrim($path, '/');
    (new \App\Router\Router())->handle(['path' => $path]);
    exit;
}

// Si no hay Router, devolvé health simple
echo json_encode(['ok' => true, 'service' => 'orquestador']);
exit;
