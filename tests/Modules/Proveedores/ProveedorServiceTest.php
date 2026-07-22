<?php

declare(strict_types=1);

namespace Tests\Modules\Proveedores;

use App\Core\Validator;
use App\Modules\Proveedores\Services\ProveedorService;
use App\Shared\Exceptions\ValidationException;
use App\Shared\Interfaces\ProveedorRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class ProveedorServiceTest extends TestCase
{
    public function testDuplicateProviderIsRejected(): void
    {
        $repository = new FakeProveedorRepository();
        $repository->duplicate = true;
        $this->expectException(ValidationException::class);
        (new ProveedorService($repository, new Validator()))->create(['nombre' => 'Agro Uno', 'ruc_cedula' => '123']);
    }

    public function testProviderWithOrdersCannotBeDeleted(): void
    {
        $repository = new FakeProveedorRepository();
        $repository->orders = 2;
        $this->expectException(ValidationException::class);
        (new ProveedorService($repository, new Validator()))->delete(4);
    }
}

final class FakeProveedorRepository implements ProveedorRepositoryInterface
{
    public bool $duplicate = false;
    public int $orders = 0;
    public function findAll(): array { return []; }
    public function duplicateExists(string $name, string $ruc, ?string $email, ?int $excludeId = null): bool { return $this->duplicate; }
    public function create(string $name, string $ruc, ?string $phone, ?string $email, ?string $address): int { return 4; }
    public function update(int $id, string $name, ?string $phone, ?string $email, ?string $address): bool { return true; }
    public function orderCount(int $id): int { return $this->orders; }
    public function delete(int $id): bool { return true; }
    public function exists(int $id): bool { return true; }
}
