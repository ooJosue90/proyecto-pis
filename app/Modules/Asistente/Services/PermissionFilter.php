<?php

declare(strict_types=1);

namespace App\Modules\Asistente\Services;

final class PermissionFilter
{
    private const TOPICS = [
        'usuarios' => ['usuario', 'usuarios', 'empleado', 'empleados', 'roles', 'correo', 'correos'],
        'inventario' => ['inventario', 'stock', 'insumo', 'insumos', 'bodega'],
        'proveedores' => ['proveedor', 'proveedores'],
        'pedidos' => ['pedido', 'pedidos'],
        'facturas' => ['factura', 'facturas', 'financiero'],
        'movimientos' => ['movimiento', 'movimientos', 'entrada', 'entradas', 'salida', 'salidas'],
        'cultivos' => ['cultivo', 'cultivos', 'siembra', 'siembras'],
        'lotes' => ['lote', 'lotes', 'monitoreo', 'seguimiento', 'fase', 'fases', 'etapa', 'etapas'],
        'solicitudes' => ['solicitud', 'solicitudes', 'requerimiento', 'requerimientos'],
        'produccion' => ['produccion', 'cosecha', 'cosechas', 'rendimiento', 'producido', 'produjo'],
        'plagas' => ['plaga', 'plagas', 'enfermedad', 'enfermedades', 'fitosanitario'],
        'reportes' => ['reporte', 'reportes', 'resumen', 'dashboard', 'panel'],
        'notificaciones' => ['notificacion', 'notificaciones', 'alerta', 'alertas'],
        'agricultura' => ['actividad agricola', 'actividades agricolas', 'tarea agricola', 'tareas agricolas', 'labor agricola', 'labores agricolas'],
    ];

    private const ALLOWED = [
        'Administrador' => [
            'usuarios', 'inventario', 'proveedores', 'pedidos', 'facturas', 'movimientos',
            'cultivos', 'lotes', 'solicitudes', 'produccion', 'plagas', 'reportes',
            'notificaciones', 'agricultura',
        ],
        'Agricultor' => [
            'cultivos', 'lotes', 'solicitudes', 'produccion', 'plagas', 'reportes', 'agricultura',
        ],
        'Bodeguero' => [
            'inventario', 'proveedores', 'pedidos', 'facturas', 'movimientos',
            'solicitudes', 'reportes',
        ],
    ];

    /**
     * @param array<string,mixed>|null $previous
     * @return array{
     *   category:string,
     *   topics:list<string>,
     *   operation:string,
     *   period:string,
     *   status:?string,
     *   agricultural:bool,
     *   follow_up:bool
     * }
     */
    public function analyze(string $question, ?array $previous = null): array
    {
        $text = $this->normalize($question);
        $topics = $this->detectTopics($text);
        $agricultural = $this->isAgricultural($text, $topics);
        $operation = $this->operation($text);
        $followUp = false;

        if ($topics === [] && $previous !== null && $this->isFollowUp($text)) {
            $topics = array_values(array_filter(
                (array) ($previous['topics'] ?? []),
                static fn (mixed $topic): bool => is_string($topic)
            ));
            $agricultural = $agricultural || (bool) ($previous['agricultural'] ?? false);
            $followUp = $topics !== [];
        }

        if ($this->isAction($text)) {
            return $this->result('action', $topics, 'action', $text, $agricultural, $followUp);
        }

        $internal = $topics !== [] && (
            $followUp
            || $this->hasInternalIndicator($text)
            || $operation !== 'list'
            || $this->referencesOwnedData($text)
        );

        if ($operation === 'advice' && $this->referencesOwnedData($text)) {
            $internal = true;
            if (!in_array('agricultura', $topics, true)) {
                $topics[] = 'agricultura';
            }
        }

        if ($internal) {
            return $this->result('internal', array_values(array_unique($topics)), $operation, $text, $agricultural, $followUp);
        }

        return $this->result('general', [], $operation, $text, $agricultural, $followUp);
    }

