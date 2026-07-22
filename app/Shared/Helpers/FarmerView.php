<?php

declare(strict_types=1);

namespace App\Shared\Helpers;

final class FarmerView
{
    public static function stageLabel(int $stage): string
    {
        return match ($stage) {
            1 => 'Siembra',
            2 => 'Desarrollo',
            3 => 'Cosecha',
            default => 'Sin etapa',
        };
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            'en_cosecha' => 'En cosecha',
            'finalizado' => 'Finalizado',
            'cancelado' => 'Cancelado',
            default => 'Activo',
        };
    }

    public static function statusSymbol(string $status): string
    {
        return match ($status) {
            'en_cosecha' => 'agriculture',
            'finalizado' => 'check_circle',
            'cancelado' => 'cancel',
            default => 'eco',
        };
    }
}
