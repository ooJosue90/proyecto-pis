<?php

declare(strict_types=1);

namespace Tests\Modules\Produccion;

use App\Core\Validator;
use App\Modules\Produccion\DTOs\FinalizeHarvestData;
use App\Modules\Produccion\Models\Produccion;
use App\Modules\Produccion\Services\ProduccionService;
use App\Shared\Exceptions\ValidationException;
use App\Shared\Interfaces\ProduccionRepositoryInterface;
use App\Shared\Interfaces\TransactionManagerInterface;
use PHPUnit\Framework\TestCase;

final class ProduccionServiceTest extends TestCase
{
    public function testFinalizeHarvestRunsTheAtomicFlow(): void
    {
        $repository = new FakeProduccionRepository('en_cosecha');
        $transactions = new FakeTransactions();
        $service = new ProduccionService($repository, $transactions, new Validator());

        $production = $service->finalizeHarvest('AGR001', [
            'id_lote' => 7, 'cantidad_total_kg' => 100,
            'calidad_primera_kg' => 70, 'calidad_segunda_kg' => 20, 'descarte_kg' => 10,
            'fecha_cosecha' => '2026-07-21', 'observaciones' => 'Cosecha seca',
        ]);

        self::assertTrue($transactions->executed);
        self::assertTrue($repository->finalized);
        self::assertSame(100.0, $production->quantity);
        self::assertStringContainsString('primera 70.00 kg', (string) $production->observations);
    }

    public function testFinalizeRejectsClassificationAboveTotal(): void
    {
        $service = new ProduccionService(new FakeProduccionRepository('en_cosecha'), new FakeTransactions(), new Validator());
        $this->expectException(ValidationException::class);
        $service->finalizeHarvest('AGR001', [
            'id_lote' => 7, 'cantidad_total_kg' => 50,
            'calidad_primera_kg' => 40, 'calidad_segunda_kg' => 20, 'descarte_kg' => 0,
            'fecha_cosecha' => '2026-07-21',
        ]);
    }

    public function testFinalizeRejectsALoteOutsideHarvestState(): void
    {
        $service = new ProduccionService(new FakeProduccionRepository('activo'), new FakeTransactions(), new Validator());
        $this->expectException(ValidationException::class);
        $service->finalizeHarvest('AGR001', [
            'id_lote' => 7, 'cantidad_total_kg' => 50,
            'calidad_primera_kg' => 0, 'calidad_segunda_kg' => 0, 'descarte_kg' => 0,
            'fecha_cosecha' => '2026-07-21',
        ]);
    }
}

final class FakeTransactions implements TransactionManagerInterface
{
    public bool $executed = false;
    public function transaction(callable $operation): mixed { $this->executed = true; return $operation(); }
}

final class FakeProduccionRepository implements ProduccionRepositoryInterface
{
    public bool $finalized = false;
    public function __construct(private readonly string $state) {}
    public function findAll(): array { return []; }
    public function findByUser(string $userId): array { return []; }
    public function lockOwnedHarvest(int $loteId, string $userId): ?array { return ['id_lote' => $loteId, 'estado_cultivo' => $this->state, 'tipo' => 'Mango']; }
    public function create(FinalizeHarvestData $data, string $productName): Produccion
    {
        return new Produccion(1, $data->userId, $data->loteId, $productName, $data->quantityKg, 'kg', $data->formattedObservations(), $data->harvestDate);
    }
    public function markLoteFinalized(int $loteId, string $harvestDate): bool { $this->finalized = true; return true; }
}
