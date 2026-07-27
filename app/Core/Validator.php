<?php

declare(strict_types=1);

namespace App\Core;

use App\Shared\Exceptions\ValidationException;
use DateTimeImmutable;

final class Validator
{
    /** @var array<string, string> */
    private const FIELD_LABELS = [
        'date' => 'fecha',
        'fecha' => 'fecha',
        'fecha_siembra' => 'fecha de siembra',
        'fecha_cosecha' => 'fecha de cosecha',
        'fecha_inicio_siembra' => 'fecha de inicio de siembra',
        'fecha_fin_siembra' => 'fecha de finalización de siembra',
        'fecha_inicio_riego' => 'fecha de inicio de riego',
        'fecha_fin_riego' => 'fecha de finalización de riego',
        'fecha_inicio_cosecha' => 'fecha de inicio de cosecha',
        'fecha_fin_cosecha' => 'fecha de finalización de cosecha',
        'price' => 'precio unitario',
        'quantity' => 'cantidad recibida',
    ];

    /**
     * @param array<string, mixed> $data
     * @param array<string, string|list<string>> $rules
     * @return array<string, mixed>
     */
    public function validate(array $data, array $rules): array
    {
        $errors = [];

        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;
            $ruleList = is_array($fieldRules) ? $fieldRules : explode('|', $fieldRules);
            $isEmpty = $value === null || $value === '';

            if ($isEmpty && !in_array('required', $ruleList, true)) {
                continue;
            }

            foreach ($ruleList as $ruleDefinition) {
                [$rule, $argument] = array_pad(explode(':', $ruleDefinition, 2), 2, null);
                $message = $this->errorFor($field, $value, $rule, $argument);
                if ($message !== null) {
                    $errors[$field][] = $message;
                }
            }
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        return $data;
    }

    private function errorFor(string $field, mixed $value, string $rule, ?string $argument): ?string
    {
        $label = self::FIELD_LABELS[$field] ?? str_replace('_', ' ', $field);

        return match ($rule) {
            'required' => ($value === null || trim((string) $value) === '') ? "El campo {$label} es obligatorio." : null,
            'email' => filter_var($value, FILTER_VALIDATE_EMAIL) === false ? "El campo {$label} debe ser un correo válido." : null,
            'integer' => filter_var($value, FILTER_VALIDATE_INT) === false ? "El campo {$label} debe ser un entero." : null,
            'numeric' => !is_numeric($value) ? "El campo {$label} debe ser numérico." : null,
            'decimal' => !$this->isDecimal((string) $value, (int) $argument)
                ? "El campo {$label} debe contener solo números, con punto decimal y máximo {$argument} decimales."
                : null,
            'date' => !$this->isDate((string) $value)
                ? "El campo {$label} debe ser una fecha válida con formato YYYY-MM-DD."
                : null,
            'date_min' => $this->isDate((string) $value) && (string) $value < $this->dateBoundary($argument)
                ? ($argument === 'today'
                    ? "El campo {$label} debe ser igual o posterior al día actual."
                    : "El campo {$label} no puede ser anterior al " . $this->dateBoundary($argument) . '.')
                : null,
            'date_max' => $this->isDate((string) $value) && (string) $value > $this->dateBoundary($argument)
                ? ($argument === 'today'
                    ? "El campo {$label} no puede ser una fecha futura."
                    : "El campo {$label} no puede ser posterior al " . $this->dateBoundary($argument) . '.')
                : null,
            'min' => is_numeric($value) && (float) $value < (float) $argument ? "El campo {$label} debe ser al menos {$argument}." : null,
            'max' => is_numeric($value) && (float) $value > (float) $argument ? "El campo {$label} no puede ser mayor que {$argument}." : null,
            'max_length' => mb_strlen((string) $value) > (int) $argument ? "El campo {$label} no puede superar {$argument} caracteres." : null,
            'in' => !in_array((string) $value, explode(',', (string) $argument), true) ? "El valor de {$label} no está permitido." : null,
            default => null,
        };
    }

    private function isDate(string $value): bool
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date !== false && $date->format('Y-m-d') === $value;
    }

    private function isDecimal(string $value, int $places): bool
    {
        $decimalPart = $places > 0 ? '(?:\.\d{1,' . $places . '})?' : '';
        return preg_match('/^\d+' . $decimalPart . '$/', $value) === 1;
    }

    private function dateBoundary(?string $argument): string
    {
        return $argument === 'today' ? date('Y-m-d') : (string) $argument;
    }
}
