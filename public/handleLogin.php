<?php

declare(strict_types=1);

// --- bootstrap/autoload si lo usás ---
define('APP_ROOT', dirname(__DIR__));
require APP_ROOT . '/app/autoload.php';

header('Content-Type: application/json; charset=utf-8');

// Solo POST y OPTIONS
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}
if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['valid' => false, 'error' => 'method_not_allowed']);
    exit;
}

// Leer input (form o JSON)
$raw = file_get_contents('php://input') ?: '';
$in  = $_POST ?: (json_decode($raw, true) ?? []);
$email = trim($in['email'] ?? '');
$role  = trim($in['role'] ?? '');
$pass  = (string)($in['password'] ?? '');

if ($email === '' || $pass === '') {
    http_response_code(400);
    echo json_encode(['valid' => false, 'error' => 'missing_fields']);
    exit;
}

// URL del Core
$coreBase = rtrim(getenv('BACKEND_CORE_URL') ?: '', '/');
$coreUrl  = $coreBase . '/login';

// cURL al Core
$ch = curl_init($coreUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Accept: application/json'],
    CURLOPT_POSTFIELDS     => json_encode(['email' => $email, 'role' => $role, 'password' => $pass]),
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_CONNECTTIMEOUT => 8,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_MAXREDIRS      => 5,
    CURLOPT_ENCODING       => '',                 // descomprime si viene gzip/brotli
    CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
]);
$coreRes  = curl_exec($ch);
$coreErr  = ($coreRes === false) ? curl_error($ch) : null;
$coreCode = curl_getinfo($ch, CURLINFO_HTTP_CODE) ?: 0;
curl_close($ch);

// Log (solo si APP_ENV != prod)
if ((getenv('APP_ENV') ?: 'prod') !== 'prod') {
    error_log('[orq-login] url=' . $coreUrl . ' code=' . $coreCode . ' len=' . (is_string($coreRes) ? strlen($coreRes) : 0) . ' err=' . ($coreErr ?? ''));
}

if ($coreErr) {
    http_response_code(502);
    // FORZAMOS cuerpo
    $out = json_encode(['valid' => false, 'error' => 'core_unreachable', 'detail' => $coreErr]);
    header('Content-Length: ' . strlen($out));
    echo $out;
    exit;
}

if ($coreCode >= 400) {
    $j = json_decode($coreRes ?: '', true);
    http_response_code($coreCode);
    $out = json_encode(['valid' => false, 'error' => $j['error'] ?? 'login_failed']);
    header('Content-Length: ' . strlen($out));
    echo $out;
    exit;
}

// Parsear y normalizar
$j = json_decode($coreRes ?: '', true);
if (!is_array($j) || !$j) {
    http_response_code(502);
    $out = json_encode(['valid' => false, 'error' => 'empty_or_invalid_core_response']);
    header('Content-Length: ' . strlen($out));
    echo $out;
    exit;
}

// Salida OK normalizada
http_response_code(200);
$out = json_encode([
    'valid' => true,
    'user'  => [
        'user_id'     => $j['user_id']     ?? null,
        'name'        => $j['name']        ?? 'Sin nombre',
        'role'        => $j['role']        ?? null,
        'permissions' => $j['permissions'] ?? [],
        'jerarquia'   => $j['jerarquia']   ?? null,
        'layout_pref' => $j['layout_pref'] ?? 'default',
    ],
]);
// FORZAMOS cuerpo (por tu 200 len=0)
header('Content-Length: ' . strlen($out));
echo $out;
