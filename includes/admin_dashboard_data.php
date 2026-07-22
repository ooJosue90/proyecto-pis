<?php

require_once __DIR__ . '/cosecha_data.php';

function admin_relative_time(?string $date): string
{
    if (!$date) {
        return 'hace menos de un minuto';
    }

    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return 'recientemente';
    }

    $seconds = max(0, time() - $timestamp);
    if ($seconds < 60) {
        return 'hace menos de un minuto';
    }

    $minutes = (int) floor($seconds / 60);
    if ($minutes < 60) {
        return 'hace ' . $minutes . ' ' . ($minutes === 1 ? 'minuto' : 'minutos');
    }

    $hours = (int) floor($minutes / 60);
    if ($hours < 24) {
        return 'hace ' . $hours . ' ' . ($hours === 1 ? 'hora' : 'horas');
    }

    $days = (int) floor($hours / 24);
    return 'hace ' . $days . ' ' . ($days === 1 ? 'día' : 'días');
}

function load_admin_dashboard_data(mysqli $conn): array
{
    $cosechaMetrics = cosecha_metrics($conn);

    $alertasInsumos = $conn->query("
        SELECT i.*,
               CASE WHEN i.cantidad = 0 THEN 'CRÍTICO'
                    WHEN i.cantidad < 5 THEN 'BAJO'
                    ELSE 'NORMAL' END AS nivel_alerta
        FROM insumos_agricolas i
        WHERE i.cantidad <= 5
        ORDER BY i.cantidad ASC
    ");

    $alertasProductos = $conn->query("
        SELECT pf.*,
               CASE WHEN pf.cantidad = 0 THEN 'CRÍTICO'
                    WHEN pf.cantidad < 5 THEN 'BAJO'
                    ELSE 'NORMAL' END AS nivel_alerta
        FROM productos_factura pf
        WHERE pf.cantidad <= 5
        ORDER BY pf.cantidad ASC
    ");

    $metrics = $conn->query("
        SELECT
            (SELECT COUNT(*) FROM lotes) AS total_lotes,
            (SELECT COUNT(*) FROM cultivos) AS total_cultivos,
            (SELECT COUNT(*) FROM usuarios) AS total_usuarios,
            (SELECT COUNT(*) FROM usuarios WHERE rol = 'Agricultor') AS agricultores,
            (SELECT COUNT(*) FROM insumos_agricolas WHERE cantidad = 0) AS total_insumos_criticos,
            (SELECT COUNT(*) FROM productos_factura WHERE cantidad <= 5) AS total_productos_bajos,
            (SELECT COUNT(*) FROM insumos_agricolas WHERE cantidad > 0 AND cantidad <= 5) +
                (SELECT COUNT(*) FROM productos_factura WHERE cantidad = 0) AS total_stock_critico,
            (SELECT COUNT(*) FROM insumos_agricolas WHERE cantidad > 5) +
                (SELECT COUNT(*) FROM productos_factura WHERE cantidad > 5) AS total_inventario_operativo,
            (SELECT MAX(fecha) FROM productos_solicitud) AS ultima_solicitud,
            (SELECT MAX(fecha_registro) FROM facturas_compra) AS ultima_factura,
            (SELECT MAX(fecha) FROM pedidos) AS ultimo_pedido,
            (SELECT MAX(fecha_registro) FROM usuarios) AS ultimo_usuario,
            (SELECT MAX(fecha) FROM notificaciones) AS ultima_notificacion
    ")->fetch_assoc();

    $eventDefinitions = [
        'pending_requests' => [
            'icon' => 'fas fa-clipboard-check',
            'title' => 'Solicitudes pendientes',
            'message' => static fn (int $total): string => $total . ' solicitudes esperan aprobación.',
            'target' => '#solicitudes',
        ],
        'registered_invoices' => [
            'icon' => 'fas fa-receipt',
            'title' => 'Facturas registradas',
            'message' => static fn (int $total): string => $total . ' facturas están pendientes de revisión.',
            'target' => '#facturas',
        ],
        'received_orders' => [
            'icon' => 'fas fa-truck-fast',
            'title' => 'Pedidos recibidos',
            'message' => static fn (int $total): string => $total . ' pedidos fueron recibidos en los últimos 7 días.',
            'target' => '#pedidos-proveedores',
        ],
        'new_users' => [
            'icon' => 'fas fa-user-check',
            'title' => 'Usuarios nuevos',
            'message' => static fn (int $total): string => $total . ' usuarios se registraron en los últimos 7 días.',
            'target' => '#usuarios',
        ],
        'pending_harvests' => [
            'icon' => 'fas fa-wheat-awn',
            'title' => 'Cosechas pendientes',
            'message' => static fn (int $total): string => $total . ' cosechas esperan validación.',
            'target' => '#cosechas',
        ],
    ];

    $eventRows = $conn->query("
        SELECT 'pending_requests' AS event_type, COUNT(*) AS total, MAX(fecha) AS last_date
        FROM productos_solicitud WHERE estado = 'Pendiente'
        UNION ALL
        SELECT 'registered_invoices', COUNT(*), MAX(fecha_registro)
        FROM facturas_compra WHERE estado = 'Registrada'
        UNION ALL
        SELECT 'received_orders', COUNT(*), MAX(fecha)
        FROM pedidos WHERE estado = 'Recibido' AND fecha >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        UNION ALL
        SELECT 'new_users', COUNT(*), MAX(fecha_registro)
        FROM usuarios WHERE fecha_registro >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        UNION ALL
        SELECT 'pending_harvests', COUNT(*), MAX(fecha_registro)
        FROM cosechas WHERE estado = 'Registrada'
    ");

    $systemEvents = [];
    $notificationTotal = 0;
    while ($row = $eventRows->fetch_assoc()) {
        $total = (int) $row['total'];
        $definition = $eventDefinitions[$row['event_type']] ?? null;
        if ($total === 0 || $definition === null) {
            continue;
        }

        $notificationTotal += $total;
        $systemEvents[] = [
            'icon' => $definition['icon'],
            'title' => $definition['title'],
            'message' => $definition['message']($total),
            'date' => $row['last_date'],
            'target' => $definition['target'],
        ];
    }

    $unreadNotifications = $conn->query("
        SELECT mensaje, fecha
        FROM notificaciones
        WHERE leida = 0
          AND (rol_destino IS NULL OR rol_destino = 'Administrador')
        ORDER BY fecha DESC
    ");
    while ($notification = $unreadNotifications->fetch_assoc()) {
        $notificationTotal++;
        $systemEvents[] = [
            'icon' => 'fas fa-satellite-dish',
            'title' => 'Actividad del sistema',
            'message' => $notification['mensaje'],
            'date' => $notification['fecha'],
            'target' => '#dashboard',
        ];
    }

    usort($systemEvents, static fn (array $first, array $second): int =>
        strtotime((string) $second['date']) <=> strtotime((string) $first['date'])
    );

    $lastDates = array_filter([
        $metrics['ultima_solicitud'],
        $metrics['ultima_factura'],
        $metrics['ultimo_pedido'],
        $metrics['ultimo_usuario'],
        $metrics['ultima_notificacion'],
    ]);
    $lastActivity = $lastDates
        ? date('Y-m-d H:i:s', max(array_map('strtotime', $lastDates)))
        : null;

    $totalInsumosCriticos = (int) $metrics['total_insumos_criticos'];
    $totalProductosBajos = (int) $metrics['total_productos_bajos'];

    return [
        'alertas_insumos' => $alertasInsumos,
        'alertas_productos' => $alertasProductos,
        'total_lotes' => (int) $metrics['total_lotes'],
        'total_cultivos' => (int) $metrics['total_cultivos'],
        'total_usuarios' => (int) $metrics['total_usuarios'],
        'agricultores' => (int) $metrics['agricultores'],
        'total_insumos_criticos' => $totalInsumosCriticos,
        'total_productos_bajos' => $totalProductosBajos,
        'total_stock_critico' => (int) $metrics['total_stock_critico'],
        'total_inventario_operativo' => (int) $metrics['total_inventario_operativo'],
        'total_alertas_inventario' => $totalInsumosCriticos + $totalProductosBajos,
        'notification_total' => $notificationTotal,
        'system_events' => $systemEvents,
        'last_activity' => $lastActivity,
        'cosechas_pendientes' => $cosechaMetrics['cosechas_pendientes'],
        'kg_cosecha_validados' => $cosechaMetrics['kg_validados'],
        'kg_cosecha_primera' => $cosechaMetrics['kg_primera'],
        'kg_cosecha_segunda' => $cosechaMetrics['kg_segunda'],
        'kg_cosecha_descarte' => $cosechaMetrics['kg_descarte'],
    ];
}
