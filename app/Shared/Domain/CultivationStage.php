<?php

declare(strict_types=1);

namespace App\Shared\Domain;

final class CultivationStage
{
    public const NONE = 0;
    public const PLANTING = 1;
    public const IRRIGATION = 2;
    public const HARVEST = 3;

    public const NONE_LABEL = 'Sin etapa';
    public const PLANTING_LABEL = 'Siembra';
    public const IRRIGATION_LABEL = 'Riego';
    public const HARVEST_LABEL = 'Cosecha';

    public const STATUS_PENDING = 'pendiente';
    public const STATUS_IN_PROGRESS = 'en_progreso';
    public const STATUS_COMPLETED = 'completada';

    /** @return array<int, string> */
    public static function labels(bool $includeNone = true): array
    {
        $labels = [
            self::PLANTING => self::PLANTING_LABEL,
            self::IRRIGATION => self::IRRIGATION_LABEL,
            self::HARVEST => self::HARVEST_LABEL,
        ];

        if ($includeNone) {
            $labels[self::NONE] = self::NONE_LABEL;
        }

        return $labels;
    }

    public static function label(int $stage): string
    {
        return self::labels()[$stage] ?? self::NONE_LABEL;
    }

    public static function isSequentialSelection(bool $planting, bool $irrigation, bool $harvest): bool
    {
        return (!$irrigation || $planting) && (!$harvest || $irrigation);
    }

    public static function currentFromSelection(bool $planting, bool $irrigation, bool $harvest): int
    {
        if ($harvest) {
            return self::HARVEST;
        }

        if ($irrigation) {
            return self::IRRIGATION;
        }

        return $planting ? self::PLANTING : self::NONE;
    }

    /** @return array<int, string> */
    public static function statesForCurrent(int $currentStage, bool $harvestCompleted = false): array
    {
        $states = [];
        foreach (self::labels(false) as $stage => $_label) {
            $states[$stage] = $stage < $currentStage
                ? self::STATUS_COMPLETED
                : ($stage === $currentStage ? self::STATUS_IN_PROGRESS : self::STATUS_PENDING);
        }

        if ($currentStage === self::NONE) {
            $states[self::PLANTING] = self::STATUS_PENDING;
        }
        if ($harvestCompleted) {
            $states[self::HARVEST] = self::STATUS_COMPLETED;
        }

        return $states;
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            self::STATUS_COMPLETED => 'Completada',
            self::STATUS_IN_PROGRESS => 'En progreso',
            default => 'Pendiente',
        };
    }

    /** @param array<int, string> $states */
    public static function canAccess(int $stage, int $currentStage, array $states): bool
    {
        if (!array_key_exists($stage, self::labels(false))) {
            return false;
        }

        return $stage <= $currentStage
            && ($stage === self::PLANTING || ($states[$stage - 1] ?? null) === self::STATUS_COMPLETED);
    }

    public static function fromName(string $name): int
    {
        return match (self::comparisonKey($name)) {
            'siembra' => self::PLANTING,
            'riego', 'desarrollo', 'crecimiento' => self::IRRIGATION,
            'cosecha' => self::HARVEST,
            'sin etapa', 'ninguna', '' => self::NONE,
            default => self::NONE,
        };
    }

    public static function normalizeName(string $name): string
    {
        $stage = self::fromName($name);

        return $stage === self::NONE && self::comparisonKey($name) !== 'sin etapa'
            ? trim($name)
            : self::label($stage);
    }

    private static function comparisonKey(string $value): string
    {
        $value = trim(mb_strtolower($value, 'UTF-8'));
        return strtr($value, [
            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ú' => 'u',
        ]);
    }
}
