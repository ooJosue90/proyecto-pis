<?php
require_once 'conexion.php';
require_auth('Administrador');

// Obtener alertas críticas
$alertas_insumos = $conn->query("
    SELECT i.*, 
           CASE WHEN i.cantidad = 0 THEN 'CRÍTICO' 
                WHEN i.cantidad < 5 THEN 'BAJO' 
                ELSE 'NORMAL' END as nivel_alerta
    FROM insumos_agricolas i 
    WHERE i.cantidad <= 5 
    ORDER BY i.cantidad ASC
");

$alertas_productos = $conn->query("
    SELECT pf.*, 
           CASE WHEN pf.cantidad = 0 THEN 'CRÍTICO' 
                WHEN pf.cantidad < 5 THEN 'BAJO' 
                ELSE 'NORMAL' END as nivel_alerta
    FROM productos_factura pf 
    WHERE pf.cantidad <= 5 
    ORDER BY pf.cantidad ASC
");

// Estadísticas generales
$total_lotes = $conn->query("SELECT COUNT(*) as count FROM lotes")->fetch_assoc()['count'];
$total_cultivos = $conn->query("SELECT COUNT(*) as count FROM cultivos")->fetch_assoc()['count'];
$total_insumos_criticos = $conn->query("SELECT COUNT(*) as count FROM insumos_agricolas WHERE cantidad = 0")->fetch_assoc()['count'];
$total_productos_bajos = $conn->query("SELECT COUNT(*) as count FROM productos_factura WHERE cantidad <= 5")->fetch_assoc()['count'];
$total_stock_critico = $conn->query("
    SELECT
        (SELECT COUNT(*) FROM insumos_agricolas WHERE cantidad > 0 AND cantidad <= 5) +
        (SELECT COUNT(*) FROM productos_factura WHERE cantidad = 0) AS count
")->fetch_assoc()['count'];
$total_inventario_operativo = $conn->query("
    SELECT
        (SELECT COUNT(*) FROM insumos_agricolas WHERE cantidad > 5) +
        (SELECT COUNT(*) FROM productos_factura WHERE cantidad > 5) AS count
")->fetch_assoc()['count'];
$total_alertas_inventario = (int) $total_insumos_criticos + (int) $total_productos_bajos;

// Estadísticas adicionales para el admin
$total_usuarios = $conn->query("SELECT COUNT(*) as count FROM usuarios")->fetch_assoc()['count'];
$agricultores = $conn->query("SELECT COUNT(*) as count FROM usuarios WHERE rol='Agricultor'")->fetch_assoc()['count'];

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

// El centro reúne eventos del sistema y excluye deliberadamente el inventario.
$system_events = [];
$notification_total = 0;

$pending_requests = $conn->query("
    SELECT COUNT(*) AS total, MAX(fecha) AS ultima_fecha
    FROM productos_solicitud
    WHERE estado = 'Pendiente'
")->fetch_assoc();
if ((int) $pending_requests['total'] > 0) {
    $notification_total += (int) $pending_requests['total'];
    $system_events[] = [
        'icon' => 'fas fa-clipboard-check',
        'title' => 'Solicitudes pendientes',
        'message' => (int) $pending_requests['total'] . ' solicitudes esperan aprobación.',
        'date' => $pending_requests['ultima_fecha'],
        'target' => '#solicitudes',
    ];
}

$registered_invoices = $conn->query("
    SELECT COUNT(*) AS total, MAX(fecha_registro) AS ultima_fecha
    FROM facturas_compra
    WHERE estado = 'Registrada'
")->fetch_assoc();
if ((int) $registered_invoices['total'] > 0) {
    $notification_total += (int) $registered_invoices['total'];
    $system_events[] = [
        'icon' => 'fas fa-receipt',
        'title' => 'Facturas registradas',
        'message' => (int) $registered_invoices['total'] . ' facturas están pendientes de revisión.',
        'date' => $registered_invoices['ultima_fecha'],
        'target' => '#facturas',
    ];
}

$received_orders = $conn->query("
    SELECT COUNT(*) AS total, MAX(fecha) AS ultima_fecha
    FROM pedidos
    WHERE estado = 'Recibido' AND fecha >= DATE_SUB(NOW(), INTERVAL 7 DAY)
")->fetch_assoc();
if ((int) $received_orders['total'] > 0) {
    $notification_total += (int) $received_orders['total'];
    $system_events[] = [
        'icon' => 'fas fa-truck-fast',
        'title' => 'Pedidos recibidos',
        'message' => (int) $received_orders['total'] . ' pedidos fueron recibidos en los últimos 7 días.',
        'date' => $received_orders['ultima_fecha'],
        'target' => '#pedidos-proveedores',
    ];
}

$new_users = $conn->query("
    SELECT COUNT(*) AS total, MAX(fecha_registro) AS ultima_fecha
    FROM usuarios
    WHERE fecha_registro >= DATE_SUB(NOW(), INTERVAL 7 DAY)
")->fetch_assoc();
if ((int) $new_users['total'] > 0) {
    $notification_total += (int) $new_users['total'];
    $system_events[] = [
        'icon' => 'fas fa-user-check',
        'title' => 'Usuarios nuevos',
        'message' => (int) $new_users['total'] . ' usuarios se registraron en los últimos 7 días.',
        'date' => $new_users['ultima_fecha'],
        'target' => '#usuarios',
    ];
}

$unread_notifications = $conn->query("SELECT * FROM notificaciones WHERE leida = 0 ORDER BY fecha DESC");
while ($notification = $unread_notifications->fetch_assoc()) {
    $notification_total++;
    $system_events[] = [
        'icon' => 'fas fa-satellite-dish',
        'title' => 'Actividad del sistema',
        'message' => $notification['mensaje'],
        'date' => $notification['fecha'],
        'target' => '#dashboard',
    ];
}

usort($system_events, static function (array $first, array $second): int {
    return strtotime((string) $second['date']) <=> strtotime((string) $first['date']);
});

$last_activity = $conn->query("
    SELECT MAX(fecha_evento) AS ultima_fecha
    FROM (
        SELECT MAX(fecha) AS fecha_evento FROM productos_solicitud
        UNION ALL SELECT MAX(fecha_registro) FROM facturas_compra
        UNION ALL SELECT MAX(fecha) FROM pedidos
        UNION ALL SELECT MAX(fecha_registro) FROM usuarios
        UNION ALL SELECT MAX(fecha) FROM notificaciones
    ) eventos
")->fetch_assoc()['ultima_fecha'];

$admin_weekdays = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
$admin_months = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
$admin_today = $admin_weekdays[(int) date('w')] . ', ' . date('j') . ' de ' . $admin_months[(int) date('n') - 1] . ' de ' . date('Y');
$pending_requests_count = (int) ($pending_requests['total'] ?? 0);
$total_inventory_items = (int) $total_inventario_operativo + (int) $total_alertas_inventario;
$inventory_health = $total_inventory_items > 0
    ? (int) round(((int) $total_inventario_operativo / $total_inventory_items) * 100)
    : 100;
?>

<?php render_head('Panel Administrador', [
    'https://fonts.googleapis.com/css2?family=Raleway:wght@500;600;700;800;900&family=Roboto+Condensed:wght@400;500;600;700;800;900&display=swap',
    'assets/vendor/bootstrap/bootstrap.min.css',
    'css/admin.css?v=' . filemtime(__DIR__ . '/css/admin.css'),
]); ?>

<body class="farmer-dashboard-page admin-dashboard-page">
    <div class="admin-tablet-shell">
        <aside class="sidebar" id="mainSidebar" aria-label="Navegación principal">
            <div class="logo-container">
                <div class="admin-sidebar-logo">
                    <i class="fas fa-seedling" aria-hidden="true"></i>
                </div>
                <span class="nav-label admin-sidebar-brand">SembriExport</span>
            </div>

            <nav class="app-sidebar-nav admin-reference-nav">
                <button class="nav-item app-sidebar-link active" type="button" data-app-tab="#dashboard" title="Dashboard">
                    <i class="fas fa-gauge-high" aria-hidden="true"></i>
                    <span class="nav-label">Dashboard</span>
                </button>
                <button class="nav-item app-sidebar-link" type="button" data-app-tab="#reportes" title="Reportes">
                    <i class="fas fa-chart-simple" aria-hidden="true"></i>
                    <span class="nav-label">Reportes</span>
                </button>
                <button class="nav-item app-sidebar-link" type="button" data-app-tab="#usuarios" title="Usuarios">
                    <i class="fas fa-users-gear" aria-hidden="true"></i>
                    <span class="nav-label">Usuarios</span>
                </button>
                <button class="nav-item app-sidebar-link" type="button" data-app-tab="#solicitudes" title="Solicitudes">
                    <i class="fas fa-clipboard-check" aria-hidden="true"></i>
                    <span class="nav-label">Solicitudes</span>
                </button>
                <button class="nav-item app-sidebar-link" type="button" data-app-tab="#movimientos" title="Movimientos">
                    <i class="fas fa-arrow-right-arrow-left" aria-hidden="true"></i>
                    <span class="nav-label">Movimientos</span>
                </button>
                <button class="nav-item app-sidebar-link" type="button" data-app-tab="#facturas" title="Facturas">
                    <i class="fas fa-receipt" aria-hidden="true"></i>
                    <span class="nav-label">Facturas</span>
                </button>
                <button class="nav-item app-sidebar-link" type="button" data-app-tab="#cultivos" title="Cultivos">
                    <i class="fas fa-seedling" aria-hidden="true"></i>
                    <span class="nav-label">Cultivos</span>
                </button>
                <button class="nav-item app-sidebar-link" type="button" data-app-tab="#pedidos-proveedores" title="Proveedores">
                    <i class="fas fa-truck-fast" aria-hidden="true"></i>
                    <span class="nav-label">Proveedores</span>
                </button>
            </nav>

            <div class="admin-sidebar-actions">
                <a class="nav-item" href="logout.php" title="Cerrar sesión">
                    <i class="fas fa-arrow-right-from-bracket" aria-hidden="true"></i>
                    <span class="nav-label">Log out</span>
                </a>
            </div>
        </aside>

        <main class="admin-inner-container">
            <header class="admin-reference-topbar">
                <div class="admin-topbar-user">
                    <span class="admin-topbar-avatar"><?php echo e(app_user_initials()); ?></span>
                    <div>
                        <h2>Saludos, <?php echo e(current_user_name()); ?></h2>
                        <p>Inicia tu jornada con Verdeagro ERP</p>
                    </div>
                </div>
                <div class="admin-topbar-actions">
                    <div class="admin-account-menu" data-admin-account-menu>
                        <button class="admin-account-button" type="button" aria-haspopup="menu" aria-expanded="false" data-admin-account-trigger>
                            <span class="admin-account-initials" aria-hidden="true"><?php echo e(app_user_initials()); ?></span>
                            <span>Cuenta</span>
                            <span class="material-symbols-outlined" aria-hidden="true">expand_more</span>
                        </button>
                        <div class="admin-account-dropdown" role="menu" aria-label="Opciones de cuenta">
                            <div class="admin-account-dropdown__profile" aria-hidden="true">
                                <strong>Admin Principal</strong>
                                <small><?php echo e(current_user_name()); ?></small>
                            </div>
                            <a href="logout.php" role="menuitem">
                                <span class="material-symbols-outlined" aria-hidden="true">logout</span>
                                <span>Cerrar sesión</span>
                            </a>
                        </div>
                    </div>
                </div>
            </header>
            <div class="container farmer-dashboard admin-dashboard mt-4">
        <?php render_flash_messages(); ?>

        <section class="farmer-page-heading admin-page-heading">
            <div class="admin-greeting">
                <div class="admin-heading-copy">
                    <h1>Resumen <span>general</span></h1>
                    <p><?php echo e($admin_today); ?></p>
                </div>
            </div>
        </section>

        <div class="tab-content" id="adminTabsContent">
            <!-- Dashboard Tab -->
            <div class="tab-pane fade show active" id="dashboard" role="tabpanel">
                <section class="admin-hero-banner" aria-labelledby="admin-hero-title">
                    <div class="admin-hero-copy">
                        <span class="admin-hero-eyebrow">Control center</span>
                        <h2 id="admin-hero-title">Operación agrícola en <span>tiempo real</span></h2>
                        <p>Supervisa inventario, producción, solicitudes y alertas críticas desde una sola vista ejecutiva.</p>
                        <div class="admin-hero-actions">
                            <a class="admin-hero-primary" href="#reportes" data-app-tab="#reportes">
                                <i class="fas fa-chart-simple"></i>
                                Ver reportes
                            </a>
                            <a class="admin-hero-secondary" href="#admin-priorities">
                                <i class="fas fa-compass"></i>
                                Ver prioridades
                            </a>
                        </div>
                    </div>
                    <div class="admin-hero-metrics" aria-label="Resumen destacado">
                        <article class="admin-hero-mini">
                            <span>Usuarios</span>
                            <strong><?php echo $total_usuarios; ?></strong>
                            <small><?php echo $agricultores; ?> agricultores activos</small>
                        </article>
                        <article class="admin-hero-mini">
                            <span>Inventario sano</span>
                            <strong><?php echo $inventory_health; ?>%</strong>
                            <small><?php echo $total_inventario_operativo; ?> ítems operativos</small>
                        </article>
                        <article class="admin-hero-mini">
                            <span>Producción</span>
                            <strong><?php echo $total_cultivos; ?></strong>
                            <small><?php echo $total_lotes; ?> lotes monitoreados</small>
                        </article>
                        <article class="admin-hero-mini">
                            <span>Pendientes</span>
                            <strong><?php echo $notification_total; ?></strong>
                            <small><?php echo $pending_requests_count; ?> solicitudes por revisar</small>
                        </article>
                    </div>
                </section>

                <section class="admin-premium-dashboard" aria-labelledby="admin-activity-title">
                    <div class="admin-premium-main">
                        <section class="admin-activity-table" aria-labelledby="admin-activity-title">
                            <div class="admin-section-heading">
                                <div class="admin-section-title-with-icon">
                                    <span class="admin-detail-card__icon admin-detail-card__icon--activity"><i class="fas fa-chart-line"></i></span>
                                    <div>
                                        <span class="admin-section-eyebrow">Operación</span>
                                        <h2 id="admin-activity-title">Actividad reciente</h2>
                                    </div>
                                </div>
                                <span class="admin-live-status"><i class="fas fa-circle"></i> Datos actualizados</span>
                            </div>

                            <?php if ($system_events): ?>
                                <div class="admin-activity-list">
                                    <?php foreach (array_slice($system_events, 0, 4) as $event): ?>
                                        <a class="admin-activity-row" href="<?php echo e($event['target']); ?>" data-app-tab="<?php echo e($event['target']); ?>">
                                            <span class="admin-activity-icon"><i class="<?php echo e($event['icon']); ?>"></i></span>
                                            <span class="admin-activity-copy">
                                                <strong><?php echo e($event['title']); ?></strong>
                                                <small><?php echo e($event['message']); ?></small>
                                            </span>
                                            <time datetime="<?php echo e($event['date']); ?>"><?php echo e(admin_relative_time($event['date'])); ?></time>
                                            <i class="fas fa-chevron-right" aria-hidden="true"></i>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="admin-empty-state">
                                    <i class="fas fa-check"></i>
                                    <div>
                                        <strong>Sistema operando normalmente.</strong>
                                        <span>No existen notificaciones pendientes.</span>
                                        <small>Última actualización: <?php echo e(admin_relative_time($last_activity)); ?>.</small>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </section>
                    </div>

                    <aside class="admin-premium-side">
                        <section class="admin-side-list" id="admin-priorities" aria-labelledby="admin-priorities-title">
                            <div class="admin-stat-card__header">
                                <div class="admin-section-title-with-icon">
                                    <span class="admin-detail-card__icon admin-detail-card__icon--focus"><i class="fas fa-compass"></i></span>
                                    <div>
                                        <span class="admin-section-eyebrow">Enfoque</span>
                                        <h2 id="admin-priorities-title">Prioridades</h2>
                                    </div>
                                </div>
                                <a class="admin-inline-link" href="#admin-inventory-alerts">Ver detalle</a>
                            </div>
                            <div class="admin-side-items">
                                <article class="admin-side-item <?php echo $pending_requests_count > 0 ? 'admin-side-item--warning' : ''; ?>">
                                    <span><i class="fas fa-clipboard-check"></i></span>
                                    <div><strong>Solicitudes por revisar</strong><small><?php echo $pending_requests_count > 0 ? 'Requieren aprobación' : 'Sin pendientes'; ?></small></div>
                                    <b><?php echo $pending_requests_count; ?></b>
                                </article>
                                <article class="admin-side-item <?php echo (int) $registered_invoices['total'] > 0 ? 'admin-side-item--warning' : ''; ?>">
                                    <span><i class="fas fa-receipt"></i></span>
                                    <div><strong>Facturas registradas</strong><small><?php echo (int) $registered_invoices['total'] > 0 ? 'Pendientes de validación' : 'Sin revisión pendiente'; ?></small></div>
                                    <b><?php echo (int) $registered_invoices['total']; ?></b>
                                </article>
                                <article class="admin-side-item <?php echo $total_alertas_inventario > 0 ? 'admin-side-item--danger' : ''; ?>">
                                    <span><i class="fas fa-triangle-exclamation"></i></span>
                                    <div><strong>Alertas de inventario</strong><small><?php echo $total_alertas_inventario > 0 ? 'Revisar existencias' : 'Inventario estable'; ?></small></div>
                                    <b><?php echo $total_alertas_inventario; ?></b>
                                </article>
                                <article class="admin-side-item">
                                    <span><i class="fas fa-truck-fast"></i></span>
                                    <div><strong>Pedidos recibidos</strong><small>Últimos 7 días</small></div>
                                    <b><?php echo (int) $received_orders['total']; ?></b>
                                </article>
                            </div>
                        </section>
                    </aside>
                </section>

                <!-- Alertas de inventario -->
                <div class="row" id="admin-inventory-alerts">
                    <div class="col-md-6">
                        <div class="card admin-detail-card admin-detail-card--danger">
                            <div class="card-header">
                                <span class="admin-detail-card__icon"><i class="fas fa-vials"></i></span>
                                <div>
                                    <span class="admin-section-eyebrow">Inventario</span>
                                    <h5>Alertas de insumos</h5>
                                </div>
                            </div>
                            <div class="card-body app-scroll-panel">
                                <?php if ($alertas_insumos->num_rows > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Insumo</th>
                                                    <th>Cantidad</th>
                                                    <th>Estado</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while ($insumo = $alertas_insumos->fetch_assoc()): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($insumo['nombre']); ?></td>
                                                        <td><?php echo $insumo['cantidad']; ?></td>
                                                        <td>
                                                            <span class="badge bg-<?php echo $insumo['nivel_alerta'] == 'CRÍTICO' ? 'danger' : 'warning'; ?>">
                                                                <?php echo $insumo['nivel_alerta']; ?>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-success">
                                        <i class="fas fa-circle-check"></i> Todos los insumos tienen niveles normales
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card admin-detail-card admin-detail-card--warning">
                            <div class="card-header">
                                <span class="admin-detail-card__icon"><i class="fas fa-box-archive"></i></span>
                                <div>
                                    <span class="admin-section-eyebrow">Inventario</span>
                                    <h5>Alertas de productos</h5>
                                </div>
                            </div>
                            <div class="card-body app-scroll-panel">
                                <?php if ($alertas_productos->num_rows > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Producto</th>
                                                    <th>Cantidad</th>
                                                    <th>Estado</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while ($producto = $alertas_productos->fetch_assoc()): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($producto['nombre']); ?></td>
                                                        <td><?php echo $producto['cantidad']; ?></td>
                                                        <td>
                                                            <span class="badge bg-<?php echo $producto['cantidad'] == 0 ? 'danger' : 'warning'; ?>">
                                                                <?php echo $producto['cantidad'] == 0 ? 'CRÍTICO' : 'BAJO'; ?>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-success">
                                        <i class="fas fa-circle-check"></i> Todos los productos tienen niveles normales
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Selector de lotes para historial -->
                <div class="row mt-4 admin-lot-history">
                    <div class="col-12">
                        <div class="card admin-detail-card admin-detail-card--history">
                            <div class="card-header">
                                <span class="admin-detail-card__icon"><i class="fas fa-route"></i></span>
                                <div>
                                    <span class="admin-section-eyebrow">Trazabilidad</span>
                                    <h5>Historial por lote</h5>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="admin-lot-history-controls">
                                    <div class="admin-lot-history-field">
                                        <label class="form-label" for="selectorLote">Seleccionar lote</label>
                                        <select class="form-control" id="selectorLote" data-admin-lot-select>
                                            <option value="">-- Seleccione un lote --</option>
                                            <?php
                                            $lotes = $conn->query("
                                                SELECT l.id_lote, l.ubicacion, l.area, c.tipo as cultivo_tipo 
                                                FROM lotes l 
                                                LEFT JOIN cultivos c ON l.id_cultivo = c.id_cultivo 
                                                ORDER BY l.id_lote
                                            ");
                                            while ($lote = $lotes->fetch_assoc()):
                                            ?>
                                                <option value="<?php echo $lote['id_lote']; ?>">
                                                    Lote <?php echo $lote['id_lote']; ?> - <?php echo htmlspecialchars($lote['ubicacion']); ?>
                                                    <?php if ($lote['cultivo_tipo']): ?>
                                                        (<?php echo htmlspecialchars($lote['cultivo_tipo']); ?>)
                                                    <?php endif; ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <div class="admin-lot-history-action">
                                        <button class="btn btn-info admin-lot-history-button" type="button" data-admin-lot-history data-app-no-ripple>
                                            <i class="fas fa-magnifying-glass" aria-hidden="true"></i>
                                            <span>Ver Historial Completo</span>
                                        </button>
                                    </div>
                                </div>
                                <div id="historialLoteContent" class="mt-3">
                                    <!-- Contenido del historial se cargará aquí -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Usuarios Tab -->
            <div class="tab-pane fade" id="usuarios" role="tabpanel">
                <div id="usuarios-content">
                    <div class="text-center mt-5">
                        <i class="fas fa-spinner fa-spin fa-2x"></i>
                        <p class="mt-2">Cargando gestión de usuarios...</p>
                    </div>
                </div>
            </div>
            <!-- MODAL CREAR USUARIO -->
            <div class="modal fade admin-premium-modal admin-user-modal" id="modalCrearUsuario" tabindex="-1" aria-labelledby="modalCrearUsuarioTitle" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <span class="admin-premium-modal__icon admin-user-modal__icon" aria-hidden="true">
                                <i class="fas fa-user-check"></i>
                            </span>
                            <div class="admin-premium-modal__heading">
                                <span class="farmer-kicker">Gestión de accesos</span>
                                <h2 class="modal-title" id="modalCrearUsuarioTitle">Crear nuevo usuario</h2>
                                <p>Registre la identidad, credenciales y nivel de acceso al sistema.</p>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                        </div>
                        <form id="formCrearUsuario">
                            <div class="modal-body">
                                <div class="admin-user-modal__notice">
                                    <span><i class="fas fa-shield-halved"></i></span>
                                    <div>
                                        <strong>Cuenta protegida</strong>
                                        <p>El usuario recibirá permisos según el rol seleccionado.</p>
                                    </div>
                                </div>

                                <div class="admin-user-modal__grid">
                                    <label class="admin-user-field">
                                        <span class="admin-user-field__label">Cédula <small>Opcional</small></span>
                                        <span class="admin-user-field__control">
                                            <i class="fas fa-id-card"></i>
                                            <input type="text" class="form-control" name="cedula" placeholder="Ingrese cédula o deje vacío">
                                        </span>
                                        <small class="admin-user-field__help">Si queda vacío, se asignará un ID automático.</small>
                                    </label>

                                    <label class="admin-user-field">
                                        <span class="admin-user-field__label">Nombre completo <b>*</b></span>
                                        <span class="admin-user-field__control">
                                            <i class="fas fa-user"></i>
                                            <input type="text" class="form-control" name="nombre" placeholder="Nombre y apellido" required>
                                        </span>
                                    </label>

                                    <label class="admin-user-field">
                                        <span class="admin-user-field__label">Email <b>*</b></span>
                                        <span class="admin-user-field__control">
                                            <i class="fas fa-envelope"></i>
                                            <input type="email" class="form-control" name="email" placeholder="usuario@correo.com" required>
                                        </span>
                                    </label>

                                    <label class="admin-user-field">
                                        <span class="admin-user-field__label">Contraseña <b>*</b></span>
                                        <span class="admin-user-field__control">
                                            <i class="fas fa-lock"></i>
                                            <input type="password" class="form-control" name="contrasena" placeholder="Mínimo 6 caracteres" minlength="6" required>
                                        </span>
                                        <small class="admin-user-field__help">Utilice al menos 6 caracteres.</small>
                                    </label>

                                    <div class="admin-user-field admin-user-field--wide">
                                        <span class="admin-user-field__label">Rol de acceso <b>*</b></span>
                                        <div class="admin-user-role" data-user-role-select>
                                            <select class="admin-user-role__native" name="rol" required tabindex="-1" aria-hidden="true">
                                                <option value="">Seleccione el nivel de acceso</option>
                                                <option value="Administrador">Administrador</option>
                                                <option value="Agricultor">Agricultor</option>
                                                <option value="Bodeguero">Bodeguero</option>
                                            </select>
                                            <button class="admin-user-role__button" type="button" data-user-role-button aria-haspopup="listbox" aria-expanded="false">
                                                <i class="fas fa-user-shield" aria-hidden="true"></i>
                                                <span data-user-role-label>Seleccione el nivel de acceso</span>
                                                <i class="fas fa-chevron-down" aria-hidden="true"></i>
                                            </button>
                                            <div class="admin-user-role__menu" role="listbox" aria-label="Nivel de acceso">
                                                <button class="admin-user-role__option" type="button" role="option" data-value="Administrador">
                                                    <i class="fas fa-user-gear" aria-hidden="true"></i>
                                                    <span><strong>Administrador</strong><small>Control completo del sistema</small></span>
                                                </button>
                                                <button class="admin-user-role__option" type="button" role="option" data-value="Agricultor">
                                                    <i class="fas fa-seedling" aria-hidden="true"></i>
                                                    <span><strong>Agricultor</strong><small>Gestión de cultivos y cosechas</small></span>
                                                </button>
                                                <button class="admin-user-role__option" type="button" role="option" data-value="Bodeguero">
                                                    <i class="fas fa-warehouse" aria-hidden="true"></i>
                                                    <span><strong>Bodeguero</strong><small>Inventario, insumos y despachos</small></span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <span class="admin-premium-modal__security">
                                    <i class="fas fa-lock"></i> Credenciales almacenadas de forma segura
                                </span>
                                <button type="button" class="btn admin-user-modal__cancel" data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-primary admin-user-modal__submit" data-app-no-ripple>
                                    <i class="fas fa-user-check"></i>
                                    <span>Crear usuario</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- MODAL EDITAR USUARIO -->
            <div class="modal fade admin-premium-modal admin-user-modal" id="modalEditarUsuario" tabindex="-1" aria-labelledby="modalEditarUsuarioTitle" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <span class="admin-premium-modal__icon admin-user-modal__icon" aria-hidden="true">
                                <i class="fas fa-user-pen"></i>
                            </span>
                            <div class="admin-premium-modal__heading">
                                <span class="farmer-kicker">Gestión de accesos</span>
                                <h2 class="modal-title" id="modalEditarUsuarioTitle">Editar usuario</h2>
                                <p>Actualice la identidad, credenciales o permisos de esta cuenta.</p>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                        </div>
                        <form id="formEditarUsuario">
                            <div class="modal-body">
                                <input type="hidden" name="action" value="editar">
                                <input type="hidden" name="id_usuario" id="edit_id">

                                <div class="admin-user-modal__notice">
                                    <span><i class="fas fa-shield-halved"></i></span>
                                    <div>
                                        <strong>Edición protegida</strong>
                                        <p>Los cambios de rol y contraseña se aplicarán al guardar.</p>
                                    </div>
                                </div>

                                <div class="admin-user-modal__grid">
                                    <label class="admin-user-field">
                                        <span class="admin-user-field__label">ID / Cédula <small>No editable</small></span>
                                        <span class="admin-user-field__control">
                                            <i class="fas fa-id-card"></i>
                                            <input type="text" class="form-control" id="edit_id_display" disabled>
                                        </span>
                                    </label>

                                    <label class="admin-user-field">
                                        <span class="admin-user-field__label">Nombre completo <b>*</b></span>
                                        <span class="admin-user-field__control">
                                            <i class="fas fa-user"></i>
                                            <input type="text" class="form-control" name="nombre" id="edit_nombre" placeholder="Nombre y apellido" required>
                                        </span>
                                    </label>

                                    <label class="admin-user-field">
                                        <span class="admin-user-field__label">Email <b>*</b></span>
                                        <span class="admin-user-field__control">
                                            <i class="fas fa-envelope"></i>
                                            <input type="email" class="form-control" name="email" id="edit_email" placeholder="usuario@correo.com" required>
                                        </span>
                                    </label>

                                    <label class="admin-user-field">
                                        <span class="admin-user-field__label">Nueva contraseña <small>Opcional</small></span>
                                        <span class="admin-user-field__control">
                                            <i class="fas fa-key"></i>
                                            <input type="password" class="form-control" name="nueva_contrasena" id="edit_contrasena" placeholder="Mínimo 6 caracteres" minlength="6">
                                        </span>
                                        <small class="admin-user-field__help">Déjela vacía para conservar la contraseña actual.</small>
                                    </label>

                                    <div class="admin-user-field admin-user-field--wide">
                                        <span class="admin-user-field__label">Rol de acceso <b>*</b></span>
                                        <div class="admin-user-role" data-user-role-select>
                                            <select class="admin-user-role__native" name="rol" id="edit_rol" required tabindex="-1" aria-hidden="true">
                                                <option value="">Seleccione el nivel de acceso</option>
                                                <option value="Administrador">Administrador</option>
                                                <option value="Agricultor">Agricultor</option>
                                                <option value="Bodeguero">Bodeguero</option>
                                            </select>
                                            <button class="admin-user-role__button" type="button" data-user-role-button aria-haspopup="listbox" aria-expanded="false">
                                                <i class="fas fa-user-shield" aria-hidden="true"></i>
                                                <span data-user-role-label>Seleccione el nivel de acceso</span>
                                                <i class="fas fa-chevron-down" aria-hidden="true"></i>
                                            </button>
                                            <div class="admin-user-role__menu" role="listbox" aria-label="Nivel de acceso">
                                                <button class="admin-user-role__option" type="button" role="option" data-value="Administrador">
                                                    <i class="fas fa-user-gear" aria-hidden="true"></i>
                                                    <span><strong>Administrador</strong><small>Control completo del sistema</small></span>
                                                </button>
                                                <button class="admin-user-role__option" type="button" role="option" data-value="Agricultor">
                                                    <i class="fas fa-seedling" aria-hidden="true"></i>
                                                    <span><strong>Agricultor</strong><small>Gestión de cultivos y cosechas</small></span>
                                                </button>
                                                <button class="admin-user-role__option" type="button" role="option" data-value="Bodeguero">
                                                    <i class="fas fa-warehouse" aria-hidden="true"></i>
                                                    <span><strong>Bodeguero</strong><small>Inventario, insumos y despachos</small></span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <span class="admin-premium-modal__security">
                                    <i class="fas fa-lock"></i> Cambios protegidos y almacenados de forma segura
                                </span>
                                <button type="button" class="btn admin-user-modal__cancel" data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-primary admin-user-modal__submit" data-app-no-ripple>
                                    <i class="fas fa-floppy-disk"></i>
                                    <span>Guardar cambios</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- MODAL ELIMINAR USUARIO -->
            <div class="modal fade admin-premium-modal admin-delete-modal admin-user-delete-modal" id="adminUserDeleteModal" tabindex="-1" aria-labelledby="adminUserDeleteTitle" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form id="adminUserDeleteForm">
                            <input type="hidden" name="id_usuario" id="adminUserDeleteId">

                            <div class="modal-header">
                                <span class="admin-delete-modal__icon" aria-hidden="true">
                                    <i class="fas fa-user-xmark"></i>
                                </span>
                                <div class="admin-premium-modal__heading">
                                    <span class="farmer-kicker">Acción irreversible</span>
                                    <h2 class="modal-title" id="adminUserDeleteTitle">Eliminar usuario</h2>
                                    <p>Esta cuenta perderá inmediatamente el acceso al sistema.</p>
                                </div>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                            </div>

                            <div class="modal-body">
                                <div class="admin-delete-modal__warning">
                                    <i class="fas fa-triangle-exclamation" aria-hidden="true"></i>
                                    <p>
                                        <strong>¿Confirma la eliminación de esta cuenta?</strong>
                                        <span>Esta acción no se puede deshacer.</span>
                                    </p>
                                </div>

                                <div class="admin-delete-modal__record admin-user-delete__record-grid">
                                    <div>
                                        <span>Usuario</span>
                                        <strong id="adminUserDeleteName"></strong>
                                    </div>
                                    <div>
                                        <span>Email</span>
                                        <strong id="adminUserDeleteEmail"></strong>
                                    </div>
                                    <div>
                                        <span>ID / Cédula</span>
                                        <strong id="adminUserDeleteDisplayId"></strong>
                                    </div>
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn admin-user-modal__cancel" data-bs-dismiss="modal">Conservar usuario</button>
                                <button type="submit" class="btn admin-delete-modal__confirm" data-skip-loading="1">
                                    <i class="fas fa-trash-can"></i>
                                    <span>Eliminar definitivamente</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <!-- Solicitudes Tab -->
            <div class="tab-pane fade" id="solicitudes" role="tabpanel">
                <div id="solicitudes-content">
                    <div class="text-center mt-5">
                        <i class="fas fa-spinner fa-spin fa-2x"></i>
                        <p class="mt-2">Cargando solicitudes de productos...</p>
                    </div>
                </div>
            </div>

            <!-- Movimientos Tab -->
            <div class="tab-pane fade" id="movimientos" role="tabpanel">
                <div id="movimientos-content">
                    <div class="text-center mt-5">
                        <i class="fas fa-spinner fa-spin fa-2x"></i>
                        <p class="mt-2">Cargando movimientos de inventario...</p>
                    </div>
                </div>
            </div>

            <!-- Facturas Tab -->
            <div class="tab-pane fade" id="facturas" role="tabpanel">
                <div id="facturas-content">
                    <div class="text-center mt-5">
                        <i class="fas fa-spinner fa-spin fa-2x"></i>
                        <p class="mt-2">Cargando registro de facturas...</p>
                    </div>
                </div>
            </div>

            <!-- Reportes Tab -->
            <div class="tab-pane fade" id="reportes" role="tabpanel">
                <div id="reportes-content">
                    <div class="text-center mt-5">
                        <i class="fas fa-spinner fa-spin fa-2x"></i>
                        <p class="mt-2">Cargando reportes...</p>
                    </div>
                </div>
            </div>

            <!-- Cultivos Tab -->
            <div class="tab-pane fade" id="cultivos" role="tabpanel">
                <div id="cultivos-content">
                    <div class="text-center mt-5">
                        <i class="fas fa-spinner fa-spin fa-2x"></i>
                        <p class="mt-2">Cargando gestión de cultivos...</p>
                    </div>
                </div>
            </div>

            <!-- Pedidos Tab -->
            <div class="tab-pane fade" id="pedidos-proveedores" role="tabpanel">
                <div id="pedidos-proveedores-content">
                    <div class="text-center mt-5">
                        <i class="fas fa-spinner fa-spin fa-2x"></i>
                        <p class="mt-2">Cargando gestión de pedidos y proveedores...</p>
                    </div>
                </div>
            </div>
        </div>
            </div>
        </main>
    </div>

    <?php render_ada_chat(); ?>

    <script src="assets/vendor/bootstrap/bootstrap.bundle.min.js"></script>
    <script src="js/admin.js?v=<?= filemtime(__DIR__ . '/js/admin.js'); ?>"></script>
    <script>
        (function () {
            document.querySelectorAll('button, a').forEach((element) => {
                element.addEventListener('mousedown', () => element.classList.add('admin-pressed'));
                element.addEventListener('mouseup', () => element.classList.remove('admin-pressed'));
                element.addEventListener('mouseleave', () => element.classList.remove('admin-pressed'));
            });

            const historyButton = document.querySelector('[data-admin-lot-history]');
            const lotSelect = document.getElementById('selectorLote');
            const historyContent = document.getElementById('historialLoteContent');

            if (!historyButton || !lotSelect || !historyContent) return;

            const normalize = (value) => String(value || '')
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .trim()
                .toLowerCase();

            const clearHistoryMenus = () => {
                document.querySelectorAll('.app-table-filter__menu[data-app-table-owner="historialLoteContent"]').forEach(menu => menu.remove());
            };

            historyButton.addEventListener('click', async () => {
                const loteId = lotSelect.value;
                const icon = historyButton.querySelector('i');
                const label = historyButton.querySelector('span');
                const customSelectButton = lotSelect.nextElementSibling?.querySelector('.admin-lot-select__button');

                clearHistoryMenus();
                historyContent.replaceChildren();

                if (!loteId) {
                    historyContent.innerHTML = '<div class="alert alert-info"><i class="fas fa-circle-info"></i> Seleccione un lote para consultar su historial.</div>';
                    customSelectButton?.focus();
                    return;
                }

                historyButton.disabled = true;
                historyButton.classList.add('is-loading');
                if (icon) icon.className = 'fas fa-circle-notch fa-spin';
                if (label) label.textContent = 'Cargando historial...';
                historyContent.innerHTML = '<div class="text-center"><i class="fas fa-circle-notch fa-spin"></i><p>Cargando historial...</p></div>';

                try {
                    const response = await fetch(`lote_historial.php?id=${encodeURIComponent(loteId)}&_=${Date.now()}`, {
                        cache: 'no-store',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    });
                    if (!response.ok) throw new Error(`HTTP ${response.status}`);

                    const result = document.createElement('div');
                    result.className = 'admin-lot-history-result';
                    result.innerHTML = await response.text();
                    historyContent.replaceChildren(result);
                    window.AppTable?.enhance?.(result);
                } catch (error) {
                    console.error('Error al cargar historial del lote:', error);
                    historyContent.innerHTML = '<div class="alert alert-danger"><i class="fas fa-triangle-exclamation"></i> No se pudo cargar el historial. Intente nuevamente.</div>';
                } finally {
                    historyButton.disabled = false;
                    historyButton.classList.remove('is-loading');
                    if (icon) icon.className = 'fas fa-magnifying-glass';
                    if (label) label.textContent = 'Ver Historial Completo';
                }
            });
        })();
    </script>
</body>

</html>
