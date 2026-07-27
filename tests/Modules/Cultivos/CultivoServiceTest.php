<?php

declare(strict_types=1);

namespace Tests\Modules\Cultivos;

use App\Core\Validator;
use App\Modules\Cultivos\DTOs\CreateCultivoData;
use App\Modules\Cultivos\Models\Cultivo;
use App\Modules\Cultivos\Services\CultivoService;
use App\Shared\Domain\AssociatedCropCatalog;
use App\Shared\Exceptions\ValidationException;
use App\Shared\Interfaces\CultivoRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class CultivoServiceTest extends TestCase
{
    public function testCreateDelegatesValidatedDataToTheRepository(): void
    {
        $repository = new class implements CultivoRepositoryInterface {
            public ?CreateCultivoData $created = null;
            public function findAll(): array { return []; }
            public function findByUser(string $userId): array { return []; }
            public function find(int $id): ?Cultivo { return null; }
            public function findOwnedBy(int $id, string $userId): ?Cultivo { return null; }
            public function nameExistsForUser(string $userId, string $name): bool { return false; }
            public function create(CreateCultivoData $data): Cultivo
            {
                $this->created = $data;
                return new Cultivo(7, $data->userId, $data->tipo, $data->fechaSiembra, nombre: $data->nombre);
            }
            public function countLotes(int $id): int { return 0; }
            public function delete(int $id): bool { return true; }
        };
        $service = new CultivoService($repository, new Validator());

        $cultivo = $service->create('AGR001', [
            'tipo' => 'Banano manipulado',
            'nombre' => 'Mango norte',
            'fecha_siembra' => date('Y-m-d'),
            'cultivos_asociados' => ['maiz', 'frijol'],
        ]);

        self::assertSame(7, $cultivo->id);
        self::assertSame(AssociatedCropCatalog::MAIN_CROP, $repository->created?->tipo);
        self::assertSame('AGR001', $repository->created?->userId);
        self::assertSame('Mango norte', $repository->created?->nombre);
        self::assertSame(['maiz', 'frijol'], $repository->created?->associatedCropCodes);
    }

    public function testAgricultorListingIsScopedToItsUser(): void
    {
        $repository = new class implements CultivoRepositoryInterface {
            public ?string $requestedUser = null;
            public function findAll(): array { return []; }
            public function findByUser(string $userId): array { $this->requestedUser = $userId; return []; }
            public function find(int $id): ?Cultivo { return null; }
            public function findOwnedBy(int $id, string $userId): ?Cultivo { return null; }
            public function nameExistsForUser(string $userId, string $name): bool { return false; }
            public function create(CreateCultivoData $data): Cultivo { throw new \LogicException(); }
            public function countLotes(int $id): int { return 0; }
            public function delete(int $id): bool { return true; }
        };
        $service = new CultivoService($repository, new Validator());

        $service->listVisibleTo('AGR002', 'Agricultor');

        self::assertSame('AGR002', $repository->requestedUser);
    }

    public function testCreateAcceptsTodaysPlantingDate(): void
    {
        $repository = new class implements CultivoRepositoryInterface {
            public ?CreateCultivoData $created = null;
            public function findAll(): array { return []; }
            public function findByUser(string $userId): array { return []; }
            public function find(int $id): ?Cultivo { return null; }
            public function findOwnedBy(int $id, string $userId): ?Cultivo { return null; }
            public function nameExistsForUser(string $userId, string $name): bool { return false; }
            public function create(CreateCultivoData $data): Cultivo
            {
                $this->created = $data;
                return new Cultivo(8, $data->userId, $data->tipo, $data->fechaSiembra, nombre: $data->nombre);
            }
            public function countLotes(int $id): int { return 0; }
            public function delete(int $id): bool { return true; }
        };
        $service = new CultivoService($repository, new Validator());

        $today = date('Y-m-d');
        $service->create('AGR001', ['nombre' => 'Mango 2026', 'fecha_siembra' => $today]);

        self::assertSame($today, $repository->created?->fechaSiembra);
    }

    public function testCreateRejectsAHistoricalPlantingDate(): void
    {
        $repository = new class implements CultivoRepositoryInterface {
            public function findAll(): array { return []; }
            public function findByUser(string $userId): array { return []; }
            public function find(int $id): ?Cultivo { return null; }
            public function findOwnedBy(int $id, string $userId): ?Cultivo { return null; }
            public function nameExistsForUser(string $userId, string $name): bool { return false; }
            public function create(CreateCultivoData $data): Cultivo { throw new \LogicException('No debe persistir.'); }
            public function countLotes(int $id): int { return 0; }
            public function delete(int $id): bool { return true; }
        };
        $service = new CultivoService($repository, new Validator());

        $this->expectException(\App\Shared\Exceptions\ValidationException::class);
        $service->create('AGR001', ['nombre' => 'Mango histórico', 'fecha_siembra' => date('Y-m-d', strtotime('-1 day'))]);
    }

    public function testCreateRejectsUnknownAssociatedCrops(): void
    {
        $repository = new class implements CultivoRepositoryInterface {
            public function findAll(): array { return []; }
            public function findByUser(string $userId): array { return []; }
            public function find(int $id): ?Cultivo { return null; }
            public function findOwnedBy(int $id, string $userId): ?Cultivo { return null; }
            public function nameExistsForUser(string $userId, string $name): bool { return false; }
            public function create(CreateCultivoData $data): Cultivo { throw new \LogicException('No debe persistir.'); }
            public function countLotes(int $id): int { return 0; }
            public function delete(int $id): bool { return true; }
        };
        $service = new CultivoService($repository, new Validator());

        $this->expectException(ValidationException::class);
        $service->create('AGR001', [
            'nombre' => 'Mango asociado',
            'fecha_siembra' => date('Y-m-d'),
            'cultivos_asociados' => ['maiz', 'cultivo-inventado'],
        ]);
    }

    public function testCreateRejectsADuplicateDisplayNameForTheSameFarmer(): void
    {
        $repository = new class implements CultivoRepositoryInterface {
            public function findAll(): array { return []; }
            public function findByUser(string $userId): array { return []; }
            public function find(int $id): ?Cultivo { return null; }
            public function findOwnedBy(int $id, string $userId): ?Cultivo { return null; }
            public function nameExistsForUser(string $userId, string $name): bool { return true; }
            public function create(CreateCultivoData $data): Cultivo { throw new \LogicException('No debe persistir.'); }
            public function countLotes(int $id): int { return 0; }
            public function delete(int $id): bool { return true; }
        };
        $service = new CultivoService($repository, new Validator());

        $this->expectException(ValidationException::class);
        $service->create('AGR001', [
            'nombre' => 'Mango norte',
            'fecha_siembra' => date('Y-m-d'),
        ]);
    }

    public function testDeleteIsBlockedWhenCultivoHasLotes(): void
    {
        $repository = new class implements CultivoRepositoryInterface {
            public bool $deleted = false;
            public function findAll(): array { return []; }
            public function findByUser(string $userId): array { return []; }
            public function find(int $id): ?Cultivo { return new Cultivo($id, 'AGR001', 'Mango', '2026-07-21'); }
            public function findOwnedBy(int $id, string $userId): ?Cultivo { return null; }
            public function nameExistsForUser(string $userId, string $name): bool { return false; }
            public function create(CreateCultivoData $data): Cultivo { throw new \LogicException(); }
            public function countLotes(int $id): int { return 2; }
            public function delete(int $id): bool { $this->deleted = true; return true; }
        };
        $service = new CultivoService($repository, new Validator());

        try {
            $service->delete(5);
            self::fail('La eliminación debió rechazarse.');
        } catch (\App\Shared\Exceptions\ValidationException) {
            self::assertFalse($repository->deleted);
        }
    }
}
