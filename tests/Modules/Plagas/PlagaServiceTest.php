<?php

declare(strict_types=1);

namespace Tests\Modules\Plagas;

use App\Core\Validator;
use App\Modules\Plagas\DTOs\CreatePlagaData;
use App\Modules\Plagas\Models\Plaga;
use App\Modules\Plagas\Services\PlagaService;
use App\Shared\Exceptions\ValidationException;
use App\Shared\Interfaces\PlagaRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class PlagaServiceTest extends TestCase
{
    public function testCreateRequiresAnOwnedLote(): void
    {
        $repository = new FakePlagaRepository(false);
        $service = new PlagaService($repository, new Validator());
        $this->expectException(ValidationException::class);
        $service->create('AGR001', ['id_lote' => 4, 'nombre' => 'Mosca de la fruta']);
    }

    public function testCreateNormalizesAndDelegates(): void
    {
        $repository = new FakePlagaRepository(true);
        $service = new PlagaService($repository, new Validator());
        $service->create('AGR001', ['id_lote' => '4', 'nombre' => '  Ácaros  ']);
        self::assertSame('Ácaros', $repository->created?->nombre);
        self::assertSame('AGR001', $repository->created?->userId);
    }
}

final class FakePlagaRepository implements PlagaRepositoryInterface
{
    public ?CreatePlagaData $created = null;
    public function __construct(private readonly bool $owns) {}
    public function findAll(): array { return []; }
    public function findByUser(string $userId): array { return []; }
    public function loteBelongsToUser(int $loteId, string $userId): bool { return $this->owns; }
    public function create(CreatePlagaData $data): Plaga
    {
        $this->created = $data;
        return new Plaga(1, $data->loteId, $data->userId, $data->nombre, '2026-07-21 10:00:00');
    }
}
