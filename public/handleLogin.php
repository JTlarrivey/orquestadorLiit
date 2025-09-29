<?php

declare(strict_types=1);

if (!defined('APP_ROOT')) define('APP_ROOT', dirname(__DIR__));
require APP_ROOT . '/app/autoload.php';

header('Content-Type: application/json; charset=utf-8');

// Preflight
$method = $_SERVER['REQUEST_METHOD'] ?? '';
if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}
if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['valid' => false, 'error' => 'method_not_allowed']);
    exit;
}

// ----- Leer entrada (JSON o form) -----
$raw  = file_get_contents('php://input') ?: '';
$post = $_POST ?: (json_decode($raw, true) ?? []);
$email = trim($post['email'] ?? '');
$pass  = (string)($post['password'] ?? '');
$role  = trim($post['role'] ?? '');

if ($email === '' || $pass === '') {
    http_response_code(400);
    echo json_encode(['valid' => false, 'error' => 'missing_fields']);
    exit;
}

// ----- Resolver Core -----
$coreBase = rtrim(getenv('BACKEND_CORE_URL') ?: '', '/');
if ($coreBase === '') {
    // Falla clara si falta env (evita 500s genéricos)
    http_response_code(500);
    echo json_encode(['valid' => false, 'error' => 'missing_BACKEND_CORE_URL']);
    exit;
}

// Endpoints candidatos del Core (ajustá si usás otro)
$coreEndpoints = ['/login', '/api/login', '/auth/login'];

// ----- Call helper -----
function call_core(string $url, array $payload, string $mode = 'json'): array
{
    $ch = curl_init($url);
    $headers = [
        'Accept: application/json, */*;q=0.8',
        'User-Agent: orq-handleLogin/1.0',
    ];
    if ($mode === 'json') {
        $body = json_encode($payload);
        $headers[] = 'Content-Type: application/json';
    } else {
        $body = http_build_query($payload, '', '&');
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
    }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 3,
        CURLOPT_HEADER         => true,                // capturar headers de respuesta
        CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
    ]);

    $raw   = curl_exec($ch);
    $err   = ($raw === false) ? curl_error($ch) : null;
    $code  = curl_getinfo($ch, CURLINFO_HTTP_CODE) ?: 0;
    $hsize = curl_getinfo($ch, CURLINFO_HEADER_SIZE) ?: 0;
    curl_close($ch);

    $respHeaders = substr($raw ?: '', 0, $hsize);
    $respBody    = substr($raw ?: '', $hsize);

    // Log diagnóstico
    error_log('[orq-login] mode=' . $mode . ' url=' . $url . ' code=' . $code . ' err=' . ($err ?: 'NONE')
        . ' headers=' . str_replace(["\r", "\n"], ['', '|'], substr($respHeaders, 0, 500))
        . ' body=' . substr($respBody ?: '', 0, 1500));

    return [$code, $err, $respBody];
}

// ----- Intentar combinaciones hasta lograr 200 con payload válido -----
$payloads = [
    ['email' => $email, 'password' => $pass, 'role' => $role],
    ['username' => $email, 'password' => $pass], // alternativo común
];
$modes = ['json', 'form'];

$ok = false;
$user = null;
$lastCode = 0;
$lastBody = null;

foreach ($coreEndpoints as $ep) {
    foreach ($payloads as $pl) {
        foreach ($modes as $mode) {
            [$code, $err, $body] = call_core($coreBase . $ep, $pl, $mode);
            $lastCode = $code;
            $lastBody = $body;

            if ($err) continue;
            if ($code !== 200) continue;

            $core = json_decode($body ?: '', true);

            // Caso 1: Core devuelve formato “plano”
            if (is_array($core) && isset($core['user_id'])) {
                $user = [
                    'user_id'     => $core['user_id'],
                    'name'        => $core['name'] ?? 'Sin nombre',
                    'role'        => $core['role'] ?? null,
                    'permissions' => $core['permissions'] ?? [],
                    'jerarquia'   => $core['jerarquia'] ?? null,
                    'layout_pref' => $core['layout_pref'] ?? 'default',
                ];
                $ok = true;
                break 3;
            }

            // Caso 2: Core devuelve {valid:true, user:{...}}
            if (is_array($core) && ($core['valid'] ?? false) && !empty($core['user'])) {
                $user = $core['user'];
                $ok = true;
                break 3;
            }
        }
    }
}

if ($ok) {
    http_response_code(200);
    echo json_encode(['valid' => true, 'user' => $user]);
    exit;
}

// --- Errores claros (propagar códigos útiles en vez de “internal_error”) ---
if ($lastCode >= 400 && $lastCode < 500) {
    http_response_code($lastCode);
    $msg = 'login_failed';
    $j = json_decode($lastBody ?: '', true);
    if (is_array($j) && !empty($j['error'])) $msg = $j['error'];
    echo json_encode(['valid' => false, 'error' => $msg]);
    exit;
}

// Si el Core no respondió o dio 5xx:
http_response_code(($lastCode >= 500) ? $lastCode : 502);
echo json_encode(['valid' => false, 'error' => 'core_error', 'code' => $lastCode]);
