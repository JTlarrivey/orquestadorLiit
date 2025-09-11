<?php

namespace App\Auth;

use App\Model\UserContext;

class AuthorizationService
{
    private $rolePermissions = [
        'admin' => ['view', 'edit', 'delete'],
        'editor' => ['view', 'edit'],
        'viewer' => ['view']
    ];

    public function authorize(UserContext $user, string $requiredPermission)
    {
        if (!isset($this->rolePermissions[$user->getRole()])) {
            throw new \Exception("Sin permisos para el rol: {$user->getRole()}");
        }

        if (!in_array($requiredPermission, $this->rolePermissions[$user->getRole()])) {
            throw new \Exception("Usuario sin permiso: {$requiredPermission}");
        }

        return true;
    }
}
