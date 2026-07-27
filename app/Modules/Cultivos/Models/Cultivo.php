<?php

declare(strict_types=1);

namespace App\Modules\Cultivos\Models;

final readonly class Cultivo
{
    /** @param list<string> $associatedCropCodes */
    public function __construct(
        public int $id,
        public string $userId,
        public string $tipo,
        public string $fechaSiembra,
        public ?string $agricultor = null,
        public array $associatedCropCodes = [],
        public string $nombre = ''
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
            isset($row['agricultor']) ? (string) $row['agricultor'] : null,
            self::parseAssociatedCodes($row['cultivos_asociados_codigos'] ?? null),
            isset($row['nombre']) && trim((string) $row['nombre']) !== ''
                ? (string) $row['nombre']
                : (string) $row['tipo']
        );
    }

    /** @return list<string> */
    public function associatedCropLabels(): array
    {
        return \App\Shared\Domain\AssociatedCropCatalog::labelsFor($this->associatedCropCodes);
    }

    /** @return list<string> */
    private static function parseAssociatedCodes(mixed $value): array
    {
        if (!is_string($value) || trim($value) === '') {
            return [];
        }

        return \App\Shared\Domain\AssociatedCropCatalog::normalizeSelection(explode(',', $value));
    }
}
