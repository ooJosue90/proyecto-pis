<?php

declare(strict_types=1);

namespace App\Modules\Produccion\Services;

use App\Core\Validator;
use App\Modules\Produccion\DTOs\FinalizeHarvestData;
use App\Modules\Produccion\Models\Produccion;
use App\Shared\Exceptions\ValidationException;
use App\Shared\Interfaces\ProduccionRepositoryInterface;
use App\Shared\Interfaces\TransactionManagerInterface;

final class ProduccionService
{
    public function __construct(
        private readonly ProduccionRepositoryInterface $repository,
        private readonly TransactionManagerInterface $transactions,
        private readonly Validator $validator
    ) {
    }

    /** @return list<Produccion> */
    public function listVisibleTo(string $userId, string $role): array
    {
        return $role === 'Administrador' ? $this->repository->findAll() : $this->repository->findByUser($userId);
    }

    /** @param array<string,mixed> $input */
    public function finalizeHarvest(string $userId, array $input): Produccion
    {
        $values = [
            'id_lote' => $input['id_lote'] ?? null,
            'cantidad_total_kg' => $input['cantidad_total_kg'] ?? null,
            'calidad_primera_kg' => $input['calidad_primera_kg'] ?? 0,
            'calidad_segunda_kg' => $input['calidad_segunda_kg'] ?? 0,
            'descarte_kg' => $input['descarte_kg'] ?? 0,
            'fecha_cosecha' => trim((string) ($input['fecha_cosecha'] ?? '')),
            'observaciones' => trim((string) ($input['observaciones'] ?? '')),
        ];
        $this->validator->validate($values, [
            'id_lote' => 'required|integer|min:1',
            'cantidad_total_kg' => 'required|decimal:2|min:0.01|max:10000000',
            'calidad_primera_kg' => 'required|decimal:2|min:0|max:10000000',
            'calidad_segunda_kg' => 'required|decimal:2|min:0|max:10000000',
            'descarte_kg' => 'required|decimal:2|min:0|max:10000000',
            'fecha_cosecha' => 'required|date|date_min:today|date_max:today',
            'observaciones' => 'max_length:2000',
        ]);

        $total = (float) $values['cantidad_total_kg'];
        $classified = (float) $values['calidad_primera_kg'] + (float) $values['calidad_segunda_kg'] + (float) $values['descarte_kg'];
        if ($classified > $total + 0.001) {
            throw new ValidationException(['clasificacion' => ['La suma de calidades y descarte no puede superar la cantidad total.']]);
        }

        $data = new FinalizeHarvestData(
            $userId, (int) $values['id_lote'], $total,
            (float) $values['calidad_primera_kg'], (float) $values['calidad_segunda_kg'],
            (float) $values['descarte_kg'], (string) $values['fecha_cosecha'],
            $values['observaciones'] === '' ? null : (string) $values['observaciones']
        );

        return $this->transactions->transaction(function () use ($data): Produccion {
            $lote = $this->repository->lockOwnedHarvest($data->loteId, $data->userId);
            if ($lote === null || $lote['estado_cultivo'] !== 'en_cosecha') {
                throw new ValidationException(['id_lote' => ['El lote no está disponible para finalizar su cosecha.']]);
            }
            $harvestStart = $lote['fecha_inicio_cosecha'] ?? null;
            if (is_string($harvestStart) && $harvestStart !== '' && $data->harvestDate < $harvestStart) {
                throw new ValidationException([
                    'fecha_cosecha' => ['La fecha real de cosecha no puede ser anterior al inicio de cosecha del lote.'],
                ]);
            }
            $production = $this->repository->create($data, $lote['tipo']);
            if (!$this->repository->markLoteFinalized($data->loteId, $data->harvestDate)) {
                throw new ValidationException(['id_lote' => ['El lote cambió de estado durante la operación.']]);
            }
            return $production;
        });
    }
}
