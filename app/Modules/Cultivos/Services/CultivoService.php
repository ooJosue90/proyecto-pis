<?php

declare(strict_types=1);

namespace App\Modules\Cultivos\Services;

use App\Core\Validator;
use App\Modules\Cultivos\DTOs\CreateCultivoData;
use App\Modules\Cultivos\Models\Cultivo;
use App\Shared\Exceptions\NotFoundException;
use App\Shared\Exceptions\ValidationException;
use App\Shared\Interfaces\CultivoRepositoryInterface;

final class CultivoService
{
    public function __construct(
        private readonly CultivoRepositoryInterface $repository,
        private readonly Validator $validator
    ) {
    }

    /** @return list<Cultivo> */
    public function listVisibleTo(string $userId, string $role): array
    {
        return $role === 'Administrador'
            ? $this->repository->findAll()
            : $this->repository->findByUser($userId);
    }

    public function getVisible(int $id, string $userId, string $role): Cultivo
    {
        $cultivo = $role === 'Administrador'
            ? $this->repository->find($id)
            : $this->repository->findOwnedBy($id, $userId);

        if (!$cultivo instanceof Cultivo) {
            throw new NotFoundException('El cultivo no existe o no pertenece a su cuenta.');
        }

        return $cultivo;
    }

    /** @param array<string, mixed> $input */
    public function create(string $userId, array $input): Cultivo
    {
        $this->validator->validate($input, [
            'tipo' => 'required|max_length:150',
            'fecha_siembra' => 'required|date',
        ]);

        return $this->repository->create(new CreateCultivoData(
            $userId,
            trim((string) $input['tipo']),
            (string) $input['fecha_siembra']
        ));
    }

    public function delete(int $id): void
    {
        if (!$this->repository->find($id) instanceof Cultivo) {
            throw new NotFoundException('El cultivo no existe.');
        }

        $associatedLotes = $this->repository->countLotes($id);
        if ($associatedLotes > 0) {
            throw new ValidationException([
                'cultivo' => ["No se puede eliminar el cultivo porque tiene {$associatedLotes} lote(s) asociado(s)."],
            ]);
        }

        if (!$this->repository->delete($id)) {
            throw new NotFoundException('El cultivo ya no existe.');
        }
    }
}
