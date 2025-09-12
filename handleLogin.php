<?php

declare(strict_types=1);

// Autoload propio
define('APP_ROOT', dirname(__DIR__));
require APP_ROOT . '/app/autoload.php';

header('Content-Type: application/json; charset=utf-8');

// CORS (por si algún día lo llamás desde el navegador)
$origin  = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true');
    header('Vary: Origin');
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
$in   = $_POST ?: (json_decode($raw, true) ?? []);
$email = trim($in['email'] ?? '');
$role  = trim($in['role'] ?? '');          // opcional, el Core no lo exige
$pass  = (string)($in['password'] ?? '');

if ($email === '' || $pass === '') {
    http_response_code(400);
    echo json_encode(['valid' => false, 'error' => 'missing_fields']);
    exit;
}

// Llamar al CORE por URL
$core = rtrim(getenv('BACKEND_CORE_URL') ?: '', '/'); // ej: https://core-XXXX.onrender.com
$ch = curl_init($core . '/login');                    // tu endpoint del Core (POST JSON)
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS     => json_encode(['email' => $email, 'role' => $role, 'password' => $pass]),
    CURLOPT_TIMEOUT        => 12,
]);
$res  = curl_exec($ch);
$err  = $res === false ? curl_error($ch) : null;
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($err) {
    http_response_code(502);
    echo json_encode(['valid' => false, 'error' => 'core_unreachable', 'detail' => $err]);
    exit;
}

$coreJson = json_decode($res ?: '[]', true);

// 401/errores del Core → propagar como {valid:false}
if ($code >= 400) {
    http_response_code($code);
    echo json_encode(['valid' => false, 'error' => $coreJson['error'] ?? 'login_failed']);
    exit;
}

// 200 OK del Core: VIENE PLANO => envolver en {valid,user}
if (is_array($coreJson) && isset($coreJson['user_id'])) {
    http_response_code(200);
    echo json_encode([
        'valid' => true,
        'user'  => [
            'user_id'     => $coreJson['user_id'],
            'name'        => $coreJson['name'] ?? 'Sin nombre',
            'role'        => $coreJson['role'] ?? null,
            'permissions' => $coreJson['permissions'] ?? [],
            'jerarquia'   => $coreJson['jerarquia'] ?? null,
            'layout_pref' => $coreJson['layout_pref'] ?? 'default',
        ],
    ]);
    exit;
}

// Fallback si el Core cambiara formato
http_response_code(502);
echo json_encode(['valid' => false, 'error' => 'bad_core_response', 'raw' => $coreJson]);
