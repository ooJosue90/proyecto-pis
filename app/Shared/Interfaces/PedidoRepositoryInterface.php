<?php

declare(strict_types=1);

namespace App\Shared\Interfaces;

interface PedidoRepositoryInterface
{
    /** @return list<array<string, mixed>> */
    public function findAll(): array;

    /** @return list<array<string, mixed>> */
    public function findUsers(): array;

    /** @return list<array<string, mixed>> */
    public function findSupplies(): array;

    /** @return array<string, int> */
    public function stats(): array;

    public function userExists(string $id): bool;

    /** @return array{id_insumos:int,nombre:string,unidad_medida:string}|null */
    public function findSupply(int $id): ?array;

    public function supplyNameExists(string $name): bool;

    public function createSupply(string $userId, string $name, string $type, string $unit, ?string $observations): int;

    public function create(int $providerId, string $userId, int $supplyId, string $product, float $quantity, string $unit, ?string $observations): int;

    public function update(int $id, int $providerId, string $userId, int $supplyId, string $product, float $quantity, string $unit, ?string $observations): bool;

    public function cancel(int $id): bool;
}
