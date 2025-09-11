<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/../converseLarry/config/config.php';


use App\Data\Factory\DataShaperFactory;

// Tipo de dato solicitado
$type = $_GET['target'] ?? '';

if (!$type) {
    http_response_code(400);
    echo json_encode(['error' => 'Falta el parámetro target']);
    exit;
}

// Petición al backend-core
$rawJson = file_get_contents(BACKEND_CORE_URL . $type);
$rawData = json_decode($rawJson, true);

// Aplicar shaper si existe
$shaper = DataShaperFactory::getShaperFor($type);
$shaped = $shaper ? $shaper->shape($rawData) : $rawData;

// Devolver al frontend
header('Content-Type: application/json');
echo json_encode($shaped);
