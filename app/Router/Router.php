<?php

declare(strict_types=1);

namespace App\Router;

final class Router
{
    /**
     * Tu router real: resuelve rutas y responde.
     */
    public function handle(array $req = []): void
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $uri    = $_SERVER['REQUEST_URI'] ?? '/';
        $path   = trim(parse_url($uri, PHP_URL_PATH) ?? '/', '/'); // '' si raíz

        header('Content-Type: application/json; charset=utf-8');

        if ($method === 'OPTIONS') {
            http_response_code(204);
            return;
        }

        $key = $method . ' ' . ($path === '' ? '/' : $path);

        switch ($key) {
            case 'GET /':
            case 'GET health':
            case 'GET healthz':
                echo json_encode(['ok' => true, 'service' => 'orquestador']);
                return;

                // acá podés agregar endpoints del orquestador si los necesitás

            default:
                http_response_code(404);
                echo json_encode(['error' => 'Ruta no encontrada', 'path' => ($path === '' ? '/' : $path)]);
                return;
        }
    }

    /**
     * Compatibilidad: si alguien llama dispatch(), delega a handle().
     */
    public function dispatch(): void
    {
        $this->handle();
    }
}
