<?php

declare(strict_types=1);

namespace App\Shared\Domain;

final class AssociatedCropCatalog
{
    public const MAIN_CROP = 'Mango Tommy Atkins';

    /**
     * @return array<string, array{label:string,category:string,description:string,icon:string}>
     */
    public static function catalog(): array
    {
        return [
            'frijol' => [
                'label' => 'Frijol (caupí, canavalia o común)',
                'category' => 'Leguminosas y cobertura',
                'description' => 'Leguminosa · Aporta cobertura y nitrógeno',
                'icon' => 'grain',
            ],
            'mani-forrajero' => [
                'label' => 'Maní forrajero',
                'category' => 'Leguminosas y cobertura',
                'description' => 'Cobertura viva · Protege y mejora el suelo',
                'icon' => 'eco',
            ],
            'crotalaria' => [
                'label' => 'Crotalaria',
                'category' => 'Leguminosas y cobertura',
                'description' => 'Abono verde · Favorece el manejo del suelo',
                'icon' => 'grass',
            ],
            'papaya' => [
                'label' => 'Papaya',
                'category' => 'Frutales',
                'description' => 'Frutal de ciclo corto',
                'icon' => 'nutrition',
            ],
            'pina' => [
                'label' => 'Piña',
                'category' => 'Frutales',
                'description' => 'Frutal de porte bajo',
                'icon' => 'nutrition',
            ],
            'maracuya' => [
                'label' => 'Maracuyá',
                'category' => 'Frutales',
                'description' => 'Frutal trepador',
                'icon' => 'local_florist',
            ],
            'sandia' => [
                'label' => 'Sandía',
                'category' => 'Frutales',
                'description' => 'Cucurbitácea de ciclo corto',
                'icon' => 'nutrition',
            ],
            'melon' => [
                'label' => 'Melón',
                'category' => 'Frutales',
                'description' => 'Cucurbitácea de porte rastrero',
                'icon' => 'nutrition',
            ],
            'pimiento' => [
                'label' => 'Pimiento',
                'category' => 'Hortalizas',
                'description' => 'Hortaliza de fruto',
                'icon' => 'grocery',
            ],
            'tomate' => [
                'label' => 'Tomate',
                'category' => 'Hortalizas',
                'description' => 'Hortaliza de ciclo corto',
                'icon' => 'grocery',
            ],
            'maiz' => [
                'label' => 'Maíz',
                'category' => 'Cereales',
                'description' => 'Cereal de porte alto',
                'icon' => 'grass',
            ],
            'sorgo' => [
                'label' => 'Sorgo',
                'category' => 'Cereales',
                'description' => 'Cereal resistente y de cobertura',
                'icon' => 'grass',
            ],
        ];
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return array_map(
            static fn (array $crop): string => $crop['label'],
            self::catalog()
        );
    }

    /** @param mixed $selection @return list<string> */
    public static function normalizeSelection(mixed $selection): array
    {
        if ($selection === null || $selection === '') {
            return [];
        }
        if (!is_array($selection)) {
            return [];
        }

        $allowed = self::options();
        $normalized = [];
        foreach ($selection as $code) {
            if (!is_string($code)) {
                continue;
            }
            $code = trim(mb_strtolower($code, 'UTF-8'));
            if (isset($allowed[$code]) && !in_array($code, $normalized, true)) {
                $normalized[] = $code;
            }
        }

        return $normalized;
    }

    /** @param list<string> $codes @return list<string> */
    public static function labelsFor(array $codes): array
    {
        $options = self::options();
        $labels = [];
        foreach ($codes as $code) {
            if (isset($options[$code])) {
                $labels[] = $options[$code];
            }
        }

        return $labels;
    }

    public static function isValidSelection(mixed $selection): bool
    {
        if ($selection === null || $selection === '') {
            return true;
        }
        if (!is_array($selection)) {
            return false;
        }

        $allowed = self::options();
        $seen = [];
        foreach ($selection as $code) {
            if (!is_string($code)) {
                return false;
            }
            $code = trim(mb_strtolower($code, 'UTF-8'));
            if (!isset($allowed[$code]) || isset($seen[$code])) {
                return false;
            }
            $seen[$code] = true;
        }

        return true;
    }
}
