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

// Estadísticas adicionales para el admin
$total_usuarios = $conn->query("SELECT COUNT(*) as count FROM usuarios")->fetch_assoc()['count'];
$agricultores = $conn->query("SELECT COUNT(*) as count FROM usuarios WHERE rol='Agricultor'")->fetch_assoc()['count'];

// Obtener notificaciones no leídas
$notificaciones = $conn->query("SELECT * FROM notificaciones WHERE leida = 0 ORDER BY fecha DESC");
?>

<?php render_head('Panel Administrador'); ?>

<body class="farmer-dashboard-page admin-dashboard-page">
    <?php render_app_nav('fas fa-seedling', 'SembriExport Admin', [
        ['href' => '#', 'label' => 'Bienvenido, ' . current_user_name(), 'class' => 'btn btn-link text-white text-decoration-none btn-sm disabled'],
        ['href' => 'logout.php', 'label' => 'Cerrar Sesión', 'icon' => 'fas fa-sign-out-alt', 'class' => 'btn btn-outline-light btn-sm'],
    ]); ?>

    <div class="container farmer-dashboard admin-dashboard mt-4">
        <?php render_flash_messages(); ?>

        <section class="farmer-page-heading admin-page-heading">
            <div class="admin-heading-copy">
                <span class="farmer-kicker">Administración</span>
                <h1>Centro de Control</h1>
                <p>Resumen general de usuarios, cultivos e inventario de SembriExport.</p>
            </div>
            <div class="admin-heading-actions">
                <span class="admin-status-chip">
                    <i class="fas fa-bell"></i>
                    <strong><?php echo $notificaciones->num_rows; ?></strong>
                    notificaciones
                </span>
                <span class="admin-status-chip admin-status-chip--warning">
                    <i class="fas fa-triangle-exclamation"></i>
                    <strong><?php echo $total_insumos_criticos + $total_productos_bajos; ?></strong>
                    alertas
                </span>
            </div>
        </section>

        <div class="tab-content" id="adminTabsContent">
            <!-- Dashboard Tab -->
            <div class="tab-pane fade show active" id="dashboard" role="tabpanel">
                <!-- Estadísticas Principales -->
                <section class="admin-overview-section" aria-labelledby="admin-overview-title">
                    <div class="admin-section-heading">
                        <div>
                            <span class="admin-section-eyebrow">Vista general</span>
                            <h2 id="admin-overview-title">Indicadores principales</h2>
                        </div>
                        <span class="admin-live-status"><i class="fas fa-circle"></i> Datos actualizados</span>
                    </div>

                    <div class="admin-metrics-grid">
                        <article class="admin-metric-card admin-metric-card--users">
                            <div class="admin-metric-top">
                                <span class="admin-metric-icon"><i class="fas fa-users"></i></span>
                                <span class="admin-metric-tag">Usuarios</span>
                            </div>
                            <strong class="admin-metric-value"><?php echo $total_usuarios; ?></strong>
                            <p>Total de usuarios registrados</p>
                            <span class="admin-metric-detail"><i class="fas fa-user-tie"></i> <?php echo $agricultores; ?> agricultores activos</span>
                        </article>

                        <article class="admin-metric-card admin-metric-card--crops">
                            <div class="admin-metric-top">
                                <span class="admin-metric-icon"><i class="fas fa-seedling"></i></span>
                                <span class="admin-metric-tag">Producción</span>
                            </div>
                            <strong class="admin-metric-value"><?php echo $total_cultivos; ?></strong>
                            <p>Cultivos registrados</p>
                            <span class="admin-metric-detail"><i class="fas fa-leaf"></i> Seguimiento agrícola</span>
                        </article>

                        <article class="admin-metric-card admin-metric-card--lots">
                            <div class="admin-metric-top">
                                <span class="admin-metric-icon"><i class="fas fa-map-location-dot"></i></span>
                                <span class="admin-metric-tag">Territorio</span>
                            </div>
                            <strong class="admin-metric-value"><?php echo $total_lotes; ?></strong>
                            <p>Lotes activos</p>
                            <span class="admin-metric-detail"><i class="fas fa-location-dot"></i> Áreas bajo gestión</span>
                        </article>

                        <article class="admin-metric-card admin-metric-card--farmers">
                            <div class="admin-metric-top">
                                <span class="admin-metric-icon"><i class="fas fa-user-tie"></i></span>
                                <span class="admin-metric-tag">Equipo</span>
                            </div>
                            <strong class="admin-metric-value"><?php echo $agricultores; ?></strong>
                            <p>Agricultores vinculados</p>
                            <span class="admin-metric-detail"><i class="fas fa-circle-check"></i> Personal registrado</span>
                        </article>
                    </div>
                </section>

                <section class="admin-dashboard-row">
                    <div class="admin-notification-panel">
                        <div class="admin-panel-heading">
                            <span class="admin-panel-icon"><i class="fas fa-bell"></i></span>
                            <div>
                                <span class="admin-section-eyebrow">Actividad reciente</span>
                                <h2>Centro de notificaciones</h2>
                            </div>
                            <span class="admin-panel-count"><?php echo $notificaciones->num_rows; ?> nuevas</span>
                        </div>
                        <div class="admin-notification-list">
                            <?php if ($notificaciones->num_rows > 0): ?>
                                <?php while ($notif = $notificaciones->fetch_assoc()): ?>
                                    <div class="admin-notification-item">
                                        <span class="admin-notification-dot"></span>
                                        <div>
                                            <p><?php echo htmlspecialchars($notif['mensaje']); ?></p>
                                            <time datetime="<?php echo htmlspecialchars($notif['fecha']); ?>">
                                                <i class="far fa-clock"></i> <?php echo htmlspecialchars($notif['fecha']); ?>
                                            </time>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="admin-empty-state">
                                    <i class="fas fa-check"></i>
                                    <div>
                                        <strong>Todo está al día</strong>
                                        <span>No tienes notificaciones pendientes.</span>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <aside class="admin-attention-panel">
                        <div class="admin-panel-heading">
                            <span class="admin-panel-icon admin-panel-icon--status" aria-hidden="true">
                                <i class="fas fa-shield-alt"></i>
                            </span>
                            <div>
                                <span class="admin-section-eyebrow">Estado operativo</span>
                                <h2>Atención requerida</h2>
                            </div>
                        </div>
                        <strong class="admin-attention-value"><?php echo $total_insumos_criticos + $total_productos_bajos; ?></strong>
                        <p>elementos requieren revisión de inventario.</p>
                        <div class="admin-attention-breakdown">
                            <span><i class="fas fa-flask"></i> Insumos agotados <strong><?php echo $total_insumos_criticos; ?></strong></span>
                            <span><i class="fas fa-box"></i> Productos bajos <strong><?php echo $total_productos_bajos; ?></strong></span>
                        </div>
                    </aside>
                </section>

                <!-- Estadísticas de inventario -->
                <section class="admin-inventory-summary" aria-labelledby="admin-inventory-title">
                    <div class="admin-section-heading">
                        <div>
                            <span class="admin-section-eyebrow">Control operativo</span>
                            <h2 id="admin-inventory-title">Resumen de inventario</h2>
                        </div>
                    </div>
                    <div class="admin-inventory-grid">
                        <article class="admin-inventory-card">
                            <span class="admin-inventory-icon"><i class="fas fa-map-marked-alt"></i></span>
                            <div>
                                <span>Total lotes</span>
                                <strong><?php echo $total_lotes; ?></strong>
                            </div>
                            <span class="admin-inventory-state admin-inventory-state--ok">Operativo</span>
                        </article>
                        <article class="admin-inventory-card">
                            <span class="admin-inventory-icon"><i class="fas fa-seedling"></i></span>
                            <div>
                                <span>Cultivos activos</span>
                                <strong><?php echo $total_cultivos; ?></strong>
                            </div>
                            <span class="admin-inventory-state admin-inventory-state--ok">En curso</span>
                        </article>
                        <article class="admin-inventory-card admin-inventory-card--danger">
                            <span class="admin-inventory-icon"><i class="fas fa-flask"></i></span>
                            <div>
                                <span>Insumos agotados</span>
                                <strong><?php echo $total_insumos_criticos; ?></strong>
                            </div>
                            <span class="admin-inventory-state">Revisar</span>
                        </article>
                        <article class="admin-inventory-card admin-inventory-card--warning">
                            <span class="admin-inventory-icon"><i class="fas fa-box-open"></i></span>
                            <div>
                                <span>Productos bajos</span>
                                <strong><?php echo $total_productos_bajos; ?></strong>
                            </div>
                            <span class="admin-inventory-state">Atención</span>
                        </article>
                    </div>
                </section>

                <!-- Alertas de inventario -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-danger text-white">
                                <h5><i class="fas fa-flask"></i> Alertas de Insumos Agrícolas</h5>
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
                                        <i class="fas fa-check-circle"></i> Todos los insumos tienen niveles normales
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-warning text-white">
                                <h5><i class="fas fa-boxes"></i> Alertas de Productos</h5>
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
                                        <i class="fas fa-check-circle"></i> Todos los productos tienen niveles normales
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Selector de lotes para historial -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-history"></i> Historial por Lote</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <label class="form-label">Seleccionar Lote:</label>
                                        <select class="form-control" id="selectorLote" onchange="cargarHistorialLote()">
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
                                    <div class="col-md-8">
                                        <button class="btn btn-info" onclick="cargarHistorialLote()">
                                            <i class="fas fa-search"></i> Ver Historial Completo
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
            <div class="modal fade" id="modalCrearUsuario" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Crear Nuevo Usuario</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form id="formCrearUsuario">
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label">Cédula (opcional)</label>
                                    <input type="text" class="form-control" name="cedula" placeholder="Ingrese cédula o deje vacío">
                                    <small class="form-text text-muted">Si no ingresa cédula, se asignará un ID automático</small>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Nombre Completo *</label>
                                    <input type="text" class="form-control" name="nombre" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Email *</label>
                                    <input type="email" class="form-control" name="email" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Contraseña *</label>
                                    <input type="password" class="form-control" name="contrasena" required>
                                    <small class="form-text text-muted">Mínimo 6 caracteres.</small>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Rol *</label>
                                    <select class="form-control" name="rol" required>
                                        <option value="">Seleccionar rol</option>
                                        <option value="Administrador">Administrador</option>
                                        <option value="Agricultor">Agricultor</option>
                                        <option value="Bodeguero">Bodeguero</option>
                                    </select>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-primary">Crear Usuario</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- MODAL EDITAR USUARIO -->
            <div class="modal fade" id="modalEditarUsuario" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Editar Usuario</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form id="formEditarUsuario">
                            <div class="modal-body">
                                <input type="hidden" name="action" value="editar">
                                <input type="hidden" name="id_usuario" id="edit_id">
                                <div class="mb-3">
                                    <label class="form-label">ID/Cédula</label>
                                    <input type="text" class="form-control" id="edit_id_display" disabled>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Nombre Completo *</label>
                                    <input type="text" class="form-control" name="nombre" id="edit_nombre" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Email *</label>
                                    <input type="email" class="form-control" name="email" id="edit_email" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Nueva Contraseña (opcional)</label>
                                    <input type="password" class="form-control" name="nueva_contrasena" id="edit_contrasena">
                                    <small class="form-text text-muted">Dejar vacío para mantener la actual.</small>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Rol *</label>
                                    <select class="form-control" name="rol" id="edit_rol" required>
                                        <option value="Administrador">Administrador</option>
                                        <option value="Agricultor">Agricultor</option>
                                        <option value="Bodeguero">Bodeguero</option>
                                    </select>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-primary">Actualizar Usuario</button>
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

    <?php render_ada_chat(); ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/admin.js?v=<?= filemtime(__DIR__ . '/js/admin.js'); ?>"></script>
    <script>
        // Funciones para el dashboard
        function cargarHistorialLote() {
            const loteId = document.getElementById('selectorLote').value;
            const contentDiv = document.getElementById('historialLoteContent');

            if (!loteId) {
                contentDiv.innerHTML = '<div class="alert alert-info">Seleccione un lote para ver su historial</div>';
                return;
            }

            contentDiv.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Cargando historial...</div>';

            fetch('lote_historial.php?id=' + loteId)
                .then(response => response.text())
                .then(data => contentDiv.innerHTML = data)
                .catch(error => {
                    console.error('Error:', error);
                    contentDiv.innerHTML = '<div class="alert alert-danger">Error al cargar el historial</div>';
                });
        }

        console.log('Admin panel inicializado correctamente');
    </script>
</body>

</html>
