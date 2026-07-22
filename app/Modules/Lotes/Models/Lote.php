<?php

declare(strict_types=1);

namespace App\Modules\Lotes\Models;

final readonly class Lote
{
    /** @param array<string, ?string> $dates */
    public function __construct(
        public int $id,
        public int $cultivoId,
        public string $ubicacion,
        public float $area,
        public int $etapaActual,
        public string $estado,
        public array $dates,
        public ?string $cultivo = null,
        public ?string $agricultor = null
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

        return new self(
            (int) $row['id_lote'],
            (int) $row['id_cultivo'],
            (string) $row['ubicacion'],
            (float) $row['area'],
            (int) $row['etapa_actual'],
            (string) ($row['estado_cultivo'] ?? 'activo'),
            $dates,
            isset($row['cultivo']) ? (string) $row['cultivo'] : null,
            isset($row['agricultor']) ? (string) $row['agricultor'] : null
        );
    }

    public function etapaLabel(): string
    {
        return match ($this->etapaActual) {
            1 => 'Siembra',
            2 => 'Desarrollo',
            3 => 'Cosecha',
            default => 'Sin etapa',
        };
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
}
