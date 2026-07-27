<?php

declare(strict_types=1);

namespace App\Shared\Interfaces;

interface FacturaRepositoryInterface
{
    public function schemaReady(): bool;
    /** @return list<array<string,mixed>> */ public function pendingOrders(): array;
    /** @return list<array<string,mixed>> */ public function supplies(): array;
    /** @return list<array<string,mixed>> */ public function recentByUser(string $userId): array;
    /** @return array<string,mixed>|null */ public function lockOrder(int $id): ?array;
    public function invoiceExistsForOrder(int $orderId): bool;
    public function invoiceNumberExists(int $providerId,string $number): bool;
    public function supplyNameExists(string $name): bool;
    public function createSupply(string $userId,string $name,string $type,?string $description,string $unit,?string $observations): int;
    /** @return array<string,mixed>|null */ public function lockSupply(int $id): ?array;
    public function linkOrderSupply(int $orderId,int $supplyId,string $name,string $unit): bool;
    public function createInvoice(int $orderId,int $providerId,string $userId,string $number,string $date,float $total,?string $observations): int;
    public function createDetail(int $invoiceId,int $supplyId,string $name,string $unit,float $quantity,float $unitPrice,float $subtotal): int;
    public function setSupplyStock(int $supplyId,float $stock): bool;
    public function recordInventoryEntry(int $invoiceId,int $detailId,int $supplyId,string $userId,float $quantity,float $previous,float $new,string $observations): void;
    public function recordSupplyEntry(int $supplyId,string $userId,float $quantity,string $observations): void;
    public function markOrderReceived(int $orderId): bool;
    /** @return list<array<string,mixed>> */ public function providers(): array;
    /** @param array<string,mixed> $filters @return list<array<string,mixed>> */ public function findAll(array $filters): array;
    /** @return array<string,mixed> */ public function stats(): array;
    public function review(int $invoiceId,string $status,string $reviewerId): bool;
    /** @return array<string,mixed>|null */ public function findDetailHeader(int $invoiceId): ?array;
    /** @return list<array<string,mixed>> */ public function findDetailItems(int $invoiceId): array;
}
