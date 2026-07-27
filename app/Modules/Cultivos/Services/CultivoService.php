<?php

declare(strict_types=1);

namespace App\Modules\Cultivos\Services;

use App\Core\Validator;
use App\Modules\Cultivos\DTOs\CreateCultivoData;
use App\Modules\Cultivos\Models\Cultivo;
use App\Shared\Domain\AssociatedCropCatalog;
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
        $data = [
            'nombre' => trim((string) ($input['nombre'] ?? '')),
            'fecha_siembra' => trim((string) ($input['fecha_siembra'] ?? '')),
        ];

        $this->validator->validate($data, [
            'nombre' => 'required|max_length:120',
            'fecha_siembra' => 'required|date|date_min:today',
        ]);
        if ($this->repository->nameExistsForUser($userId, $data['nombre'])) {
            throw new ValidationException([
                'nombre' => ['Ya existe un cultivo con ese nombre. Utilice un identificador diferente.'],
            ]);
        }
        if (!AssociatedCropCatalog::isValidSelection($input['cultivos_asociados'] ?? [])) {
            throw new ValidationException([
                'cultivos_asociados' => ['Seleccione únicamente cultivos asociados disponibles en el catálogo.'],
            ]);
        }
        $associatedCropCodes = AssociatedCropCatalog::normalizeSelection(
            $input['cultivos_asociados'] ?? []
        );

        return $this->repository->create(new CreateCultivoData(
            $userId,
            AssociatedCropCatalog::MAIN_CROP,
            $data['fecha_siembra'],
            $associatedCropCodes,
            $data['nombre']
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
