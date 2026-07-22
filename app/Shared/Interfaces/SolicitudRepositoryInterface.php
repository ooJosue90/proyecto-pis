<?php
declare(strict_types=1);
namespace App\Shared\Interfaces;
interface SolicitudRepositoryInterface
{
    /** @return array{solicitudes:list<array<string,mixed>>,stats_solicitudes:array<string,mixed>} */
    public function adminDashboard(): array;
    /** @return list<array<string,mixed>> */
    public function historyByUser(string $userId): array;
    /** @return array<string,mixed>|null */
    public function lockInState(int $id, string $state): ?array;
    public function transition(int $id, string $from, string $to, ?int $insumoId = null): bool;
    public function ownedLoteArea(int $loteId,string $userId): ?float;
    /** @return array{id_insumos:int,nombre:string,tipo:string}|null */
    public function findInsumo(int $id): ?array;
    public function create(string $userId,int $loteId,int $insumoId,string $stage,string $name,float $quantity,?string $observations): int;
}
