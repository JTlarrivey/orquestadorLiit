<?php

namespace App\Model;

class UserContext
{
    private int $id;
    private string $name;
    private string $role;
    private array $permissions;
    private $jerarquia;

    public function __construct(int $id, string $name, string $role, array $permissions, $jerarquia = null)
    {
        $this->id = $id;
        $this->name = $name;
        $this->role = $role;
        $this->permissions = $permissions;
        $this->jerarquia = $jerarquia;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name ?? 'Invitado';
    }

    public function getRole(): string
    {
        return $this->role ?? 'guest';
    }

    public function getPermissions(): array
    {
        return $this->permissions;
    }

    public function getJerarquia()
    {
        return $this->jerarquia;
    }

    /**
     * Permite reconstruir un contexto desde un array (útil para sesiones)
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'],
            $data['name'] ?? 'Invitado',
            $data['role'] ?? 'guest',
            $data['permissions'] ?? [],
            $data['jerarquia'] ?? null
        );
    }

    /**
     * Permite serializar el contexto a array (útil para guardar en sesión)
     */
    public function toArray(): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'role'        => $this->role,
            'permissions' => $this->permissions,
            'jerarquia'   => $this->jerarquia,
        ];
    }
}
