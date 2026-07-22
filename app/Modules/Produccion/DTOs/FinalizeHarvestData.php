<?php

declare(strict_types=1);

namespace App\Modules\Produccion\DTOs;

final readonly class FinalizeHarvestData
{
    public function __construct(
        public string $userId,
        public int $loteId,
        public float $quantityKg,
        public float $firstQualityKg,
        public float $secondQualityKg,
        public float $discardKg,
        public string $harvestDate,
        public ?string $observations
    ) {
    }

    public function formattedObservations(): ?string
    {
        $classification = $this->firstQualityKg + $this->secondQualityKg + $this->discardKg;
        $parts = [];
        if ($classification > 0) {
            $parts[] = sprintf(
                'Clasificación: primera %.2f kg; segunda %.2f kg; descarte %.2f kg.',
                $this->firstQualityKg,
                $this->secondQualityKg,
                $this->discardKg
            );
        }
        if ($this->observations !== null) {
            $parts[] = $this->observations;
        }
        return $parts === [] ? null : implode(' ', $parts);
    }
}
