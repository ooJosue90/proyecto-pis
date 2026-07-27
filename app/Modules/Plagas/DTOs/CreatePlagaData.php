<?php

declare(strict_types=1);

namespace App\Modules\Plagas\DTOs;

final readonly class CreatePlagaData
{
    public function __construct(public int $loteId, public string $userId, public string $nombre)
    {
    }
}
