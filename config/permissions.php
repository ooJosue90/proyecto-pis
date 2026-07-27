<?php

declare(strict_types=1);

return [
    'Administrador' => [
        '*',
    ],
    'Agricultor' => [
        'dashboard.agricultor',
        'cultivos.ver',
        'cultivos.crear',
        'lotes.ver',
        'lotes.crear',
        'lotes.actualizar',
        'plagas.ver',
        'plagas.crear',
        'produccion.ver',
        'produccion.crear',
        'solicitudes.ver_propias',
        'solicitudes.crear',
        'asistente.usar',
    ],
    'Bodeguero' => [
        'dashboard.bodeguero',
        'inventario.ver',
        'inventario.actualizar',
        'proveedores.ver',
        'pedidos.ver',
        'facturas.ver',
        'facturas.crear',
        'reportes.bodega',
        'solicitudes.procesar',
        'asistente.usar',
    ],
];
