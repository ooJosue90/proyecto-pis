<?php

declare(strict_types=1);

namespace App\Core;

use App\Shared\Exceptions\ValidationException;

final class Csrf
{
    private const SESSION_KEY = '_csrf_token';

    public function __construct(private readonly Session $session)
    {
    }

    public function token(): string
    {
        $token = $this->session->get(self::SESSION_KEY);
        if (!is_string($token) || strlen($token) < 32) {
            $token = bin2hex(random_bytes(32));
            $this->session->put(self::SESSION_KEY, $token);
        }

        return $token;
    }

    public function field(): string
    {
        return '<input type="hidden" name="_token" value="'
            . htmlspecialchars($this->token(), ENT_QUOTES, 'UTF-8') . '">';
    }

    public function validate(?string $token): void
    {
        $stored = $this->session->get(self::SESSION_KEY);
        if (!is_string($stored) || !is_string($token) || !hash_equals($stored, $token)) {
            throw new ValidationException(['_token' => ['La sesión del formulario expiró. Recargue la página e inténtelo nuevamente.']]);
        }
    }
}
