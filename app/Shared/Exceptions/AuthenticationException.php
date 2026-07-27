<?php

declare(strict_types=1);

namespace App\Shared\Exceptions;

final class AuthenticationException extends HttpException
{
    public function __construct(string $message = 'Debe iniciar sesión para continuar.')
    {
        parent::__construct($message, 401);
    }
}
