<?php

declare(strict_types=1);

namespace App\Router;

final class Router
{
    /**
     * Router principal (tu implementación actual).
     * Podés agregar más rutas acá si las necesitás.
     */
    public function handle(array $req = []): void
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $uri    = $_SERVER['REQUEST_URI'] ?? '/';
        $path   = trim(parse_url($uri, PHP_URL_PATH) ?? '/', '/'); // '' si es raíz

        header('Content-Type: application/json; charset=utf-8');

        if ($method === 'OPTIONS') {
            http_response_code(204);
            return;
        }

        $key = $method . ' ' . ($path === '' ? '/' : $path);

        switch ($key) {
            // Health / raíz
            case 'GET /':
            case 'GET health':
            case 'GET healthz':
                echo json_encode(['ok' => true, 'service' => 'orquestador']);
                return;

                // acá podrías agregar más endpoints si querés
                // case 'POST algo':
                //   ...
                //   return;

            default:
                http_response_code(404);
                echo json_encode(['error' => 'Ruta no encontrada', 'path' => ($path === '' ? '/' : $path)]);
                return;
        }
    }

    /**
     * Shim: deja vivo el código que llama a dispatch().
     * Internamente solo delega a handle().
     */
    public function dispatch(): void
    {
        $this->handle();
    }
}
