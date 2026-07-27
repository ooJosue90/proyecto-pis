<?php

declare(strict_types=1);

namespace App\Modules\Produccion\Models;

final readonly class Produccion
{
    public function __construct(
        public int $id,
        public string $userId,
        public int $loteId,
        public string $productName,
        public float $quantity,
        public string $unit,
        public ?string $observations,
        public string $date,
        public ?string $ubicacion = null,
        public ?string $agricultor = null
    ) {
    }

    /** @param array<string,mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self(
            (int) $row['id_producto_final'], (string) $row['id_usuario'], (int) $row['id_lote'],
            (string) $row['nombre_producto'], (float) $row['cantidad'],
            (string) ($row['unidad_medida'] ?? ''),
            isset($row['observaciones']) ? (string) $row['observaciones'] : null,
            (string) $row['fecha'],
            isset($row['ubicacion']) ? (string) $row['ubicacion'] : null,
            isset($row['agricultor']) ? (string) $row['agricultor'] : null
        );
    }
}
