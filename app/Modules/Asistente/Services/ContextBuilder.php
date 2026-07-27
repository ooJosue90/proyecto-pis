<?php

declare(strict_types=1);

namespace App\Modules\Asistente\Services;

use App\Shared\Interfaces\AssistantDataRepositoryInterface;

final class ContextBuilder
{
    private readonly AgriculturalKnowledgeBase $knowledge;

    public function __construct(
        private readonly AssistantDataRepositoryInterface $repository,
        private readonly int $maxRows = 20,
        private readonly int $maxChars = 12000,
        ?AgriculturalKnowledgeBase $knowledge = null
    ) {
        $this->knowledge = $knowledge ?? new AgriculturalKnowledgeBase();
    }

    public function build(string $role, string $userId, array $analysis): string
    {
        $parts = [
            'TIPO_DE_CONSULTA: ' . (string) ($analysis['operation'] ?? 'list'),
        ];

        if ($analysis['category'] === 'action') {
            $parts[] = 'RESTRICCIÓN: ADA no ejecuta cambios. Debe explicar el procedimiento y orientar al módulo autorizado.';
            return implode("\n\n", $parts);
        }

        if ($analysis['category'] === 'internal') {
            $topics = array_values(array_unique((array) $analysis['topics']));
            if (($analysis['operation'] ?? '') === 'advice' && !in_array('agricultura', $topics, true)) {
                $topics[] = 'agricultura';
            }
            if (in_array('reportes', $topics, true)) {
                $topics = array_values(array_unique(array_merge(
                    $topics,
                    match ($role) {
                        'Agricultor' => ['agricultura'],
                        'Bodeguero' => ['inventario', 'pedidos', 'solicitudes'],
                        default => [],
                    }
                )));
            }

            foreach ($topics as $topic) {
                $rows = $this->redact(
                    $this->repository->context($topic, $role, $userId, $this->maxRows, $analysis)
                );
                $payload = [
                    'tema' => $topic,
                    'registros' => $rows,
                    'limite_aplicado' => count($rows) >= $this->maxRows,
                ];
                $parts[] = 'DATOS_AUTORIZADOS_' . strtoupper((string) $topic) . ":\n"
                    . json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                if (strlen(implode("\n\n", $parts)) >= $this->maxChars) {
                    break;
                }
            }
        } else {
            $parts[] = 'DATOS_INTERNOS: No se proporcionaron datos del sistema para esta consulta general.';
        }

        $knowledge = $this->knowledge->context($analysis);
        if ($knowledge !== '') {
            $parts[] = $knowledge;
        }

        $context = implode("\n\n", $parts);
        return mb_substr($context, 0, $this->maxChars);
    }

    private function redact(array $rows): array
    {
        foreach ($rows as &$row) {
            foreach (array_keys($row) as $key) {
                if (preg_match('/password|contrasena|token|secret|api.?key/i', (string) $key)) {
                    unset($row[$key]);
                }
            }
        }
        unset($row);

        return $rows;
    }
}
