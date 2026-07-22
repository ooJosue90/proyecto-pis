<?php

declare(strict_types=1);

namespace App\Shared\Interfaces;

interface AssistantDataRepositoryInterface
{
    /** @return list<array<string,mixed>> */
    public function context(string $topic,string $role,string $userId,int $limit):array;
}
