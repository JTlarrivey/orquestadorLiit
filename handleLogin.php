<?php

declare(strict_types=1);

// Autoload propio
define('APP_ROOT', dirname(__DIR__));
require APP_ROOT . '/app/autoload.php';

header('Content-Type: application/json; charset=utf-8');

// CORS opcional si alguna vez lo llamás desde el navegador
$origin  = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true');
}
header('Access-Control-Allow-Headers: Authorization, Content-Type');
header('Access-Control-Allow-Methods: POST, OPTIONS');
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['valid' => false, 'error' => 'method_not_allowed']);
    exit;
}

// Leer POST (form o JSON)
$raw  = file_get_contents('php://input') ?: '';
$data = $_POST ?: (json_decode($raw, true) ?? []);
$email = trim($data['email'] ?? '');
$role  = trim($data['role'] ?? '');
$pass  = (string)($data['password'] ?? '');

if ($email === '' || $pass === '') {
    http_response_code(400);
    echo json_encode(['valid' => false, 'error' => 'missing_fields']);
    exit;
}

// Llamar al CORE por URL
$core = rtrim(getenv('BACKEND_CORE_URL') ?: '', '/'); // ej: https://core-XXXX.onrender.com
$payload = ['email' => $email, 'role' => $role, 'password' => $pass];

$ch = curl_init($core . '/login'); // AJUSTA a la ruta real del Core (ej. /api/login)
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    // Si el Core espera JSON, usa JSON; si espera form, quita HTTPHEADER y usa http_build_query
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_TIMEOUT        => 12,
]);
$res   = curl_exec($ch);
$err   = $res === false ? curl_error($ch) : null;
$code  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($err) {
    http_response_code(502);
    echo json_encode(['valid' => false, 'error' => 'core_unreachable', 'detail' => $err]);
    exit;
}

$resp = json_decode($res ?: '[]', true);
if (!is_array($resp)) {
    http_response_code(502);
    echo json_encode(['valid' => false, 'error' => 'bad_core_response']);
    exit;
}

// Reenviar tal cual (o normalizar a {valid,user,error})
http_response_code($code ?: 200);
echo json_encode($resp);
