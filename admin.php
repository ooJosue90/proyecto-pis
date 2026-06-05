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
            <div>
                <span class="farmer-kicker">Administración</span>
                <h1>Centro de Control</h1>
            </div>
            <div class="admin-heading-actions">
                <span><i class="fas fa-bell"></i> <?php echo $notificaciones->num_rows; ?> notificaciones</span>
                <span><i class="fas fa-triangle-exclamation"></i> <?php echo $total_insumos_criticos + $total_productos_bajos; ?> alertas</span>
            </div>
        </section>

        <div class="tab-content" id="adminTabsContent">
            <!-- Dashboard Tab -->
            <div class="tab-pane fade show active" id="dashboard" role="tabpanel">
                <!-- Estadísticas Principales -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card stats-card card-custom">
                            <div class="card-body text-center">
                                <i class="fas fa-users fa-2x text-primary mb-2"></i>
                                <h4 class="text-primary"><?php echo $total_usuarios; ?></h4>
                                <p class="mb-0">Total Usuarios</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3 mb-3">
                        <div class="card stats-card card-custom">
                            <div class="card-body text-center">
                                <i class="fas fa-leaf fa-2x text-success mb-2"></i>
                                <h4 class="text-success"><?php echo $total_cultivos; ?></h4>
                                <p class="mb-0">Cultivos Registrados</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3 mb-3">
                        <div class="card stats-card card-custom">
                            <div class="card-body text-center">
                                <i class="fas fa-map-marked-alt fa-2x text-warning mb-2"></i>
                                <h4 class="text-warning"><?php echo $total_lotes; ?></h4>
                                <p class="mb-0">Lotes Activos</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3 mb-3">
                        <div class="card stats-card card-custom">
                            <div class="card-body text-center">
                                <i class="fas fa-user-tie fa-2x text-info mb-2"></i>
                                <h4 class="text-info"><?php echo $agricultores; ?></h4>
                                <p class="mb-0">Agricultores</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Alertas críticas -->
                <?php if ($alertas_insumos->num_rows > 0 || $alertas_productos->num_rows > 0): ?>
                    <div class="row">
                        <div class="col-12 mb-4">
                            <div class="alert alert-danger">
                                <h5><i class="fas fa-exclamation-triangle"></i> Alertas Críticas</h5>
                                <p>Hay productos o insumos con inventario bajo que requieren atención inmediata.</p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- notificacion -->
                <?php if ($notificaciones->num_rows > 0): ?>
                    <div class="row">
                        <div class="col-12 mb-4">
                            <div class="alert alert-info">
                                <h5><i class="fas fa-bell"></i> Notificaciones</h5>
                                <ul class="mb-0">
                                    <?php while ($notif = $notificaciones->fetch_assoc()): ?>
                                        <li><?php echo htmlspecialchars($notif['mensaje']); ?> <small>(<?php echo $notif['fecha']; ?>)</small></li>
                                    <?php endwhile; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Estadísticas de inventario -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-primary">
                            <div class="card-body text-center">
                                <i class="fas fa-map-marked-alt fa-2x mb-2"></i>
                                <h3><?php echo $total_lotes; ?></h3>
                                <p class="mb-0">Total Lotes</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-success">
                            <div class="card-body text-center">
                                <i class="fas fa-seedling fa-2x mb-2"></i>
                                <h3><?php echo $total_cultivos; ?></h3>
                                <p class="mb-0">Cultivos Activos</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-danger">
                            <div class="card-body text-center">
                                <i class="fas fa-exclamation-circle fa-2x mb-2"></i>
                                <h3><?php echo $total_insumos_criticos; ?></h3>
                                <p class="mb-0">Insumos Agotados</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-warning">
                            <div class="card-body text-center">
                                <i class="fas fa-box fa-2x mb-2"></i>
                                <h3><?php echo $total_productos_bajos; ?></h3>
                                <p class="mb-0">Productos Bajos</p>
                            </div>
                        </div>
                    </div>
                </div>

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
    <script src="js/admin.js?v=20260605-notifications"></script>
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
