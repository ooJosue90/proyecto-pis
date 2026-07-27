<?php

declare(strict_types=1);

namespace App\Shared\Exceptions;

final class ValidationException extends HttpException
{
    /** @param array<string, list<string>> $errors */
    public function __construct(
        private readonly array $errors,
        string $message = 'Revise los datos ingresados.'
    ) {
        parent::__construct($message, 422);
    }

    /** @return array<string, list<string>> */
    public function errors(): array
    {
        return $this->errors;
    }
}
