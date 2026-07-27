<?php

declare(strict_types=1);

namespace App\Shared\Exceptions;

use RuntimeException;
use Throwable;

class HttpException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly int $statusCode = 500,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }
}
