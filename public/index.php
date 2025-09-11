<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Router\Router;

// Extraer la path sin query params
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$router = new Router();
$router->handle(['path' => $path]);