    public function authorized(string $role, array $analysis): bool
    {
        if ($analysis['category'] === 'general') {
            return true;
        }

        if ($analysis['category'] === 'action') {
            if ($analysis['topics'] === []) {
                return false;
            }
            $allowed = self::ALLOWED[$role] ?? [];
            foreach ($analysis['topics'] as $topic) {
                if (!in_array($topic, $allowed, true)) {
                    return false;
                }
            }
            return true;
        }

        $allowed = self::ALLOWED[$role] ?? [];
        foreach ($analysis['topics'] as $topic) {
            if (!in_array($topic, $allowed, true)) {
                return false;
            }
        }

        return $analysis['topics'] !== [];
    }

    /** @return list<string> */
    public function allowedTopics(string $role): array
    {
        return self::ALLOWED[$role] ?? [];
    }

    /** @return list<string> */
    private function detectTopics(string $text): array
    {
        $topics = [];
        foreach (self::TOPICS as $topic => $words) {
            foreach ($words as $word) {
                if ($this->containsTerm($text, $word)) {
                    $topics[] = $topic;
                    break;
                }
            }
        }

        return array_values(array_unique($topics));
    }

    private function operation(string $text): string
    {
        if ($this->containsAny($text, ['recomienda', 'recomendacion', 'recomendaciones', 'aconseja', 'que debo hacer', 'como manejo', 'como controlo', 'tratamiento'])) {
            return 'advice';
        }
        if ($this->containsAny($text, ['cuanto', 'cuanta', 'cuantos', 'cuantas', 'total', 'numero de'])) {
            return 'count';
        }
        if ($this->containsAny($text, ['stock bajo', 'poco stock', 'por agotarse', 'agotado', 'agotados'])) {
            return 'low_stock';
        }
        if ($this->containsAny($text, ['resumen', 'dashboard', 'panel', 'como va todo', 'como estamos', 'estado general'])) {
            return 'summary';
        }
        if ($this->containsAny($text, ['pendiente', 'pendientes', 'por atender'])) {
            return 'pending';
        }
        if ($this->containsAny($text, ['reciente', 'recientes', 'ultimo', 'ultimos', 'ultima', 'ultimas', 'hoy', 'ayer', 'esta semana', 'este mes'])) {
            return 'recent';
        }
        if ($this->containsAny($text, ['estado', 'fase', 'etapa', 'monitoreo', 'seguimiento', 'actividad', 'actividades', 'tarea', 'tareas'])) {
            return 'status';
        }

        return 'list';
    }

    private function isAction(string $text): bool
    {
        $verbs = [
            'crea', 'crear', 'agrega', 'agregar', 'registra', 'registrar', 'edita', 'editar',
            'actualiza', 'actualizar', 'elimina', 'eliminar', 'borra', 'borrar', 'aprueba',
            'aprobar', 'rechaza', 'rechazar', 'entrega', 'entregar', 'cambia', 'cambiar',
            'asigna', 'asignar', 'restablece', 'restablecer',
        ];
        $objects = [
            'usuario', 'rol', 'contrasena', 'cultivo', 'lote', 'plaga', 'solicitud',
            'pedido', 'factura', 'inventario', 'insumo', 'proveedor',
        ];

        return $this->containsAny($text, $verbs) && $this->containsAny($text, $objects);
    }

    private function hasInternalIndicator(string $text): bool
    {
        return $this->containsAny($text, [
            'registrado', 'registrada', 'registrados', 'registradas', 'sistema', 'base de datos',
            'lista', 'listar', 'muestra', 'muestrame', 'consulta', 'consultar', 'dame', 'ver',
            'hay', 'queda', 'disponible', 'actual', 'pendiente', 'reporte', 'resumen',
            'dashboard', 'panel', 'monitoreo', 'seguimiento', 'actividad', 'reciente',
            'cuanto', 'cuanta', 'cuantos', 'cuantas', 'total', 'estado', 'llevame',
            'abre el modulo', 'ir a',
        ]);
    }

