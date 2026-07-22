<?php

declare(strict_types=1);

namespace Tests\Modules\Lotes;

use App\Core\Validator;
use App\Modules\Lotes\DTOs\CreateLoteData;
use App\Modules\Lotes\Models\Lote;
use App\Modules\Lotes\Services\LoteService;
use App\Shared\Exceptions\ValidationException;
use App\Shared\Interfaces\LoteRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class LoteServiceTest extends TestCase
{
    public function testCreateValidatesOwnershipAndMapsTheCurrentStage(): void
    {
        $repository = new FakeLoteRepository(true);
        $service = new LoteService($repository, new Validator());

        $lote = $service->create('AGR001', [
            'id_cultivo' => '8',
            'ubicacion' => ' Sector Norte ',
            'area' => '2.50',
            'etapa_siembra' => '1',
            'etapa_riego' => '1',
            'fecha_inicio_riego' => '2026-07-01',
            'fecha_fin_riego' => '2026-07-20',
        ]);

        self::assertSame(21, $lote->id);
        self::assertSame('Sector Norte', $repository->created?->ubicacion);
        self::assertSame(2, $repository->created?->etapaActual);
        self::assertSame('activo', $repository->created?->estado);
    }

    public function testCreateRejectsACultivoOwnedByAnotherUser(): void
    {
        $repository = new FakeLoteRepository(false);
        $service = new LoteService($repository, new Validator());

        $this->expectException(ValidationException::class);
        $service->create('AGR001', [
            'id_cultivo' => 99,
            'ubicacion' => 'Sector Sur',
            'area' => 1,
        ]);
    }

    public function testCreateRejectsAnInvalidDateRange(): void
    {
        $repository = new FakeLoteRepository(true);
        $service = new LoteService($repository, new Validator());

        $this->expectException(ValidationException::class);
        $service->create('AGR001', [
            'id_cultivo' => 8,
            'ubicacion' => 'Sector Sur',
            'area' => 1,
            'fecha_inicio_cosecha' => '2026-08-20',
            'fecha_fin_cosecha' => '2026-08-01',
        ]);
    }
}

final class FakeLoteRepository implements LoteRepositoryInterface
{
    public ?CreateLoteData $created = null;

    public function __construct(private readonly bool $ownsCultivo)
    {
    }

    public function findAll(): array { return []; }
    public function findByUser(string $userId): array { return []; }
    public function find(int $id): ?Lote { return null; }
    public function findOwnedBy(int $id, string $userId): ?Lote { return null; }
    public function cultivoBelongsToUser(int $cultivoId, string $userId): bool { return $this->ownsCultivo; }
    public function create(CreateLoteData $data): Lote
    {
        $this->created = $data;
        return new Lote(21, $data->cultivoId, $data->ubicacion, $data->area, $data->etapaActual, $data->estado, $data->dates);
    }
}
