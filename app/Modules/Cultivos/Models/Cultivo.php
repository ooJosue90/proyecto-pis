<?php

declare(strict_types=1);

namespace App\Modules\Cultivos\Models;

final readonly class Cultivo
{
    public function __construct(
        public int $id,
        public string $userId,
        public string $tipo,
        public string $fechaSiembra,
        public ?string $agricultor = null
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self(
            (int) $row['id_cultivo'],
            (string) $row['id_usuario'],
            (string) $row['tipo'],
            (string) $row['fecha_siembra'],
            isset($row['agricultor']) ? (string) $row['agricultor'] : null
        );
    }
}
