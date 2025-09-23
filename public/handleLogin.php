<?php

declare(strict_types=1);

if (!defined('APP_ROOT')) define('APP_ROOT', dirname(__DIR__));
require APP_ROOT . '/app/autoload.php';

header('Content-Type: application/json; charset=utf-8');

// Preflight
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['valid' => false, 'error' => 'method_not_allowed']);
    exit;
}

// Leer JSON o form
$raw = file_get_contents('php://input') ?: '';
$data = $_POST ?: (json_decode($raw, true) ?? []);
$email = trim($data['email'] ?? '');
$pass  = (string)($data['password'] ?? '');
$role  = trim($data['role'] ?? '');

if ($email === '' || $pass === '') {
    http_response_code(400);
    echo json_encode(['valid' => false, 'error' => 'missing_fields']);
    exit;
}

// Llamar al Core
$coreBase = rtrim(getenv('BACKEND_CORE_URL') ?: 'http://core.local', '/');
$ch = curl_init($coreBase . '/login');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Accept: application/json'],
    CURLOPT_POSTFIELDS     => json_encode(['email' => $email, 'password' => $pass]),
    CURLOPT_TIMEOUT        => 12,
    CURLOPT_CONNECTTIMEOUT => 5,
]);
$res  = curl_exec($ch);
$err  = ($res === false) ? curl_error($ch) : null;
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE) ?: 0;
curl_close($ch);

if ($err) {
    http_response_code(502);
    echo json_encode(['valid' => false, 'error' => 'core_unreachable', 'detail' => $err]);
    exit;
}

$core = json_decode($res ?: '', true);

// Éxito del Core: normalizar a {valid,user}
if ($code === 200 && is_array($core) && isset($core['user_id'])) {
    $user = [
        'user_id'     => $core['user_id'],
        'name'        => $core['name'] ?? 'Sin nombre',
        'role'        => $core['role'] ?? null,
        'permissions' => $core['permissions'] ?? [],
        'jerarquia'   => $core['jerarquia'] ?? null,
        'layout_pref' => $core['layout_pref'] ?? 'default',
    ];
    http_response_code(200);
    echo json_encode(['valid' => true, 'user' => $user]);
    exit;
}

// Cualquier otro caso: propagar error
http_response_code($code ?: 500);
echo json_encode(['valid' => false, 'error' => $core['error'] ?? 'login_failed']);
