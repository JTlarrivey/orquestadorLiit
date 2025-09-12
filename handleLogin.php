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
$core = rtrim(getenv('BACKEND_CORE_URL') ?: '', '/');   // p.ej. https://core-xxxx.onrender.com
$url  = $core . '/login';                               // ajustá si tu Core usa otra ruta

$payload = ['email' => $email, 'role' => $role, 'password' => $pass];

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Accept: application/json'],
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_CONNECTTIMEOUT => 8,
    CURLOPT_FOLLOWLOCATION => true,  // sigue 301/302
    CURLOPT_MAXREDIRS      => 5,
    CURLOPT_ENCODING       => '',    // descomprime gzip/deflate/brotli si aplica
]);
$res  = curl_exec($ch);
$err  = ($res === false) ? curl_error($ch) : null;
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE) ?: 0;
curl_close($ch);

// Log en dev (ver en Logs del servicio orquestador)
if ((getenv('APP_ENV') ?: 'prod') !== 'prod') {
    error_log('[orq-login] url=' . $url . ' code=' . $code . ' len=' . (is_string($res) ? strlen($res) : 0) . ' err=' . ($err ?? ''));
}

if ($err) {
    http_response_code(502);
    echo json_encode(['valid' => false, 'error' => 'core_unreachable', 'detail' => $err]);
    exit;
}
if ($code >= 400) {
    $coreErr = json_decode($res ?: '', true);
    http_response_code($code);
    echo json_encode(['valid' => false, 'error' => $coreErr['error'] ?? 'login_failed']);
    exit;
}

$coreJson = json_decode($res ?: '', true);
if (!is_array($coreJson) || !$coreJson) {
    http_response_code(502);
    echo json_encode(['valid' => false, 'error' => 'empty_or_invalid_core_response']);
    exit;
}

// Normalizar SIEMPRE a {valid,user}
http_response_code(200);
echo json_encode([
    'valid' => true,
    'user'  => [
        'user_id'     => $coreJson['user_id']     ?? null,
        'name'        => $coreJson['name']        ?? 'Sin nombre',
        'role'        => $coreJson['role']        ?? null,
        'permissions' => $coreJson['permissions'] ?? [],
        'jerarquia'   => $coreJson['jerarquia']   ?? null,
        'layout_pref' => $coreJson['layout_pref'] ?? 'default',
    ],
]);
