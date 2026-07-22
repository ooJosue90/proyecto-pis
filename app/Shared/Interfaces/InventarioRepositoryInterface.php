<?php
declare(strict_types=1);
namespace App\Shared\Interfaces;
interface InventarioRepositoryInterface
{
    /** @return list<array<string,mixed>> */
    public function findAll(): array;
    public function create(string $userId,string $name,string $type,?string $description,string $unit,float $quantity,?string $observations): int;
    /** @return array{id_insumos:int,cantidad:float}|null */
    public function lockByIdOrName(?int $id, string $name): ?array;
    public function decrementStock(int $id, float $quantity): bool;
    public function recordDelivery(int $insumoId, string $userId, int $solicitudId, float $quantity): void;
    public function setStock(int $id,float $quantity): bool;
    public function recordAdjustment(int $id,string $userId,float $change,float $previous,float $new,?string $observations): void;
}
