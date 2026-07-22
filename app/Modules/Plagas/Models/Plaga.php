<?php

declare(strict_types=1);

namespace App\Modules\Plagas\Models;

final readonly class Plaga
{
    public function __construct(
        public int $id,
        public int $loteId,
        public string $userId,
        public string $nombre,
        public string $fecha,
        public ?string $ubicacion = null,
        public ?string $agricultor = null
    ) {
    }

    /** @param array<string,mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self(
            (int) $row['id_plaga'], (int) $row['id_lote'], (string) $row['id_usuario'],
            (string) $row['nombre'], (string) $row['fecha'],
            isset($row['ubicacion']) ? (string) $row['ubicacion'] : null,
            isset($row['agricultor']) ? (string) $row['agricultor'] : null
        );
    }
}
