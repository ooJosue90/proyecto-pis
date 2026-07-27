<?php

declare(strict_types=1);

namespace App\Shared\Interfaces;

use App\Modules\Plagas\DTOs\CreatePlagaData;
use App\Modules\Plagas\Models\Plaga;

interface PlagaRepositoryInterface
{
    /** @return list<Plaga> */
    public function findAll(): array;
    /** @return list<Plaga> */
    public function findByUser(string $userId): array;
    public function loteBelongsToUser(int $loteId, string $userId): bool;
    public function create(CreatePlagaData $data): Plaga;
}
