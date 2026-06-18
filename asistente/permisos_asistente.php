<?php
/**
 * Permisos por rol para ADA.
 * Primero clasifica la intencion y luego valida permisos por rol.
 */

const ADA_INTENCION_AGRICOLA_GENERAL = 'conocimiento_agricola_general';
const ADA_INTENCION_DATOS_INTERNOS = 'datos_internos';
const ADA_INTENCION_ACCION_ADMINISTRATIVA = 'accion_administrativa';

function ada_normalizar_texto(string $texto): string
{
    $texto = function_exists('mb_strtolower')
        ? mb_strtolower($texto, 'UTF-8')
        : strtolower($texto);
    $texto = str_replace(
        ['á', 'é', 'í', 'ó', 'ú', 'ñ'],
        ['a', 'e', 'i', 'o', 'u', 'n'],
        $texto
    );

    return preg_replace('/\s+/', ' ', trim($texto));
}

function ada_permisos_por_rol(string $rol): array
{
    $rol = ada_normalizar_texto($rol);

    $permisos = [
        'administrador' => [
            'permitidos' => [
                'resumen', 'reportes', 'usuarios', 'inventario', 'proveedores', 'pedidos',
                'facturas', 'movimientos', 'cultivos', 'lotes', 'solicitudes', 'produccion',
                'plagas', 'monitoreo', 'actividades', 'notificaciones', 'acciones_administrativas',
            ],
            'restringidos' => [],
        ],
        'agricultor' => [
            'permitidos' => [
                'resumen', 'cultivos', 'lotes', 'solicitudes', 'produccion', 'plagas',
                'monitoreo', 'actividades',
            ],
            'restringidos' => [
                'usuarios', 'inventario', 'proveedores', 'pedidos', 'facturas', 'movimientos',
                'reportes', 'notificaciones', 'acciones_administrativas',
            ],
        ],
        'tecnico de campo' => [
            'permitidos' => [
                'resumen', 'cultivos', 'lotes', 'solicitudes', 'produccion', 'plagas',
                'monitoreo', 'actividades',
            ],
            'restringidos' => [
                'usuarios', 'inventario', 'proveedores', 'pedidos', 'facturas', 'movimientos',
                'reportes', 'notificaciones', 'acciones_administrativas',
            ],
        ],
        'bodeguero' => [
            'permitidos' => [
                'resumen', 'inventario', 'proveedores', 'pedidos', 'facturas', 'movimientos',
                'solicitudes',
            ],
            'restringidos' => [
                'usuarios', 'cultivos', 'lotes', 'produccion', 'plagas', 'monitoreo',
                'actividades', 'reportes',
                'notificaciones', 'acciones_administrativas',
            ],
        ],
    ];

    return $permisos[$rol] ?? ['permitidos' => [], 'restringidos' => ['']];
}

function ada_texto_contiene_alguno(string $texto, array $terminos): bool
{
    foreach ($terminos as $termino) {
        if ($termino !== '' && str_contains($texto, ada_normalizar_texto($termino))) {
            return true;
        }
    }

    return false;
}

function ada_texto_inicia_con_alguno(string $texto, array $inicios): bool
{
    $texto = preg_replace('/^[¿?¡!\s]+/u', '', ada_normalizar_texto($texto));

    foreach ($inicios as $inicio) {
        if (str_starts_with($texto, ada_normalizar_texto($inicio))) {
            return true;
        }
    }

    return false;
}

function ada_detectar_temas_internos(string $pregunta): array
{
    $texto = ada_normalizar_texto($pregunta);

    $temas = [
        'usuarios' => ['usuario', 'usuarios', 'empleado', 'empleados', 'roles', 'perfil'],
        'inventario' => ['inventario', 'stock', 'insumo', 'insumos', 'bodega', 'producto', 'productos'],
        'proveedores' => ['proveedor', 'proveedores'],
        'pedidos' => ['pedido', 'pedidos'],
        'facturas' => ['factura', 'facturas', 'financiero'],
        'movimientos' => ['movimiento', 'movimientos', 'entrada', 'salida'],
        'cultivos' => ['cultivo', 'cultivos'],
        'lotes' => ['lote', 'lotes'],
        'solicitudes' => ['solicitud', 'solicitudes', 'requerimiento', 'requerimientos'],
        'produccion' => [
            'produccion', 'producido', 'produjo', 'produjeron', 'rendimiento',
            'cosecha', 'cosechado', 'producto final', 'productos finales',
        ],
        'plagas' => ['plaga', 'plagas'],
        'monitoreo' => [
            'monitoreo', 'monitoreos', 'seguimiento', 'estado del lote',
            'etapa del lote', 'etapas del lote', 'avance del cultivo',
        ],
        'actividades' => [
            'actividad', 'actividades', 'labor', 'labores', 'tarea', 'tareas',
            'trabajo pendiente', 'trabajos pendientes',
        ],
        'reportes' => ['reporte', 'reportes', 'resumen ejecutivo'],
        'notificaciones' => ['notificacion', 'notificaciones', 'alerta', 'alertas'],
    ];

    $detectados = [];

    foreach ($temas as $tema => $terminos) {
        if (ada_texto_contiene_alguno($texto, $terminos)) {
            $detectados[] = $tema;
        }
    }

    return array_values(array_unique($detectados));
}

