<?php

declare(strict_types=1);

namespace App\Shared\Exceptions;

use Throwable;

final class ExternalServiceException extends HttpException
{
    public function __construct(string $message = 'El servicio externo no está disponible.', ?Throwable $previous = null)
    {
        parent::__construct($message, 502, $previous);
    }
}
