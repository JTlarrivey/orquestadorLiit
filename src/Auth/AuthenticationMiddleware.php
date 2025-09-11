<?php

namespace App\Auth;

use App\Model\UserContext;

class AuthenticationMiddleware
{
    /**
     * Comprueba que haya usuario en sesión, o muere con 401.
     */
    public static function requireLogin(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        // ✅ Verificamos instancia válida
        if (
            empty($_SESSION['user_context']) ||
            !($_SESSION['user_context'] instanceof UserContext)
        ) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode([
                'error'   => 'No autenticado',
                'message' => 'Debe iniciar sesión para acceder a este recurso.'
            ]);
            exit;
        }
    }

    /**
     * Devuelve una instancia de UserContext ya almacenada en sesión.
     *
     * @param array $request (no usado actualmente)
     * @return UserContext
     * @throws \Exception si no está autenticado
     */
    public function authenticate(array $request): UserContext
    {

        $context = $_SESSION['user_context'] ?? null;

        if (!$context || !($context instanceof UserContext)) {
            throw new \Exception('Usuario no autenticado.');
        }

        return $context;
    }
}
