<?php

declare(strict_types=1);

namespace App\Shared\Interfaces;

use App\Modules\Auth\Models\User;

interface UserRepositoryInterface
{
    public function findByEmail(string $email): ?User;

    public function updatePasswordHash(string $userId, string $passwordHash): void;

    public function createPasswordResetNotification(string $name, string $email): void;

    public function findAdministratorEmail(): ?string;
}
