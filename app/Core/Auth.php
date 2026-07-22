<?php

declare(strict_types=1);

namespace App\Core;

final class Auth
{
    public function __construct(private readonly Session $session)
    {
    }

    public function check(): bool
    {
        return $this->session->has('id_usuario') && $this->session->has('rol');
    }

    public function id(): ?string
    {
        $id = $this->session->get('id_usuario');
        return $id === null ? null : (string) $id;
    }

    public function role(): ?string
    {
        $role = $this->session->get('rol');
        return is_string($role) ? $role : null;
    }

    /** @return array{id_usuario:string,nombre:string,email:string,rol:string}|null */
    public function user(): ?array
    {
        if (!$this->check()) {
            return null;
        }

        return [
            'id_usuario' => (string) $this->session->get('id_usuario'),
            'nombre' => (string) $this->session->get('nombre', ''),
            'email' => (string) $this->session->get('email', ''),
            'rol' => (string) $this->session->get('rol'),
        ];
    }

    /** @param array{id_usuario:mixed,nombre:mixed,email:mixed,rol:mixed} $user */
    public function login(array $user): void
    {
        $this->session->regenerate();
        foreach (['id_usuario', 'nombre', 'email', 'rol'] as $key) {
            $this->session->put($key, (string) $user[$key]);
        }
    }

    public function logout(): void
    {
        $this->session->destroy();
    }
}
