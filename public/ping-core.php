<?php
header('Content-Type: application/json');
$url = rtrim(getenv('BACKEND_CORE_URL') ?: '', '/') . '/';
echo json_encode(['core_url' => $url], JSON_PRETTY_PRINT);
