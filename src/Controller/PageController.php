<?php

namespace App\Controller;

use App\Auth\AuthenticationMiddleware;
use App\Auth\AuthorizationService;
use App\Template\TemplateResolver;
use Exception;

class PageController
{
    /**
     * Renderiza la vista principal después del login.
     *
     * @param array $request
     * @return void
     */
    public function dashboard(array $request): void
    {
        // 1) La sesión ya debería estar iniciada (session_start en index.php)

        // 2) Autenticación y autorización
        $user = (new AuthenticationMiddleware())->authenticate($request);
        (new AuthorizationService())->authorize($user, 'view_dashboard');

        // 3) Resolver la configuración de layout mediante TemplateResolver
        $templateResolver = new TemplateResolver();
        $layout = $templateResolver->resolveTemplate($user);

        // 4) Guardar configuración de layout en la sesión
        $_SESSION['layout'] = $layout->config;

        // 5) Definir menú de navegación según rol
        $role = $layout->config['role'] ?? $user->role;
        $_SESSION['layout']['partials']['navbar']['items'] = match ($role) {
            'admin'  => ['Dashboard', 'Usuarios', 'Reportes'],
            'viewer' => ['Inicio', 'Mi perfil'],
            default  => ['Inicio'],
        };

        // 6) Preparar datos para la vista (p.ej., rows de una tabla)
        // Aquí podrías llamar a un DataShaper o BackendClient para obtener $rows
        // Por ejemplo:
        // $rows = (new SomeDataShaper())->shape($rawData);
        $rows = [];

        // Guardar filas en la sesión para que los parciales las lean
        $_SESSION['data']['rows'] = $rows;

        // 7) Incluir la vista principal (layout + parciales)
        //    Variables disponibles en la vista: $user, $_SESSION['layout'], $_SESSION['data']
        include __DIR__ . '/../../views/htmlBase.php';
    }
}
