<?php

declare(strict_types=1);

namespace App\Modules\Cultivos\DTOs;

final readonly class CreateCultivoData
{
    /** @param list<string> $associatedCropCodes */
    public function __construct(
        public string $userId,
        public string $tipo,
        public string $fechaSiembra,
        public array $associatedCropCodes = [],
        public string $nombre = ''
    ) {
    }
}
