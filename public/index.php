<?php

declare(strict_types=1);

// Evitá redefinir APP_ROOT si ya existe
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}
require APP_ROOT . '/app/autoload.php';

// CORS (ajustá orígenes permitidos)
$origin  = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowed = [
    'https://converselarry.onrender.com', // front en Render
    // 'http://localhost:5173',            // opcional, dev local
];
if ($origin && in_array($origin, $allowed, true)) {
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

// En prod, no mostrar errores (que vayan a logs)
if ((getenv('APP_ENV') ?: 'prod') === 'prod') {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}

// Levantá el router y despachá (ahora dispatch() existe)
$router = new \App\Router\Router();
$router->dispatch();
