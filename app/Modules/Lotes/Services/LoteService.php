<?php

declare(strict_types=1);

namespace App\Modules\Lotes\Services;

use App\Core\Validator;
use App\Modules\Lotes\DTOs\CreateLoteData;
use App\Modules\Lotes\Models\Lote;
use App\Shared\Domain\CultivationStage;
use App\Shared\Exceptions\NotFoundException;
use App\Shared\Exceptions\ValidationException;
use App\Shared\Interfaces\LoteRepositoryInterface;

final class LoteService
{
    private const DATE_FIELDS = [
        'fecha_inicio_siembra', 'fecha_fin_siembra',
        'fecha_inicio_riego', 'fecha_fin_riego',
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
        $planningLimit = date('Y-m-d', strtotime('+10 years'));
        foreach (self::DATE_FIELDS as $field) {
            $rules[$field] = "date|date_min:today|date_max:{$planningLimit}";
        }
        $this->validator->validate($normalized, $rules);

        $cultivoId = (int) $normalized['id_cultivo'];
        $plantingDate = $this->repository->findCultivoPlantingDate($cultivoId, $userId);
        if ($plantingDate === null) {
            throw new ValidationException(['id_cultivo' => ['El cultivo seleccionado no pertenece a su cuenta.']]);
        }
        $normalized['fecha_inicio_siembra'] = $plantingDate;
        $this->validateDateRange($normalized, 'fecha_inicio_siembra', 'fecha_fin_siembra', 'siembra');
        $this->validateDateRange($normalized, 'fecha_inicio_riego', 'fecha_fin_riego', 'riego');
        $this->validateDateRange($normalized, 'fecha_inicio_cosecha', 'fecha_fin_cosecha', 'cosecha');
        $this->validateStageChronology(
            $normalized,
            'fecha_inicio_siembra',
            'fecha_fin_siembra',
            'fecha_inicio_cosecha',
            CultivationStage::HARVEST_LABEL
        );
        $this->validateStageChronology(
            $normalized,
            'fecha_inicio_siembra',
            'fecha_fin_siembra',
            'fecha_inicio_riego',
            CultivationStage::IRRIGATION_LABEL
        );
        $this->validateStageChronology(
            $normalized,
            'fecha_inicio_riego',
            'fecha_fin_riego',
            'fecha_inicio_cosecha',
            CultivationStage::HARVEST_LABEL
        );

        $etapaSiembraPlanificada = 1;
        $etapaRiegoPlanificada = isset($input['etapa_riego']) ? 1 : 0;
        $etapaCosechaPlanificada = isset($input['etapa_cosecha']) ? 1 : 0;
        if (!CultivationStage::isSequentialSelection(
            $etapaSiembraPlanificada === 1,
            $etapaRiegoPlanificada === 1,
            $etapaCosechaPlanificada === 1
        )) {
            throw new ValidationException([
                'etapa_actual' => ['Las etapas deben avanzar en orden: Siembra, Riego y Cosecha.'],
            ]);
        }
        $this->validateStageDates(
            $normalized,
            $etapaRiegoPlanificada === 1,
            $etapaCosechaPlanificada === 1
        );

        // Las marcas del formulario representan la planificación de fechas, no el
        // avance operativo. Todo lote nuevo comienza en Siembra y solo cambia de
        // etapa mediante advanceStage(), después de completar la fase actual.
        $etapaActual = CultivationStage::PLANTING;
        /** @var array<string, ?string> $dates */
        $dates = array_intersect_key($normalized, array_flip(self::DATE_FIELDS));
        $phaseStates = CultivationStage::statesForCurrent($etapaActual);
        return $this->repository->create(new CreateLoteData(
            $cultivoId,
            (string) $normalized['ubicacion'],
            (float) $normalized['area'],
            $etapaActual,
            'activo',
            0,
            1,
            0,
            $dates,
            $phaseStates
        ));
    }

