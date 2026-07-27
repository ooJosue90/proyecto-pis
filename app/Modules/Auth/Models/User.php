<?php

declare(strict_types=1);

namespace App\Modules\Auth\Models;

final readonly class User
{
    public function __construct(
        public string $id,
        public string $name,
        public string $email,
        public string $passwordHash,
        public string $role
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self(
            (string) $row['id_usuario'],
            (string) $row['nombre'],
            (string) $row['email'],
            (string) $row['contrasena'],
            (string) $row['rol']
        );
    }

    /** @return array{id_usuario:string,nombre:string,email:string,rol:string} */
    public function sessionData(): array
    {
        return [
            'id_usuario' => $this->id,
            'nombre' => $this->name,
            'email' => $this->email,
            'rol' => $this->role,
        ];
    }
}
