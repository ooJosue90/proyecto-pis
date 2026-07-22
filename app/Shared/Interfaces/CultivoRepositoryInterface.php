<?php

declare(strict_types=1);

namespace App\Shared\Interfaces;

use App\Modules\Cultivos\DTOs\CreateCultivoData;
use App\Modules\Cultivos\Models\Cultivo;

interface CultivoRepositoryInterface
{
    /** @return list<Cultivo> */
    public function findAll(): array;

    /** @return list<Cultivo> */
    public function findByUser(string $userId): array;

    public function find(int $id): ?Cultivo;

    public function findOwnedBy(int $id, string $userId): ?Cultivo;

    public function create(CreateCultivoData $data): Cultivo;

    public function countLotes(int $id): int;

    public function delete(int $id): bool;
}
