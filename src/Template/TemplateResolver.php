<?php

namespace App\Template;

class TemplateResolver
{
    public function resolveLayoutForRole(string $role): array
    {
        $layouts = [
            'admin' => [
                'header' => 'partials/header-admin.php',
                'navbar' => 'partials/navbar-admin.php',
                'body' => 'partials/body-admin.php',
                'footer' => 'partials/footer-admin.php',
                'rightBar' => 'partials/rightBar-admin.php',
            ],
            'guest' => [
                'body' => 'partials/body-login.php',
            ],
            // otros roles...
        ];

        return $layouts[$role] ?? [];
    }
}
