<?php

declare(strict_types=1);

namespace Tests\Modules\Cultivos;

use App\Core\Validator;
use App\Modules\Cultivos\DTOs\CreateCultivoData;
use App\Modules\Cultivos\Models\Cultivo;
use App\Modules\Cultivos\Services\CultivoService;
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
            public function create(CreateCultivoData $data): Cultivo
            {
                $this->created = $data;
                return new Cultivo(7, $data->userId, $data->tipo, $data->fechaSiembra);
            }
            public function countLotes(int $id): int { return 0; }
            public function delete(int $id): bool { return true; }
        };
        $service = new CultivoService($repository, new Validator());

        $cultivo = $service->create('AGR001', ['tipo' => '  Mango  ', 'fecha_siembra' => '2026-07-21']);

        self::assertSame(7, $cultivo->id);
        self::assertSame('Mango', $repository->created?->tipo);
        self::assertSame('AGR001', $repository->created?->userId);
    }

    public function testAgricultorListingIsScopedToItsUser(): void
    {
        $repository = new class implements CultivoRepositoryInterface {
            public ?string $requestedUser = null;
            public function findAll(): array { return []; }
            public function findByUser(string $userId): array { $this->requestedUser = $userId; return []; }
            public function find(int $id): ?Cultivo { return null; }
            public function findOwnedBy(int $id, string $userId): ?Cultivo { return null; }
            public function create(CreateCultivoData $data): Cultivo { throw new \LogicException(); }
            public function countLotes(int $id): int { return 0; }
            public function delete(int $id): bool { return true; }
        };
        $service = new CultivoService($repository, new Validator());

        $service->listVisibleTo('AGR002', 'Agricultor');

        self::assertSame('AGR002', $repository->requestedUser);
    }

    public function testDeleteIsBlockedWhenCultivoHasLotes(): void
    {
        $repository = new class implements CultivoRepositoryInterface {
            public bool $deleted = false;
            public function findAll(): array { return []; }
            public function findByUser(string $userId): array { return []; }
            public function find(int $id): ?Cultivo { return new Cultivo($id, 'AGR001', 'Mango', '2026-07-21'); }
            public function findOwnedBy(int $id, string $userId): ?Cultivo { return null; }
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
