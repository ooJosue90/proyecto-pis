<?php

declare(strict_types=1);

namespace App\Modules\Lotes\Models;

use App\Shared\Domain\CultivationStage;

final readonly class Lote
{
    /**
     * @param array<string, ?string> $dates
     * @param array<int, string> $phaseStates
     */
    public function __construct(
        public int $id,
        public int $cultivoId,
        public string $ubicacion,
        public float $area,
        public int $etapaActual,
        public string $estado,
        public array $dates,
        public ?string $cultivo = null,
        public ?string $agricultor = null,
        public array $phaseStates = []
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        $dateFields = [
            'fecha_inicio_riego', 'fecha_fin_riego',
            'fecha_inicio_siembra', 'fecha_fin_siembra',
            'fecha_inicio_cosecha', 'fecha_fin_cosecha',
            'fecha_fin_cosecha_real',
        ];
        $dates = [];
        foreach ($dateFields as $field) {
            $dates[$field] = isset($row[$field]) ? (string) $row[$field] : null;
        }

        $phaseStates = [
            CultivationStage::PLANTING => (string) ($row['estado_fase_siembra']
                ?? CultivationStage::statesForCurrent((int) $row['etapa_actual'])[CultivationStage::PLANTING]),
            CultivationStage::IRRIGATION => (string) ($row['estado_fase_riego']
                ?? CultivationStage::statesForCurrent((int) $row['etapa_actual'])[CultivationStage::IRRIGATION]),
            CultivationStage::HARVEST => (string) ($row['estado_fase_cosecha']
                ?? CultivationStage::statesForCurrent(
                    (int) $row['etapa_actual'],
                    ($row['estado_cultivo'] ?? '') === 'finalizado'
                )[CultivationStage::HARVEST]),
        ];

        return new self(
            (int) $row['id_lote'],
            (int) $row['id_cultivo'],
            (string) $row['ubicacion'],
            (float) $row['area'],
            (int) $row['etapa_actual'],
            (string) ($row['estado_cultivo'] ?? 'activo'),
            $dates,
            isset($row['cultivo']) ? (string) $row['cultivo'] : null,
            isset($row['agricultor']) ? (string) $row['agricultor'] : null,
            $phaseStates
        );
    }

    public function etapaLabel(): string
    {
        return CultivationStage::label($this->etapaActual);
    }

    public function estadoLabel(): string
    {
        return match ($this->estado) {
            'en_cosecha' => 'En cosecha',
            'finalizado' => 'Finalizado',
            'cancelado' => 'Cancelado',
            default => 'Activo',
        };
    }

    public function phaseStatus(int $stage): string
    {
        return $this->phaseStates[$stage] ?? CultivationStage::STATUS_PENDING;
    }
}
