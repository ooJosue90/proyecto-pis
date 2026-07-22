<?php

declare(strict_types=1);

namespace Tests\Modules\Pedidos;

use App\Core\Validator;
use App\Modules\Pedidos\Services\PedidoService;
use App\Shared\Exceptions\ValidationException;
use App\Shared\Interfaces\PedidoRepositoryInterface;
use App\Shared\Interfaces\ProveedorRepositoryInterface;
use App\Shared\Interfaces\TransactionManagerInterface;
use PHPUnit\Framework\TestCase;

final class PedidoServiceTest extends TestCase
{
    public function testNewSupplyAndOrderAreCreatedInOneTransaction(): void
    {
        $orders = new FakePedidoRepository();
        $transactions = new FakePedidoTransactions();
        $service = new PedidoService($orders, new FakePedidoProveedorRepository(), $transactions, new Validator());
        $id = $service->create('ADMIN1', ['id_proveedor' => 2, 'id_usuario' => 'BOD1', 'cantidad' => 4, 'crear_producto_nuevo' => '1', 'nuevo_insumo_nombre' => 'Guantes', 'nuevo_insumo_tipo' => 'Herramientas', 'nuevo_insumo_unidad' => 'par']);
        self::assertSame(18, $id);
        self::assertTrue($transactions->executed);
        self::assertSame(12, $orders->createdSupplyId);
    }

    public function testNonPendingOrderCannotBeCancelled(): void
    {
        $orders = new FakePedidoRepository();
        $orders->canCancel = false;
        $this->expectException(ValidationException::class);
        (new PedidoService($orders, new FakePedidoProveedorRepository(), new FakePedidoTransactions(), new Validator()))->cancel(8);
    }
}

final class FakePedidoTransactions implements TransactionManagerInterface
{
    public bool $executed = false;
    public function transaction(callable $operation): mixed { $this->executed = true; return $operation(); }
}

final class FakePedidoProveedorRepository implements ProveedorRepositoryInterface
{
    public function findAll(): array { return []; }
    public function duplicateExists(string $name, string $ruc, ?string $email, ?int $excludeId = null): bool { return false; }
    public function create(string $name, string $ruc, ?string $phone, ?string $email, ?string $address): int { return 2; }
    public function update(int $id, string $name, ?string $phone, ?string $email, ?string $address): bool { return true; }
    public function orderCount(int $id): int { return 0; }
    public function delete(int $id): bool { return true; }
    public function exists(int $id): bool { return true; }
}

final class FakePedidoRepository implements PedidoRepositoryInterface
{
    public int $createdSupplyId = 0;
    public bool $canCancel = true;
    public function findAll(): array { return []; }
    public function findUsers(): array { return []; }
    public function findSupplies(): array { return []; }
    public function stats(): array { return []; }
    public function userExists(string $id): bool { return true; }
    public function findSupply(int $id): ?array { return ['id_insumos' => $id, 'nombre' => 'Guantes', 'unidad_medida' => 'par']; }
    public function supplyNameExists(string $name): bool { return false; }
    public function createSupply(string $userId, string $name, string $type, string $unit, ?string $observations): int { return $this->createdSupplyId = 12; }
    public function create(int $providerId, string $userId, int $supplyId, string $product, float $quantity, string $unit, ?string $observations): int { return 18; }
    public function update(int $id, int $providerId, string $userId, int $supplyId, string $product, float $quantity, string $unit, ?string $observations): bool { return true; }
    public function cancel(int $id): bool { return $this->canCancel; }
}
