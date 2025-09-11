<?php

namespace App\Router;

use App\Controller\TemplateController;

class Router
{
    public function handle($request)
    {
        $path = $request['path'] ?? '/';
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        switch ("$method $path") {
            case 'GET /api/layout':
                $controller = new TemplateController();
                $layout = $controller->getLayout();

                header('Content-Type: application/json');
                echo json_encode($layout);
                return;

                // Ejemplo adicional: otro endpoint si lo necesitás más adelante
                // case 'GET /api/metrics':
                //     $controller = new MetricsController();
                //     $data = $controller->getMetrics();
                //     header('Content-Type: application/json');
                //     echo json_encode($data);
                //     return;

            default:
                http_response_code(404);
                echo json_encode(['error' => 'Not Found']);
                return;
        }
    }
}
