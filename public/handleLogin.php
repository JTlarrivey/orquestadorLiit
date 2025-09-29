<?php

declare(strict_types=1);
if (!defined('APP_ROOT')) define('APP_ROOT', dirname(__DIR__));
require APP_ROOT . '/app/autoload.php';
header('Content-Type: application/json; charset=utf-8');

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

$raw  = file_get_contents('php://input') ?: '';
$data = json_decode($raw, true);
if (!is_array($data) || !$data) $data = $_POST ?? [];
$email = trim($data['email'] ?? '');
$pass = (string)($data['password'] ?? '');
$role = trim($data['role'] ?? '');
if ($email === '' || $pass === '') {
    http_response_code(400);
    echo json_encode(['valid' => false, 'error' => 'missing_fields']);
    exit;
}

$coreBase = rtrim(cfg('BACKEND_CORE_URL', 'BACKEND_CORE_URL_LOCAL', 'http://core.local'), '/');
$corePath = '/' . ltrim((getenv('CORE_LOGIN_PATH') ?: '/login'), '/');
if ($coreBase === '') {
    http_response_code(500);
    echo json_encode(['valid' => false, 'error' => 'missing_BACKEND_CORE_URL']);
    exit;
}

$payload = ['email' => $email, 'password' => $pass, 'role' => $role];

function call_core($url, $pl, $mode = 'json')
{
    $ch = curl_init($url);
    $hdr = ['Accept: application/json, */*;q=0.8', 'User-Agent: orq-login/1.0'];
    if ($mode === 'json') {
        $body = json_encode($pl);
        $hdr[] = 'Content-Type: application/json';
    } else {
        $body = http_build_query($pl, '', '&');
        $hdr[] = 'Content-Type: application/x-www-form-urlencoded';
    }
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => $hdr,
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 3,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1
    ]);
    $res = curl_exec($ch);
    $err = ($res === false) ? curl_error($ch) : null;
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE) ?: 0;
    curl_close($ch);
    return [$code, $err, $res];
}

list($code, $err, $res) = call_core($coreBase . $corePath, $payload, 'json');
if ($err || $code >= 500 || $code === 415 || trim((string)$res) === '') {
    list($code, $err, $res) = call_core($coreBase . $corePath, $payload, 'form');
}

if ($err) {
    http_response_code(502);
    echo json_encode(['valid' => false, 'error' => 'core_unreachable', 'detail' => $err]);
    exit;
}

$j = json_decode($res ?: '', true);
if ($code === 200 && is_array($j) && isset($j['user_id'])) {
    echo json_encode(['valid' => true, 'user' => [
        'user_id' => $j['id'] ?? $j['user_id'],
        'name' => $j['name'] ?? '',
        'role' => $j['role'] ?? null,
        'permissions' => $j['permissions'] ?? [],
        'layout_pref' => $j['layout_pref'] ?? 'default'
    ]]);
    exit;
}
if ($code === 200 && is_array($j) && ($j['valid'] ?? false) && !empty($j['user'])) {
    echo json_encode(['valid' => true, 'user' => $j['user']]);
    exit;
}

http_response_code($code ?: 500);
echo json_encode(['valid' => false, 'error' => $j['error'] ?? 'login_failed']);
