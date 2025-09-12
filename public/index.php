<?php

declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));
require APP_ROOT . '/app/autoload.php';

// --- CORS (ajustá los orígenes permitidos) ---
$origin  = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowed = [
    'https://converselarry.onrender.com',   // front en Render
    // 'http://localhost:5173',              // (opcional) dev local
];

if ($origin && in_array($origin, $allowed, true)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true');   // si usás cookies; si no, podés quitarla
}
header('Access-Control-Allow-Headers: Authorization, Content-Type');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Vary: Origin');

// Preflight
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

use App\Router\Router;

(new Router())->dispatch();
