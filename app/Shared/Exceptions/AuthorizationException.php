<?php

declare(strict_types=1);

namespace App\Shared\Exceptions;

final class AuthorizationException extends HttpException
{
    public function __construct(string $message = 'No tiene permisos para realizar esta acción.')
    {
        parent::__construct($message, 403);
    }
}
