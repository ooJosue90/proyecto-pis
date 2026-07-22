<?php

declare(strict_types=1);

namespace App\Modules\Cultivos\DTOs;

final readonly class CreateCultivoData
{
    public function __construct(
        public string $userId,
        public string $tipo,
        public string $fechaSiembra
    ) {
    }
}
