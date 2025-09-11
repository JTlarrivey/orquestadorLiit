<?php

namespace App\Controller;

use App\Model\UserContext;

class TemplateController
{
    public function getLayout(): array
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        // ✅ Recuperar directamente el UserContext
        $context = $_SESSION['user_context'] ?? null;

        if (!$context || !($context instanceof UserContext)) {
            http_response_code(401);
            echo json_encode(['error' => 'No autorizado']);
            exit;
        }

        $role = $context->getRole();

        return [
            'header'   => "header-{$role}.php",
            'navbar'   => "navbar-{$role}.php",
            'body'     => "body-{$role}.php",
            'rightBar' => "rightBar-{$role}.php",
            'footer'   => "footer-{$role}.php",
        ];
    }
}
