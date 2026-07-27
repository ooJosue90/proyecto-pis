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
    public function testCreateValidatesOwnershipAndAlwaysStartsInPlanting(): void
    {
        $repository = new FakeLoteRepository(true);
        $service = new LoteService($repository, new Validator());

        $lote = $service->create('AGR001', [
            'id_cultivo' => '8',
            'ubicacion' => ' Sector Norte ',
            'area' => '2.50',
            'etapa_siembra' => '1',
            'etapa_riego' => '1',
            'fecha_fin_siembra' => date('Y-m-d'),
            'fecha_inicio_riego' => date('Y-m-d'),
            'fecha_fin_riego' => date('Y-m-d', strtotime('+1 day')),
        ]);

        self::assertSame(21, $lote->id);
        self::assertSame('Sector Norte', $repository->created?->ubicacion);
        self::assertSame(1, $repository->created?->etapaActual);
        self::assertSame('activo', $repository->created?->estado);
        self::assertSame(1, $repository->created?->etapaSiembra);
        self::assertSame(0, $repository->created?->etapaRiego);
        self::assertSame(0, $repository->created?->etapaCosecha);
        self::assertSame(
            \App\Shared\Domain\CultivationStage::STATUS_IN_PROGRESS,
            $repository->created?->phaseStates[\App\Shared\Domain\CultivationStage::PLANTING]
        );
        self::assertSame(
            \App\Shared\Domain\CultivationStage::STATUS_PENDING,
            $repository->created?->phaseStates[\App\Shared\Domain\CultivationStage::HARVEST]
        );
        self::assertSame(date('Y-m-d'), $repository->created?->dates['fecha_inicio_siembra']);
    }

    public function testCreateKeepsACompleteScheduleWithoutAdvancingToHarvest(): void
    {
        $repository = new FakeLoteRepository(true);
        $service = new LoteService($repository, new Validator());

        $lote = $service->create('AGR001', [
            'id_cultivo' => '8',
            'ubicacion' => 'Hacienda de prueba',
            'area' => '3',
            'etapa_siembra' => '1',
            'etapa_riego' => '1',
            'etapa_cosecha' => '1',
            'fecha_fin_siembra' => date('Y-m-d', strtotime('+1 day')),
            'fecha_inicio_riego' => date('Y-m-d', strtotime('+2 days')),
            'fecha_fin_riego' => date('Y-m-d', strtotime('+3 days')),
            'fecha_inicio_cosecha' => date('Y-m-d', strtotime('+4 days')),
            'fecha_fin_cosecha' => date('Y-m-d', strtotime('+5 days')),
        ]);

        self::assertSame(
            \App\Shared\Domain\CultivationStage::PLANTING,
            $lote->etapaActual
        );
        self::assertSame('activo', $lote->estado);
        self::assertSame(0, $repository->created?->etapaRiego);
        self::assertSame(0, $repository->created?->etapaCosecha);
        self::assertSame(
            date('Y-m-d', strtotime('+4 days')),
            $repository->created?->dates['fecha_inicio_cosecha']
        );
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
            'fecha_inicio_cosecha' => date('Y-m-d', strtotime('+20 days')),
            'fecha_fin_cosecha' => date('Y-m-d', strtotime('+10 days')),
        ]);
    }

    public function testCreateRejectsSkippedStages(): void
    {
        $service = new LoteService(new FakeLoteRepository(true), new Validator());

        $this->expectException(ValidationException::class);
        $service->create('AGR001', [
            'id_cultivo' => 8,
            'ubicacion' => 'Sector Este',
            'area' => 1,
            'etapa_cosecha' => '1',
        ]);
    }

    public function testCreateRejectsIrrigationUntilPlantingHasAnEndDate(): void
    {
        $service = new LoteService(new FakeLoteRepository(true), new Validator());

        $this->expectException(ValidationException::class);
        $service->create('AGR001', [
            'id_cultivo' => 8,
            'ubicacion' => 'Sector Este',
            'area' => 1,
            'etapa_riego' => '1',
            'fecha_inicio_riego' => date('Y-m-d', strtotime('+1 day')),
        ]);
    }

    public function testCreateRejectsAStageThatStartsBeforeThePreviousOneEnds(): void
    {
        $service = new LoteService(new FakeLoteRepository(true), new Validator());

        $this->expectException(ValidationException::class);
        $service->create('AGR001', [
            'id_cultivo' => 8,
            'ubicacion' => 'Sector Este',
            'area' => 1,
            'etapa_siembra' => '1',
            'etapa_riego' => '1',
            'fecha_fin_siembra' => date('Y-m-d', strtotime('+10 days')),
            'fecha_inicio_riego' => date('Y-m-d', strtotime('+5 days')),
        ]);
    }

    public function testCreateRejectsHarvestWithoutAnEndDate(): void
    {
        $repository = new FakeLoteRepository(true);
        $service = new LoteService($repository, new Validator());

        try {
            $service->create('AGR001', [
                'id_cultivo' => 8,
                'ubicacion' => 'Mango 2',
                'area' => 1,
                'etapa_siembra' => '1',
                'etapa_riego' => '1',
                'etapa_cosecha' => '1',
                'fecha_fin_siembra' => date('Y-m-d', strtotime('+1 day')),
                'fecha_inicio_riego' => date('Y-m-d', strtotime('+2 days')),
                'fecha_fin_riego' => date('Y-m-d', strtotime('+3 days')),
                'fecha_inicio_cosecha' => date('Y-m-d', strtotime('+4 days')),
            ]);
            self::fail('El lote no debe registrarse sin la fecha final de Cosecha.');
        } catch (ValidationException $exception) {
            self::assertSame(
                ['Ingrese la fecha final de Cosecha antes de registrar el lote.'],
                $exception->errors()['fecha_fin_cosecha'] ?? []
            );
            self::assertNull($repository->created);
        }
    }

    public function testAdvanceCompletesCurrentStageBeforeOpeningTheNextOne(): void
    {
        $states = \App\Shared\Domain\CultivationStage::statesForCurrent(
            \App\Shared\Domain\CultivationStage::PLANTING
        );
        $repository = new FakeLoteRepository(true, new Lote(
            21, 8, 'Sector Norte', 2.5,
            \App\Shared\Domain\CultivationStage::PLANTING,
            'activo',
            [],
            'Mango',
            'Agricultor',
            $states
        ));
        $service = new LoteService($repository, new Validator());

        $updated = $service->advanceStage(
            21,
            'AGR001',
            'Agricultor',
            \App\Shared\Domain\CultivationStage::PLANTING
        );

        self::assertTrue($repository->advanced);
        self::assertSame(\App\Shared\Domain\CultivationStage::IRRIGATION, $updated->etapaActual);
        self::assertSame(
            \App\Shared\Domain\CultivationStage::STATUS_COMPLETED,
            $updated->phaseStatus(\App\Shared\Domain\CultivationStage::PLANTING)
        );
        self::assertSame(
            \App\Shared\Domain\CultivationStage::STATUS_IN_PROGRESS,
            $updated->phaseStatus(\App\Shared\Domain\CultivationStage::IRRIGATION)
        );
    }

    public function testAdvanceRejectsSkippingTheCurrentStage(): void
    {
        $states = \App\Shared\Domain\CultivationStage::statesForCurrent(
            \App\Shared\Domain\CultivationStage::PLANTING
        );
        $repository = new FakeLoteRepository(true, new Lote(
            21, 8, 'Sector Norte', 2.5,
            \App\Shared\Domain\CultivationStage::PLANTING,
            'activo',
            [],
            phaseStates: $states
        ));
        $service = new LoteService($repository, new Validator());

        $this->expectException(ValidationException::class);
        $service->advanceStage(
            21,
            'AGR001',
            'Agricultor',
            \App\Shared\Domain\CultivationStage::HARVEST
        );
    }

    public function testReviewAllowsPastStagesButRejectsFutureStages(): void
    {
        $states = \App\Shared\Domain\CultivationStage::statesForCurrent(
            \App\Shared\Domain\CultivationStage::IRRIGATION
        );
        $lote = new Lote(
            21, 8, 'Sector Norte', 2.5,
            \App\Shared\Domain\CultivationStage::IRRIGATION,
            'activo',
            [],
            phaseStates: $states
        );
        $service = new LoteService(new FakeLoteRepository(true, $lote), new Validator());

        self::assertSame(
            \App\Shared\Domain\CultivationStage::PLANTING,
            $service->reviewStage($lote, \App\Shared\Domain\CultivationStage::PLANTING)
        );

        $this->expectException(ValidationException::class);
        $service->reviewStage($lote, \App\Shared\Domain\CultivationStage::HARVEST);
    }
}

