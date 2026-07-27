<?php

declare(strict_types=1);

namespace App\Shared\Exceptions;

use Throwable;

final class DatabaseException extends HttpException
{
    public function __construct(string $message = 'No se pudo completar la operación en la base de datos.', ?Throwable $previous = null)
    {
        parent::__construct($message, 500, $previous);
    }
}
