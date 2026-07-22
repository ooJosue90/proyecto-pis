<?php

declare(strict_types=1);

namespace App\Modules\Lotes\Services;

use App\Core\Validator;
use App\Modules\Lotes\DTOs\CreateLoteData;
use App\Modules\Lotes\Models\Lote;
use App\Shared\Exceptions\NotFoundException;
use App\Shared\Exceptions\ValidationException;
use App\Shared\Interfaces\LoteRepositoryInterface;

final class LoteService
{
    private const DATE_FIELDS = [
        'fecha_inicio_riego', 'fecha_fin_riego',
        'fecha_inicio_siembra', 'fecha_fin_siembra',
        'fecha_inicio_cosecha', 'fecha_fin_cosecha',
    ];

    public function __construct(
        private readonly LoteRepositoryInterface $repository,
        private readonly Validator $validator
    ) {
    }

    /** @return list<Lote> */
    public function listVisibleTo(string $userId, string $role): array
    {
        return $role === 'Administrador'
            ? $this->repository->findAll()
            : $this->repository->findByUser($userId);
    }

    public function getVisible(int $id, string $userId, string $role): Lote
    {
        $lote = $role === 'Administrador'
            ? $this->repository->find($id)
            : $this->repository->findOwnedBy($id, $userId);

        if (!$lote instanceof Lote) {
            throw new NotFoundException('El lote no existe o no pertenece a su cuenta.');
        }

        return $lote;
    }

    /** @param array<string, mixed> $input */
    public function create(string $userId, array $input): Lote
    {
        $normalized = [
            'id_cultivo' => $input['id_cultivo'] ?? null,
            'ubicacion' => trim((string) ($input['ubicacion'] ?? '')),
            'area' => $input['area'] ?? null,
        ];
        foreach (self::DATE_FIELDS as $field) {
            $value = trim((string) ($input[$field] ?? ''));
            $normalized[$field] = $value === '' ? null : $value;
        }

        $rules = [
            'id_cultivo' => 'required|integer|min:1',
            'ubicacion' => 'required|max_length:200',
            'area' => 'required|numeric|min:0.01',
        ];
        foreach (self::DATE_FIELDS as $field) {
            $rules[$field] = 'date';
        }
        $this->validator->validate($normalized, $rules);

        $cultivoId = (int) $normalized['id_cultivo'];
        if (!$this->repository->cultivoBelongsToUser($cultivoId, $userId)) {
            throw new ValidationException(['id_cultivo' => ['El cultivo seleccionado no pertenece a su cuenta.']]);
        }
        $this->validateDateRange($normalized, 'fecha_inicio_riego', 'fecha_fin_riego', 'riego');
        $this->validateDateRange($normalized, 'fecha_inicio_siembra', 'fecha_fin_siembra', 'siembra');
        $this->validateDateRange($normalized, 'fecha_inicio_cosecha', 'fecha_fin_cosecha', 'cosecha');

        $etapaSiembra = isset($input['etapa_siembra']) ? 1 : 0;
        $etapaRiego = isset($input['etapa_riego']) ? 1 : 0;
        $etapaCosecha = isset($input['etapa_cosecha']) ? 1 : 0;
        $etapaActual = $etapaCosecha === 1 ? 3 : ($etapaRiego === 1 ? 2 : ($etapaSiembra === 1 ? 1 : 0));

        /** @var array<string, ?string> $dates */
        $dates = array_intersect_key($normalized, array_flip(self::DATE_FIELDS));
        return $this->repository->create(new CreateLoteData(
            $cultivoId,
            (string) $normalized['ubicacion'],
            (float) $normalized['area'],
            $etapaActual,
            $etapaActual === 3 ? 'en_cosecha' : 'activo',
            $etapaRiego,
            $etapaSiembra,
            $etapaCosecha,
            $dates
        ));
    }

    /** @param array<string, mixed> $data */
    private function validateDateRange(array $data, string $startField, string $endField, string $label): void
    {
        $start = $data[$startField] ?? null;
        $end = $data[$endField] ?? null;
        if (is_string($start) && is_string($end) && $end < $start) {
            throw new ValidationException([
                $endField => ["La fecha final de {$label} no puede ser anterior a la fecha inicial."],
            ]);
        }
    }
}
