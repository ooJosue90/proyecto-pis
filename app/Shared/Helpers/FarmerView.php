<?php

declare(strict_types=1);

namespace App\Shared\Helpers;

use App\Shared\Domain\CultivationStage;

final class FarmerView
{
    public static function stageLabel(int $stage): string
    {
        return CultivationStage::label($stage);
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
