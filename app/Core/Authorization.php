<?php

declare(strict_types=1);

namespace App\Core;

use App\Shared\Exceptions\AuthorizationException;

final class Authorization
{
    /** @param array<string, list<string>> $permissions */
    public function __construct(
        private readonly Auth $auth,
        private readonly array $permissions
    ) {
    }

    public function can(string $permission): bool
    {
        $role = $this->auth->role();
        if ($role === null) {
            return false;
        }

        $allowed = $this->permissions[$role] ?? [];
        if (in_array('*', $allowed, true) || in_array($permission, $allowed, true)) {
            return true;
        }

        foreach ($allowed as $candidate) {
            if (str_ends_with($candidate, '.*')
                && str_starts_with($permission, substr($candidate, 0, -1))) {
                return true;
            }
        }

        return false;
    }

    public function authorize(string $permission): void
    {
        if (!$this->can($permission)) {
            throw new AuthorizationException();
        }
    }
}
