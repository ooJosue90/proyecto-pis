<?php

declare(strict_types=1);

namespace App\Modules\Asistente\Services;

final class AgriculturalKnowledgeBase
{
    public function context(array $analysis): string
    {
        if (!(bool) ($analysis['agricultural'] ?? false)) {
            return '';
        }

        $guidance = [
            'ALCANCE' => [
                'La orientación es apoyo para decisiones, no sustituye una inspección agronómica.',
                'Diferenciar siempre hechos del sistema, inferencias y recomendaciones.',
                'Si faltan síntomas, severidad, clima, suelo o ubicación, indicarlo y pedirlos.',
            ],
            'MANEJO_INTEGRADO' => [
                'Prevenir mediante sanidad del lote, material vegetal sano, nutrición equilibrada y manejo de humedad.',
                'Monitorear, identificar correctamente el problema y registrar incidencia antes de intervenir.',
                'Priorizar controles culturales, físicos y biológicos; usar control químico solo cuando esté justificado.',
                'No recomendar marcas, ingredientes activos ni dosis sin diagnóstico y verificación del registro vigente.',
            ],
            'ETAPAS' => [
                'Sin iniciar: verificar preparación del terreno, drenaje, trazado, material vegetal y plan de insumos.',
                'Siembra: comprobar establecimiento, humedad uniforme, supervivencia y sanidad inicial.',
                'Riego y desarrollo: vigilar humedad, drenaje, malezas, nutrición basada en análisis y presencia de plagas.',
                'Cosecha: revisar madurez, higiene, trazabilidad, manipulación y registro del rendimiento.',
            ],
            'MANGO' => [
                'Evitar recomendaciones de calendario rígido: floración, fructificación y presión sanitaria dependen del clima y la zona.',
                'En problemas foliares o de fruto, solicitar fotografías o descripción de síntomas, parte afectada, distribución y evolución.',
                'Para exportación, recordar trazabilidad, periodos de carencia y límites de residuos antes de cualquier tratamiento.',
            ],
            'FUENTES' => [
                'FAO, principios de manejo integrado de plagas: https://www.fao.org/pest-and-pesticide-management/ipm/principles-and-practices/en/',
                'Agrocalidad, registro de insumos agrícolas de Ecuador: https://www.agrocalidad.gob.ec/direccion-de-registro-de-insumos-agricolas/',
            ],
        ];

        return "BASE TÉCNICA CURADA:\n"
            . json_encode($guidance, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
