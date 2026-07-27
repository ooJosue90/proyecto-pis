<?php

declare(strict_types=1);

namespace App\Shared\Interfaces;

interface ProveedorRepositoryInterface
{
    /** @return list<array<string, mixed>> */
    public function findAll(): array;

    public function duplicateExists(string $name, string $ruc, ?string $email, ?int $excludeId = null): bool;

    public function create(string $name, string $ruc, ?string $phone, ?string $email, ?string $address): int;

    public function update(int $id, string $name, ?string $phone, ?string $email, ?string $address): bool;

    public function orderCount(int $id): int;

    public function delete(int $id): bool;

    public function exists(int $id): bool;
}
