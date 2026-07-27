<?php

declare(strict_types=1);

namespace App\Shared\Interfaces;

interface TransactionManagerInterface
{
    public function transaction(callable $operation): mixed;
}
