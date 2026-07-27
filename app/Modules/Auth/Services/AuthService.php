<?php

declare(strict_types=1);

namespace App\Modules\Auth\Services;

use App\Core\Validator;
use App\Modules\Auth\DTOs\LoginData;
use App\Modules\Auth\Models\User;
use App\Shared\Exceptions\AuthenticationException;
use App\Shared\Interfaces\UserRepositoryInterface;

final class AuthService
{
    private const INVALID_CREDENTIALS = 'El correo o la contraseña son incorrectos.';

    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly Validator $validator
    ) {
    }

    /** @param array<string, mixed> $input */
    public function authenticate(array $input): User
    {
        $credentials = new LoginData(
            strtolower(trim((string) ($input['email'] ?? ''))),
            (string) ($input['password'] ?? '')
        );
        $this->validator->validate([
            'email' => $credentials->email,
            'password' => $credentials->password,
        ], [
            'email' => 'required|email|max_length:100',
            'password' => 'required|max_length:4096',
        ]);
        $user = $this->users->findByEmail($credentials->email);

        if (!$user instanceof User || !$this->passwordMatches($credentials->password, $user)) {
            throw new AuthenticationException(self::INVALID_CREDENTIALS);
        }

        return $user;
    }

    private function passwordMatches(string $password, User $user): bool
    {
        if (password_verify($password, $user->passwordHash)) {
            if (password_needs_rehash($user->passwordHash, PASSWORD_DEFAULT)) {
                $this->users->updatePasswordHash($user->id, password_hash($password, PASSWORD_DEFAULT));
            }
            return true;
        }

        $isLegacyPlainText = !str_starts_with($user->passwordHash, '$')
            && hash_equals($user->passwordHash, $password);
        if (!$isLegacyPlainText) {
            return false;
        }

        $this->users->updatePasswordHash($user->id, password_hash($password, PASSWORD_DEFAULT));
        return true;
    }
}
