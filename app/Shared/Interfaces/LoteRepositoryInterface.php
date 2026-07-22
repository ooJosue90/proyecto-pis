<?php

declare(strict_types=1);

namespace App\Shared\Interfaces;

use App\Modules\Lotes\DTOs\CreateLoteData;
use App\Modules\Lotes\Models\Lote;

interface LoteRepositoryInterface
{
    /** @return list<Lote> */
    public function findAll(): array;

    /** @return list<Lote> */
    public function findByUser(string $userId): array;

    public function find(int $id): ?Lote;

    public function findOwnedBy(int $id, string $userId): ?Lote;

    public function cultivoBelongsToUser(int $cultivoId, string $userId): bool;

    public function create(CreateLoteData $data): Lote;
}