    private function referencesOwnedData(string $text): bool
    {
        return $this->containsAny($text, [
            ' mi ', ' mis ', ' mio', ' mia', ' tengo', ' tenemos', ' nuestro', ' nuestra',
            'segun su etapa', 'segun la etapa', 'etapa actual', 'estado actual',
        ]);
    }

    private function isFollowUp(string $text): bool
    {
        $text = trim($text, " \t\n\r\0\x0B¿?¡!");
        return (bool) preg_match(
            '/^(y\s+)?(cual|cuales|cuanto|cuantos|las|los|ellos|ellas|esas|esos|pendientes|aprobados|rechazados|recientes|ahora|tambien)\b/u',
            $text
        ) || $this->containsAny($text, ['de esos', 'de esas', 'los anteriores', 'las anteriores']);
    }

    private function isAgricultural(string $text, array $topics): bool
    {
        if (array_intersect($topics, ['cultivos', 'lotes', 'produccion', 'plagas', 'agricultura']) !== []) {
            return true;
        }

        return $this->containsAny($text, [
            'agricola', 'agricultura', 'mango', 'suelo', 'riego', 'fertilizante',
            'abono', 'nutriente', 'poda', 'floracion', 'fruto', 'antracnosis',
            'control biologico', 'manejo integrado', 'buenas practicas',
        ]);
    }

    /** @return array{category:string,topics:list<string>,operation:string,period:string,status:?string,agricultural:bool,follow_up:bool} */
    private function result(
        string $category,
        array $topics,
        string $operation,
        string $text,
        bool $agricultural,
        bool $followUp
    ): array {
        return [
            'category' => $category,
            'topics' => array_values($topics),
            'operation' => $operation,
            'period' => $this->period($text),
            'status' => $this->status($text),
            'agricultural' => $agricultural,
            'follow_up' => $followUp,
        ];
    }

    private function period(string $text): string
    {
        return match (true) {
            $this->containsTerm($text, 'hoy') => 'today',
            $this->containsTerm($text, 'ayer') => 'yesterday',
            $this->containsTerm($text, 'esta semana') => 'week',
            $this->containsTerm($text, 'este mes') => 'month',
            default => 'all',
        };
    }

    private function status(string $text): ?string
    {
        return match (true) {
            $this->containsAny($text, ['pendiente', 'pendientes', 'por atender']) => 'Pendiente',
            $this->containsAny($text, ['aprobada', 'aprobadas', 'aprobado', 'aprobados']) => 'Aprobado',
            $this->containsAny($text, ['rechazada', 'rechazadas', 'rechazado', 'rechazados']) => 'Rechazado',
            $this->containsAny($text, ['entregada', 'entregadas', 'entregado', 'entregados']) => 'Entregado',
            $this->containsAny($text, ['finalizado', 'finalizados', 'finalizada', 'finalizadas']) => 'finalizado',
            $this->containsAny($text, ['cancelado', 'cancelados', 'cancelada', 'canceladas']) => 'cancelado',
            default => null,
        };
    }

    private function normalize(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');
        $text = str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ'],
            ['a', 'e', 'i', 'o', 'u', 'u', 'n'],
            $text
        );

        return ' ' . preg_replace('/\s+/u', ' ', trim($text)) . ' ';
    }

    private function containsAny(string $text, array $terms): bool
    {
        foreach ($terms as $term) {
            if ($this->containsTerm($text, $term)) {
                return true;
            }
        }

        return false;
    }

    private function containsTerm(string $text, string $term): bool
    {
        $term = trim($term);
        return (bool) preg_match(
            '/(?<![\p{L}\p{N}])' . preg_quote($term, '/') . '(?![\p{L}\p{N}])/u',
            $text
        );
    }
}
