<?php

declare(strict_types=1);

namespace App\Shared\Interfaces;

use App\Modules\Produccion\DTOs\FinalizeHarvestData;
use App\Modules\Produccion\Models\Produccion;

interface ProduccionRepositoryInterface
{
    /** @return list<Produccion> */
    public function findAll(): array;
    /** @return list<Produccion> */
    public function findByUser(string $userId): array;
    /** @return array{id_lote:int,estado_cultivo:string,tipo:string}|null */
    public function lockOwnedHarvest(int $loteId, string $userId): ?array;
    public function create(FinalizeHarvestData $data, string $productName): Produccion;
    public function markLoteFinalized(int $loteId, string $harvestDate): bool;
}