final class FakeLoteRepository implements LoteRepositoryInterface
{
    public ?CreateLoteData $created = null;
    public bool $advanced = false;

    public function __construct(private readonly bool $ownsCultivo, private ?Lote $visible = null)
    {
    }

    public function findAll(): array { return []; }
    public function findByUser(string $userId): array { return []; }
    public function find(int $id): ?Lote { return $this->visible; }
    public function findOwnedBy(int $id, string $userId): ?Lote { return $this->visible; }
    public function findCultivoPlantingDate(int $cultivoId, string $userId): ?string
    {
        return $this->ownsCultivo ? date('Y-m-d') : null;
    }
    public function create(CreateLoteData $data): Lote
    {
        $this->created = $data;
        return new Lote(
            21,
            $data->cultivoId,
            $data->ubicacion,
            $data->area,
            $data->etapaActual,
            $data->estado,
            $data->dates,
            phaseStates: $data->phaseStates
        );
    }
    public function advanceStage(int $id, ?string $ownerId, int $expectedCurrentStage, int $nextStage, array $phaseStates, string $cropState): bool
    {
        if (!$this->visible || $this->visible->etapaActual !== $expectedCurrentStage) {
            return false;
        }
        $this->advanced = true;
        $this->visible = new Lote(
            $this->visible->id,
            $this->visible->cultivoId,
            $this->visible->ubicacion,
            $this->visible->area,
            $nextStage,
            $cropState,
            $this->visible->dates,
            $this->visible->cultivo,
            $this->visible->agricultor,
            $phaseStates
        );
        return true;
    }
}
