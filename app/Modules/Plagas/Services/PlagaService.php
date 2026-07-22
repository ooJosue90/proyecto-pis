<?php

declare(strict_types=1);

namespace App\Modules\Plagas\Services;

use App\Core\Validator;
use App\Modules\Plagas\DTOs\CreatePlagaData;
use App\Modules\Plagas\Models\Plaga;
use App\Shared\Exceptions\ValidationException;
use App\Shared\Interfaces\PlagaRepositoryInterface;

final class PlagaService
{
    public function __construct(private readonly PlagaRepositoryInterface $repository, private readonly Validator $validator)
    {
    }

    /** @return list<Plaga> */
    public function listVisibleTo(string $userId, string $role): array
    {
        return $role === 'Administrador' ? $this->repository->findAll() : $this->repository->findByUser($userId);
    }

    /** @param array<string,mixed> $input */
    public function create(string $userId, array $input): Plaga
    {
        $data = ['id_lote' => $input['id_lote'] ?? null, 'nombre' => trim((string) ($input['nombre'] ?? ''))];
        $this->validator->validate($data, [
            'id_lote' => 'required|integer|min:1',
            'nombre' => 'required|max_length:200',
        ]);
        $loteId = (int) $data['id_lote'];
        if (!$this->repository->loteBelongsToUser($loteId, $userId)) {
            throw new ValidationException(['id_lote' => ['El lote no pertenece a su cuenta.']]);
        }
        return $this->repository->create(new CreatePlagaData($loteId, $userId, (string) $data['nombre']));
    }
}
