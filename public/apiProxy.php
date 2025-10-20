<?php

/**
 * apiProxy.php
 * -----------------------------------------------------------------------------
 * Endpoint proxy entre converse.local y backend-core.
 * Maneja CORS, enruta requests y aplica DataShaper si corresponde.
 * -----------------------------------------------------------------------------
 */

// --------------------------------------------------------------------------
// 🛡️ CONFIGURACIÓN CORS (debe ir al principio, antes de cualquier output)
// --------------------------------------------------------------------------

$allowed_origins = [
    'http://converse.local',
    'http://master.local',
    'http://orq.local',
    'http://localhost'
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowed_origins, true)) {
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Credentials: true");
    header("Vary: Origin"); // evita conflictos de cache
}

header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Responder preflight OPTIONS de forma temprana
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// --------------------------------------------------------------------------
// ⚙️ AUTOLOAD ARTESANAL Y CONFIGURACIÓN LOCAL
// --------------------------------------------------------------------------

// Ruta base del orquestador (sube un nivel desde /public)
$baseDir = dirname(__DIR__);

// ✅ Autoload artesanal (ahora en /app/)
$autoloadPath = $baseDir . '/app/autoload.php';
if (!file_exists($autoloadPath)) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode([
        'error' => 'No se encontró autoload.php',
        'expected_path' => $autoloadPath
    ], JSON_UNESCAPED_UNICODE);
    exit();
}
require_once $autoloadPath;

// ✅ DataShaperFactory ahora en /src/Data/Factory/
$dataShaperPath = $baseDir . '/src/Data/Factory/DataShaperFactory.php';
if (!file_exists($dataShaperPath)) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode([
        'error' => 'No se encontró DataShaperFactory.php',
        'expected_path' => $dataShaperPath
    ], JSON_UNESCAPED_UNICODE);
    exit();
}
require_once $dataShaperPath;

// Namespace correcto
use App\Data\Factory\DataShaperFactory;

// --------------------------------------------------------------------------
// 📦 CONFIGURACIÓN DEL BACKEND-CORE
// --------------------------------------------------------------------------

if (!defined('BACKEND_CORE_URL_LOCAL')) {
    define('BACKEND_CORE_URL_LOCAL', 'http://core.local/');
}


// --------------------------------------------------------------------------
// 📦 PROCESAMIENTO DE REQUEST
// --------------------------------------------------------------------------

header('Content-Type: application/json; charset=utf-8');

$type = $_GET['target'] ?? '';

if (!$type) {
    http_response_code(400);
    echo json_encode(['error' => 'Falta el parámetro "target".'], JSON_UNESCAPED_UNICODE);
    exit();
}

// Construir URL completa hacia el backend-core
$backendUrl = rtrim(BACKEND_CORE_URL_LOCAL, '/') . '/' . ltrim($type, '/');

// Obtener datos del backend-core (timeout / mejor manejo futuro con cURL)
// @file_get_contents suprime warnings; chequeamos resultado
$rawJson = @file_get_contents($backendUrl);

if ($rawJson === false) {
    http_response_code(502);
    echo json_encode([
        'error' => 'No se pudo conectar con el backend-core.',
        'url'   => $backendUrl
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

$rawData = json_decode($rawJson, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Respuesta JSON inválida del backend-core.',
        'response_sample' => substr($rawJson, 0, 1024) // para debugging sin volcar todo
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// --------------------------------------------------------------------------
// 🧠 APLICAR DATASHAPER (si existe)
// --------------------------------------------------------------------------

$shaper = null;
try {
    if (class_exists('App\\Data\\Factory\\DataShaperFactory')) {
        $shaper = DataShaperFactory::getShaperFor($type);
    }
} catch (Throwable $e) {
    // No rompemos todo por un shaper; lo registramos en la respuesta si ayuda a debug
    // (podés quitar este campo en producción)
    $shaperError = $e->getMessage();
}

$shaped = $shaper ? $shaper->shape($rawData) : $rawData;

// Si hubo error interno en shaper, lo incluimos (solo en desarrollo)
if (!empty($shaperError)) {
    $shaped = [
        'data' => $shaped,
        'shaper_error' => $shaperError
    ];
}

// --------------------------------------------------------------------------
// 📤 RESPUESTA FINAL
// --------------------------------------------------------------------------

http_response_code(200);
echo json_encode($shaped, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
