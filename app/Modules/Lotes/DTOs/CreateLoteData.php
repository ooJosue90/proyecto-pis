<?php

declare(strict_types=1);

namespace App\Modules\Lotes\DTOs;

final readonly class CreateLoteData
{
    /**
     * @param array<string, ?string> $dates
     * @param array<int, string> $phaseStates
     */
    public function __construct(
        public int $cultivoId,
        public string $ubicacion,
        public float $area,
        public int $etapaActual,
        public string $estado,
        public int $etapaRiego,
        public int $etapaSiembra,
        public int $etapaCosecha,
        public array $dates,
        public array $phaseStates
    ) {
    }
}
