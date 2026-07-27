<?php

declare(strict_types=1);

namespace App\Shared\Exceptions;

final class NotFoundException extends HttpException
{
    public function __construct(string $message = 'El recurso solicitado no existe.')
    {
        parent::__construct($message, 404);
    }
}