function ada_clasificar_intencion(string $pregunta): array
{
    $texto = ada_normalizar_texto($pregunta);
    $temasInternos = ada_detectar_temas_internos($pregunta);

    if (ada_texto_contiene_alguno($texto, [
        'como va todo',
        'como estamos',
        'estado general',
        'dame un resumen general',
        'resumen del sistema',
    ])) {
        return [
            'categoria' => ADA_INTENCION_DATOS_INTERNOS,
            'temas' => ['resumen'],
        ];
    }

    $iniciosEducativos = [
        'que es',
        'que son',
        'que significa',
        'como funciona',
        'para que sirve',
        'cual es la diferencia',
        'diferencia entre',
        'explica',
        'explicame',
        'define',
    ];

    if (ada_texto_inicia_con_alguno($pregunta, $iniciosEducativos)) {
        return [
            'categoria' => ADA_INTENCION_AGRICOLA_GENERAL,
            'temas' => [],
        ];
    }

    $verbosAdministrativos = [
        'crear', 'agregar', 'registrar', 'eliminar', 'borrar', 'desactivar', 'activar',
        'modificar', 'editar', 'actualizar', 'cambiar', 'gestionar', 'asignar', 'quitar',
        'configurar', 'restablecer', 'crea', 'agrega', 'registra', 'elimina', 'borra',
        'desactiva', 'activa', 'modifica', 'edita', 'actualiza', 'cambia', 'gestiona',
        'asigna', 'quita', 'configura', 'restablece',
    ];
    $objetosAdministrativos = [
        'usuario', 'usuarios', 'empleado', 'empleados', 'permiso', 'permisos', 'rol',
        'roles', 'perfil', 'perfiles', 'configuracion', 'parametro global',
        'parametros globales', 'contrasena',
    ];

    if (
        ada_texto_contiene_alguno($texto, $verbosAdministrativos)
        && ada_texto_contiene_alguno($texto, $objetosAdministrativos)
    ) {
        return [
            'categoria' => ADA_INTENCION_ACCION_ADMINISTRATIVA,
            'temas' => ['acciones_administrativas'],
        ];
    }

    $indicadoresDatosInternos = [
        'registrado', 'registrados', 'registrada', 'registradas', 'en el sistema',
        'base de datos', 'sembriexport', 'listar', 'lista', 'muestra', 'muestrame',
        'ver ', 'consultar', 'dame', 'cuantos', 'cuantas', 'total', 'estado',
        'pendiente', 'pendientes', 'actual', 'disponible', 'disponibles', 'reporte',
        'reportes', 'resumen', 'dashboard', 'panel', 'monitoreo', 'monitoreos',
        'seguimiento', 'actividad', 'actividades', 'labor', 'labores',
        'cuanto', 'cuanta', 'cuando fue', 'ultimo', 'ultima', 'reciente',
        'hoy', 'ayer', 'esta semana', 'este mes', 'produjo', 'producido',
    ];

    if ($temasInternos && ada_texto_contiene_alguno($texto, $indicadoresDatosInternos)) {
        return [
            'categoria' => ADA_INTENCION_DATOS_INTERNOS,
            'temas' => $temasInternos,
        ];
    }

    $temasAgricolas = [
        'agricola', 'agricultura', 'cultivo', 'cultivos', 'plaga', 'plagas',
        'fertilizante', 'fertilizantes', 'abono', 'abonos', 'riego', 'siembra',
        'cosecha', 'enfermedad', 'enfermedades', 'produccion agricola',
        'buenas practicas', 'mango', 'suelo', 'nutriente', 'nutrientes', 'poda',
        'floracion', 'control biologico', 'manejo integrado', 'recomendacion',
        'recomendaciones',
    ];

    if (ada_texto_contiene_alguno($texto, $temasAgricolas)) {
        return [
            'categoria' => ADA_INTENCION_AGRICOLA_GENERAL,
            'temas' => [],
        ];
    }

    return [
        'categoria' => ADA_INTENCION_AGRICOLA_GENERAL,
        'temas' => [],
    ];
}

function ada_consulta_permitida(string $rol, string $pregunta): bool
{
    $intencion = ada_clasificar_intencion($pregunta);
    return ada_intencion_autorizada($rol, $intencion);
}

function ada_intencion_autorizada(string $rol, array $intencion): bool
{
    $categoria = $intencion['categoria'] ?? ADA_INTENCION_AGRICOLA_GENERAL;

    if ($categoria === ADA_INTENCION_AGRICOLA_GENERAL) {
        return true;
    }

    $rolNormalizado = ada_normalizar_texto($rol);

    if ($rolNormalizado === 'administrador') {
        return true;
    }

    $permisos = ada_permisos_por_rol($rol);

    if ($categoria === ADA_INTENCION_ACCION_ADMINISTRATIVA) {
        return in_array('acciones_administrativas', $permisos['permitidos'], true);
    }

    $temas = $intencion['temas'] ?? [];

    if (!$temas) {
        return false;
    }

    foreach ($temas as $tema) {
        if (!in_array($tema, $permisos['permitidos'], true)) {
            return false;
        }
    }

    return true;
}

function ada_temas_permitidos_texto(string $rol): string
{
    $permisos = ada_permisos_por_rol($rol);
    return implode(', ', $permisos['permitidos']);
}