    public function advanceStage(int $id, string $userId, string $role, int $requestedStage): Lote
    {
        $lote = $this->getVisible($id, $userId, $role);
        $currentStage = $lote->etapaActual;

        if ($currentStage === CultivationStage::NONE) {
            if ($requestedStage !== CultivationStage::PLANTING) {
                throw new ValidationException([
                    'fase' => ['Debe iniciar la fase Siembra antes de acceder a una fase posterior.'],
                ]);
            }
            $nextStage = CultivationStage::PLANTING;
        } else {
            if ($requestedStage > $currentStage) {
                throw new ValidationException([
                    'fase' => ['No puede avanzar a ' . CultivationStage::label($requestedStage)
                        . ' hasta completar ' . CultivationStage::label($currentStage) . '.'],
                ]);
            }
            if ($requestedStage < $currentStage) {
                throw new ValidationException([
                    'fase' => ['Las fases anteriores están disponibles únicamente para revisión.'],
                ]);
            }
            if ($currentStage === CultivationStage::HARVEST) {
                throw new ValidationException([
                    'fase' => ['Para completar Cosecha debe registrar la producción final del lote.'],
                ]);
            }
            if ($lote->phaseStatus($currentStage) !== CultivationStage::STATUS_IN_PROGRESS) {
                throw new ValidationException([
                    'fase' => ['La fase actual debe estar en progreso antes de completarla.'],
                ]);
            }
            if ($currentStage > CultivationStage::PLANTING
                && $lote->phaseStatus($currentStage - 1) !== CultivationStage::STATUS_COMPLETED) {
                throw new ValidationException([
                    'fase' => ['Debe completar ' . CultivationStage::label($currentStage - 1)
                        . ' antes de avanzar a ' . CultivationStage::label($currentStage) . '.'],
                ]);
            }
            $nextStage = $currentStage + 1;
        }

        $states = CultivationStage::statesForCurrent($nextStage);
        $cropState = $nextStage === CultivationStage::HARVEST ? 'en_cosecha' : 'activo';
        $ownerId = $role === 'Administrador' ? null : $userId;
        if (!$this->repository->advanceStage($id, $ownerId, $currentStage, $nextStage, $states, $cropState)) {
            throw new ValidationException([
                'fase' => ['El lote cambió mientras se actualizaba. Recargue la página e inténtelo nuevamente.'],
            ]);
        }

        return $this->getVisible($id, $userId, $role);
    }

    public function reviewStage(Lote $lote, int $requestedStage): int
    {
        if ($requestedStage === CultivationStage::NONE) {
            return $lote->etapaActual === CultivationStage::NONE
                ? CultivationStage::PLANTING
                : $lote->etapaActual;
        }
        if (!CultivationStage::canAccess($requestedStage, $lote->etapaActual, $lote->phaseStates)) {
            $previous = max(CultivationStage::PLANTING, $requestedStage - 1);
            throw new ValidationException([
                'fase' => ['Debe completar ' . CultivationStage::label($previous)
                    . ' antes de acceder a ' . CultivationStage::label($requestedStage) . '.'],
            ]);
        }

        return $requestedStage;
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

    /** @param array<string, mixed> $data */
    private function validateStageChronology(
        array $data,
        string $previousStartField,
        string $previousEndField,
        string $nextStartField,
        string $nextLabel
    ): void {
        $previousBoundary = $data[$previousEndField] ?? $data[$previousStartField] ?? null;
        $nextStart = $data[$nextStartField] ?? null;

        if (is_string($previousBoundary) && is_string($nextStart) && $nextStart < $previousBoundary) {
            throw new ValidationException([
                $nextStartField => ["La etapa {$nextLabel} no puede comenzar antes de finalizar la etapa anterior."],
            ]);
        }
    }

    /** @param array<string, mixed> $data */
    private function validateStageDates(array $data, bool $irrigationSelected, bool $harvestSelected): void
    {
        if ($irrigationSelected) {
            if (empty($data['fecha_fin_siembra'])) {
                throw new ValidationException([
                    'fecha_fin_siembra' => ['Complete la fecha final de Siembra antes de continuar con Riego.'],
                ]);
            }
            if (empty($data['fecha_inicio_riego'])) {
                throw new ValidationException([
                    'fecha_inicio_riego' => ['Ingrese la fecha inicial de Riego.'],
                ]);
            }
        }

        if ($harvestSelected) {
            if (empty($data['fecha_fin_riego'])) {
                throw new ValidationException([
                    'fecha_fin_riego' => ['Complete la fecha final de Riego antes de continuar con Cosecha.'],
                ]);
            }
            if (empty($data['fecha_inicio_cosecha'])) {
                throw new ValidationException([
                    'fecha_inicio_cosecha' => ['Ingrese la fecha inicial de Cosecha.'],
                ]);
            }
            if (empty($data['fecha_fin_cosecha'])) {
                throw new ValidationException([
                    'fecha_fin_cosecha' => ['Ingrese la fecha final de Cosecha antes de registrar el lote.'],
                ]);
            }
        }
    }
}
