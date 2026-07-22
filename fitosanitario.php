<?php
require_once 'conexion.php';
require_once __DIR__ . '/includes/fitosanitario_helpers.php';
require_once __DIR__ . '/includes/fitosanitario_data.php';

fitosanitario_require_access();

$role = (string) $_SESSION['rol'];
$id_usuario = (string) $_SESSION['id_usuario'];

if ($role === 'Administrador') {
    redirect('admin.php#fitosanitario');
}

$lotes = $role === 'Agricultor' ? fitosanitario_lotes_for_farmer($conn, $id_usuario) : [];
$registros = fitosanitario_records($conn, $role, $id_usuario);
$productosFitosanitarios = fitosanitario_inventory_products($conn);
$canCreate = $role === 'Agricultor';
$totalRegistros = count($registros);
$registrosPendientes = count(array_filter($registros, static fn ($registro) => in_array($registro['estado'], ['Pendiente', 'En tratamiento'], true)));
$registrosControlados = count(array_filter($registros, static fn ($registro) => $registro['estado'] === 'Controlado'));
$registrosAlta = count(array_filter($registros, static fn ($registro) => ($registro['severidad'] ?? '') === 'Alta'));
?>
<?php render_head('Control Fitosanitario', [
    'https://fonts.googleapis.com/css2?family=Raleway:wght@500;600;700;800;900&family=Roboto+Condensed:wght@400;500;600;700;800;900&display=swap',
    'assets/vendor/bootstrap/bootstrap.min.css',
    'assets/vendor/fontawesome/css/all.min.css',
    'css/admin.css?v=' . filemtime(__DIR__ . '/css/admin.css'),
]); ?>
<body class="farmer-dashboard-page admin-dashboard-page farmer-admin-page phytosanitary-page">
    <div class="admin-tablet-shell">
        <aside class="sidebar" id="mainSidebar" aria-label="Navegación principal">
            <div class="logo-container">
                <div class="admin-sidebar-logo">
                    <span class="material-symbols-outlined" aria-hidden="true">agriculture</span>
                </div>
                <span class="nav-label admin-sidebar-brand">SembriExport</span>
            </div>

            <nav class="app-sidebar-nav admin-reference-nav">
                <?php if ($role === 'Bodeguero'): ?>
                    <a class="nav-item app-sidebar-link" href="bodeguero.php" title="Bodega">
                        <span class="material-symbols-outlined" aria-hidden="true">warehouse</span>
                        <span class="nav-label">Bodega</span>
                    </a>
                    <a class="nav-item app-sidebar-link active" href="fitosanitario.php" title="Fitosanitario">
                        <span class="material-symbols-outlined" aria-hidden="true">health_and_safety</span>
                        <span class="nav-label">Fitosanitario</span>
                    </a>
                    <a class="nav-item app-sidebar-link" href="cosechas.php" title="Cosecha">
                        <span class="material-symbols-outlined" aria-hidden="true">agriculture</span>
                        <span class="nav-label">Cosecha</span>
                    </a>
                    <a class="nav-item app-sidebar-link" href="poscosecha.php" title="Poscosecha">
                        <span class="material-symbols-outlined" aria-hidden="true">inventory_2</span>
                        <span class="nav-label">Poscosecha</span>
                    </a>
                    <a class="nav-item app-sidebar-link" href="bodeguero_facturas.php" title="Facturas">
                        <span class="material-symbols-outlined" aria-hidden="true">receipt_long</span>
                        <span class="nav-label">Facturas</span>
                    </a>
                    <a class="nav-item app-sidebar-link" href="imprimir_solicitudes.php" title="Solicitudes">
                        <span class="material-symbols-outlined" aria-hidden="true">assignment</span>
                        <span class="nav-label">Solicitudes</span>
                    </a>
                <?php else: ?>
                    <a class="nav-item app-sidebar-link" href="agricultor.php" title="Dashboard">
                        <span class="material-symbols-outlined" aria-hidden="true">dashboard</span>
                        <span class="nav-label">Dashboard</span>
                    </a>
                    <a class="nav-item app-sidebar-link" href="calcular_insumos.php" title="Calculadora">
                        <span class="material-symbols-outlined" aria-hidden="true">calculate</span>
                        <span class="nav-label">Calculadora</span>
                    </a>
                    <a class="nav-item app-sidebar-link active" href="fitosanitario.php" title="Fitosanitario">
                        <span class="material-symbols-outlined" aria-hidden="true">health_and_safety</span>
                        <span class="nav-label">Fitosanitario</span>
                    </a>
                    <a class="nav-item app-sidebar-link" href="cosechas.php" title="Cosecha">
                        <span class="material-symbols-outlined" aria-hidden="true">agriculture</span>
                        <span class="nav-label">Cosecha</span>
                    </a>
                    <a class="nav-item app-sidebar-link" href="poscosecha.php" title="Poscosecha">
                        <span class="material-symbols-outlined" aria-hidden="true">inventory_2</span>
                        <span class="nav-label">Poscosecha</span>
                    </a>
                    <a class="nav-item app-sidebar-link" href="historial_solicitudes.php" title="Historial">
                        <span class="material-symbols-outlined" aria-hidden="true">route</span>
                        <span class="nav-label">Historial</span>
                    </a>
                <?php endif; ?>
            </nav>

            <div class="admin-sidebar-actions">
                <a class="nav-item" href="logout.php" title="Cerrar sesión">
                    <span class="material-symbols-outlined" aria-hidden="true">logout</span>
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
                        <p>Registra incidencias, tratamientos y productos aplicados por lote.</p>
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
                                <strong><?php echo e($role); ?></strong>
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

            <div class="container farmer-dashboard admin-dashboard phytosanitary-dashboard mt-4">
                <?php render_flash_messages(); ?>

                <section class="farmer-page-heading admin-page-heading farmer-dashboard-hero">
                    <div class="farmer-hero-copy">
                        <span class="farmer-kicker">Sanidad agrícola</span>
                        <h1>Control Fitosanitario</h1>
                        <p>Registra, consulta y da seguimiento a incidencias por lote hasta controlar el problema.</p>
                    </div>
                    <div class="farmer-hero-status">
                        <span class="farmer-hero-status-icon"><span class="material-symbols-outlined" aria-hidden="true">health_and_safety</span></span>
                        <div>
                            <small>Estado del módulo</small>
                            <strong><span class="material-symbols-outlined" aria-hidden="true">circle</span> <?php echo $role === 'Bodeguero' ? 'Consulta de productos' : 'Seguimiento activo'; ?></strong>
                        </div>
                    </div>
                </section>

                <section class="farmer-stats-grid phytosanitary-stats-grid" aria-label="Resumen fitosanitario">
                    <article class="farmer-stat-card farmer-stat-card--total">
                        <div class="farmer-stat-top">
                            <span class="farmer-stat-icon"><span class="material-symbols-outlined" aria-hidden="true">clinical_notes</span></span>
                            <span class="farmer-stat-status">Registros</span>
                        </div>
                        <strong><?php echo $totalRegistros; ?></strong>
                        <p>Reportes disponibles</p>
                        <span class="farmer-stat-detail"><span class="material-symbols-outlined" aria-hidden="true">fact_check</span> Historial trazable</span>
                    </article>

                    <article class="farmer-stat-card farmer-stat-card--riego">
                        <div class="farmer-stat-top">
                            <span class="farmer-stat-icon"><span class="material-symbols-outlined" aria-hidden="true">pending_actions</span></span>
                            <span class="farmer-stat-status">Seguimiento</span>
                        </div>
                        <strong><?php echo $registrosPendientes; ?></strong>
                        <p>Pendientes o en tratamiento</p>
                        <span class="farmer-stat-detail"><span class="material-symbols-outlined" aria-hidden="true">monitor_heart</span> Requieren revisión</span>
                    </article>

                    <article class="farmer-stat-card farmer-stat-card--siembra">
                        <div class="farmer-stat-top">
                            <span class="farmer-stat-icon"><span class="material-symbols-outlined" aria-hidden="true">verified</span></span>
                            <span class="farmer-stat-status">Control</span>
                        </div>
                        <strong><?php echo $registrosControlados; ?></strong>
                        <p>Casos controlados</p>
                        <span class="farmer-stat-detail"><span class="material-symbols-outlined" aria-hidden="true">task_alt</span> Cierre sanitario</span>
                    </article>

                    <article class="farmer-stat-card farmer-stat-card--cosecha">
                        <div class="farmer-stat-top">
                            <span class="farmer-stat-icon"><span class="material-symbols-outlined" aria-hidden="true">priority_high</span></span>
                            <span class="farmer-stat-status">Alerta</span>
                        </div>
                        <strong><?php echo $registrosAlta; ?></strong>
                        <p>Severidad alta</p>
                        <span class="farmer-stat-detail"><span class="material-symbols-outlined" aria-hidden="true">report</span> Atención prioritaria</span>
                    </article>
                </section>

    <?php if ($canCreate): ?>
        <section class="phytosanitary-panel">
            <form method="POST" action="fitosanitario_acciones.php" class="farmer-form phytosanitary-form">
                <input type="hidden" name="accion" value="crear_registro">

                <div class="record-hero phytosanitary-form__hero">
                    <div>
                        <span class="farmer-kicker">Nuevo reporte</span>
                        <h2>Registrar incidencia fitosanitaria</h2>
                        <p>Seleccione el lote afectado y documente la detección inicial.</p>
                    </div>
                    <span class="record-hero-icon" aria-hidden="true"><span class="material-symbols-outlined">pest_control</span></span>
                </div>

                <div class="farmer-form-grid record-field-grid phytosanitary-form__grid">
                    <label class="record-field-card">
                        <span>Lote</span>
                        <select name="id_lote" class="form-select" required>
                            <option value="">Seleccione un lote</option>
                            <?php foreach ($lotes as $lote): ?>
                                <option value="<?php echo (int) $lote['id_lote']; ?>" data-area="<?php echo e($lote['area']); ?>">
                                    Lote #<?php echo (int) $lote['id_lote']; ?> - <?php echo e($lote['ubicacion']); ?> (<?php echo e($lote['cultivo'] ?: 'Sin cultivo'); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label class="record-field-card">
                        <span>Tipo</span>
                        <select name="tipo" class="form-select" required>
                            <option value="">Seleccione</option>
                            <option value="Plaga">Plaga</option>
                            <option value="Enfermedad">Enfermedad</option>
                            <option value="Hongo">Hongo</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </label>

                    <label class="record-field-card">
                        <span>Nombre del problema</span>
                        <input type="text" name="nombre_problema" class="form-control" maxlength="200" required>
                    </label>

                    <label class="record-field-card">
                        <span>Nivel de severidad</span>
                        <select name="severidad" class="form-select" required>
                            <option value="">Seleccione</option>
                            <option value="Baja">Baja</option>
                            <option value="Media">Media</option>
                            <option value="Alta">Alta</option>
                        </select>
                    </label>

                    <label class="record-field-card">
                        <span>Fecha de detección</span>
                        <input type="date" name="fecha_deteccion" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                    </label>

                    <label class="record-field-card">
                        <span>Fecha de aplicación</span>
                        <input type="date" name="fecha_aplicacion" class="form-control">
                    </label>

                    <label class="record-field-card">
                        <span>Producto aplicado</span>
                        <select name="id_insumo" class="form-select" data-fito-product-select>
                            <option value="">Sin producto aplicado</option>
                            <?php foreach ($productosFitosanitarios as $producto): ?>
                                <option
                                    value="<?php echo (int) $producto['id_insumos']; ?>"
                                    data-stock="<?php echo e($producto['cantidad']); ?>"
                                    data-unit="<?php echo e($producto['unidad_medida']); ?>"
                                    data-dose="<?php echo e($producto['dosis_recomendada']); ?>"
                                    data-dose-unit="<?php echo e($producto['unidad_dosis']); ?>"
                                    data-application-unit="<?php echo e($producto['unidad_aplicacion']); ?>">
                                    <?php echo e($producto['nombre']); ?> · <?php echo e($producto['ingrediente_activo'] ?: $producto['tipo_producto']); ?>
                                    <?php if ($producto['unidad_medida']): ?>
                                        (<?php echo e($producto['cantidad']); ?> <?php echo e($producto['unidad_medida']); ?>)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="phytosanitary-stock-hint" data-fito-stock-hint>Seleccione un producto para ver stock disponible.</small>
                        <div class="phytosanitary-dose-hint" data-fito-dose-hint></div>
                    </label>

                    <section class="phytosanitary-dose-panel phytosanitary-field--wide" data-fito-dose-panel>
                        <div class="phytosanitary-dose-card">
                            <span>Dosis recomendada</span>
                            <strong data-fito-recommended-display>Seleccione producto</strong>
                            <small>Solo lectura</small>
                        </div>
                        <label class="phytosanitary-dose-card">
                            <span>Dosis aplicada</span>
                            <input type="number" name="dosis_aplicada" class="form-control" min="0.01" step="0.01" data-fito-applied-dose>
                            <small>Unidad: <b data-fito-dose-unit>--</b></small>
                        </label>
                        <div class="phytosanitary-dose-card">
                            <span>Cantidad sugerida</span>
                            <strong data-fito-suggested-display>--</strong>
                            <small>Solo lectura</small>
                        </div>
                        <label class="phytosanitary-dose-card">
                            <span>Cantidad para entrega</span>
                            <input type="number" name="cantidad_aplicada" class="form-control" min="0.01" step="0.01" data-fito-applied-quantity>
                            <small>Editable</small>
                        </label>
                        <div class="phytosanitary-dose-warning phytosanitary-dose-card--wide" data-fito-adjustment-warning hidden>
                            ⚠️ La dosis aplicada es diferente de la dosis recomendada. Al guardar el tratamiento deberá justificar el motivo del cambio.
                        </div>
                        <label class="phytosanitary-dose-card phytosanitary-dose-card--wide" data-fito-adjustment-wrap hidden>
                            <span>Motivo del ajuste</span>
                            <textarea name="motivo_ajuste" class="form-control" rows="3" data-fito-adjustment-reason></textarea>
                        </label>
                    </section>

                    <label class="record-field-card phytosanitary-field--wide">
                        <span>Descripción</span>
                        <textarea name="descripcion" class="form-control" rows="3" required></textarea>
                    </label>

                    <label class="record-field-card phytosanitary-field--wide">
                        <span>Observaciones</span>
                        <textarea name="observaciones" class="form-control" rows="3"></textarea>
                    </label>
                </div>

                <button type="submit" class="btn w-100 farmer-submit farmer-action-button farmer-action-button--primary">
                    <span class="material-symbols-outlined" aria-hidden="true">health_and_safety</span>
                    <span>Guardar reporte fitosanitario</span>
                </button>
            </form>
        </section>
    <?php endif; ?>

    <section class="phytosanitary-panel">
        <div class="farmer-section-heading">
            <div>
                <h2><?php echo $role === 'Bodeguero' ? 'Productos fitosanitarios utilizados' : 'Historial fitosanitario'; ?></h2>
                <p><?php echo count($registros); ?> registros disponibles</p>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle phytosanitary-table" data-app-table>
                <thead>
                    <tr>
                        <th>Lote</th>
                        <?php if ($role !== 'Bodeguero'): ?>
                            <th>Problema</th>
                            <th>Severidad</th>
                        <?php endif; ?>
                        <th>Producto</th>
                        <th>Dosis</th>
                        <th>Aplicación</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$registros): ?>
                        <tr>
                            <td colspan="<?php echo $role === 'Bodeguero' ? 6 : 8; ?>" class="app-empty-state">
                                No hay registros fitosanitarios disponibles.
                            </td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($registros as $registro): ?>
                        <?php
                        $canEdit = fitosanitario_can_edit($conn, $role, $id_usuario, (int) $registro['id_control']);
                        $canTreat = fitosanitario_can_add_treatment($conn, $role, $id_usuario, (int) $registro['id_control']);
                        ?>
                        <tr>
                            <td>
                                <strong>Lote #<?php echo (int) $registro['id_lote']; ?></strong>
                                <small class="d-block text-muted"><?php echo e($registro['lote_ubicacion']); ?></small>
                            </td>
                            <?php if ($role !== 'Bodeguero'): ?>
                                <td>
                                    <strong><?php echo e($registro['nombre_problema']); ?></strong>
                                    <small class="d-block text-muted"><?php echo e($registro['tipo']); ?></small>
                                </td>
                                <td>
                                    <span class="app-table-status-capsule app-table-status-capsule--<?php echo e(fitosanitario_severity_tone($registro['severidad'])); ?>">
                                        <?php echo e($registro['severidad']); ?>
                                    </span>
                                </td>
                            <?php endif; ?>
                            <td><?php echo e($registro['producto_aplicado'] ?: 'Sin producto'); ?></td>
                            <td><?php echo e($registro['dosis'] ?: '-'); ?></td>
                            <td><?php echo $registro['fecha_aplicacion'] ? date('d/m/Y', strtotime($registro['fecha_aplicacion'])) : '-'; ?></td>
                            <td>
                                <span class="app-table-status-capsule app-table-status-capsule--<?php echo e(fitosanitario_status_tone($registro['estado'])); ?>">
                                    <?php echo e($registro['estado']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="phytosanitary-actions">
                                    <button type="button" class="btn btn-sm btn-outline-info" data-fito-detail="<?php echo (int) $registro['id_control']; ?>">
                                        <span class="material-symbols-outlined" aria-hidden="true">visibility</span>
                                    </button>
                                    <?php if ($canEdit): ?>
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-primary"
                                            data-fito-edit
                                            data-id="<?php echo (int) $registro['id_control']; ?>"
                                            data-lote="<?php echo (int) $registro['id_lote']; ?>"
                                            data-id-insumo="<?php echo e($registro['id_insumo']); ?>"
                                            data-area="<?php echo e($registro['lote_area']); ?>"
                                            data-tipo="<?php echo e($registro['tipo']); ?>"
                                            data-problema="<?php echo e($registro['nombre_problema']); ?>"
                                            data-severidad="<?php echo e($registro['severidad']); ?>"
                                            data-descripcion="<?php echo e($registro['descripcion']); ?>"
                                            data-producto="<?php echo e($registro['producto_aplicado']); ?>"
                                            data-dosis="<?php echo e($registro['dosis']); ?>"
                                            data-fecha-deteccion="<?php echo e($registro['fecha_deteccion']); ?>"
                                            data-fecha-aplicacion="<?php echo e($registro['fecha_aplicacion']); ?>"
                                            data-observaciones="<?php echo e($registro['observaciones']); ?>">
                                            <span class="material-symbols-outlined" aria-hidden="true">edit</span>
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($canTreat): ?>
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-success"
                                            data-fito-treatment="<?php echo (int) $registro['id_control']; ?>"
                                            data-area="<?php echo e($registro['lote_area']); ?>">
                                            <span class="material-symbols-outlined" aria-hidden="true">medication</span>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
            </div>
        </main>
    </div>

<?php if ($role === 'Agricultor'): ?>
    <div class="modal fade phytosanitary-modal" id="fitoEditModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <form method="POST" action="fitosanitario_acciones.php">
                    <input type="hidden" name="accion" value="editar_registro">
                    <input type="hidden" name="id_control" data-fito-edit-id>
                    <div class="modal-header">
                        <span class="admin-premium-modal__icon"><span class="material-symbols-outlined" aria-hidden="true">edit_note</span></span>
                        <div>
                            <span class="farmer-kicker">Edición fitosanitaria</span>
                            <h2 class="modal-title">Editar registro</h2>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="farmer-form-grid record-field-grid phytosanitary-form__grid">
                            <label class="record-field-card">
                                <span>Lote</span>
                                <select name="id_lote" class="form-select" data-fito-edit-lote required>
                                    <?php foreach ($lotes as $lote): ?>
                                        <option value="<?php echo (int) $lote['id_lote']; ?>" data-area="<?php echo e($lote['area']); ?>">Lote #<?php echo (int) $lote['id_lote']; ?> - <?php echo e($lote['ubicacion']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label class="record-field-card">
                                <span>Tipo</span>
                                <select name="tipo" class="form-select" data-fito-edit-tipo required>
                                    <option value="Plaga">Plaga</option>
                                    <option value="Enfermedad">Enfermedad</option>
                                    <option value="Hongo">Hongo</option>
                                    <option value="Otro">Otro</option>
                                </select>
                            </label>
                            <label class="record-field-card">
                                <span>Nombre del problema</span>
                                <input type="text" name="nombre_problema" class="form-control" data-fito-edit-problema maxlength="200" required>
                            </label>
                            <label class="record-field-card">
                                <span>Severidad</span>
                                <select name="severidad" class="form-select" data-fito-edit-severidad required>
                                    <option value="Baja">Baja</option>
                                    <option value="Media">Media</option>
                                    <option value="Alta">Alta</option>
                                </select>
                            </label>
                            <label class="record-field-card">
                                <span>Fecha de detección</span>
                                <input type="date" name="fecha_deteccion" class="form-control" data-fito-edit-fecha-deteccion required>
                            </label>
                            <label class="record-field-card">
                                <span>Fecha de aplicación</span>
                                <input type="date" name="fecha_aplicacion" class="form-control" data-fito-edit-fecha-aplicacion>
                            </label>
                            <label class="record-field-card">
                                <span>Producto aplicado</span>
                                <select name="id_insumo" class="form-select" data-fito-edit-producto data-fito-product-select>
                                    <option value="">Sin producto aplicado</option>
                                    <?php foreach ($productosFitosanitarios as $producto): ?>
                                        <option
                                            value="<?php echo (int) $producto['id_insumos']; ?>"
                                            data-stock="<?php echo e($producto['cantidad']); ?>"
                                            data-unit="<?php echo e($producto['unidad_medida']); ?>"
                                            data-dose="<?php echo e($producto['dosis_recomendada']); ?>"
                                            data-dose-unit="<?php echo e($producto['unidad_dosis']); ?>"
                                            data-application-unit="<?php echo e($producto['unidad_aplicacion']); ?>">
                                            <?php echo e($producto['nombre']); ?> · <?php echo e($producto['ingrediente_activo'] ?: $producto['tipo_producto']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="phytosanitary-stock-hint" data-fito-stock-hint>Seleccione un producto para ver stock disponible.</small>
                                <div class="phytosanitary-dose-hint" data-fito-dose-hint></div>
                            </label>
                            <label class="record-field-card phytosanitary-field--wide">
                                <span>Descripción</span>
                                <textarea name="descripcion" class="form-control" data-fito-edit-descripcion rows="3" required></textarea>
                            </label>
                            <label class="record-field-card phytosanitary-field--wide">
                                <span>Observaciones</span>
                                <textarea name="observaciones" class="form-control" data-fito-edit-observaciones rows="3"></textarea>
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn warehouse-modal-back" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn warehouse-modal-confirm">
                            <span class="material-symbols-outlined" aria-hidden="true">check</span>
                            <span>Guardar cambios</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if ($role !== 'Bodeguero'): ?>
    <div class="modal fade phytosanitary-modal" id="fitoTreatmentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="fitosanitario_acciones.php">
                    <input type="hidden" name="accion" value="agregar_tratamiento">
                    <input type="hidden" name="id_control" data-fito-treatment-id>
                    <input type="hidden" data-fito-treatment-area>
                    <div class="modal-header">
                        <span class="admin-premium-modal__icon"><span class="material-symbols-outlined" aria-hidden="true">medical_services</span></span>
                        <div>
                            <span class="farmer-kicker">Historial de tratamiento</span>
                            <h2 class="modal-title">Agregar tratamiento</h2>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="phytosanitary-treatment-grid">
                            <label class="record-field-card">
                                <span>Producto aplicado</span>
                                <select name="id_insumo" class="form-select" data-fito-product-select required>
                                    <option value="">Seleccione producto</option>
                                    <?php foreach ($productosFitosanitarios as $producto): ?>
                                        <option
                                            value="<?php echo (int) $producto['id_insumos']; ?>"
                                            data-stock="<?php echo e($producto['cantidad']); ?>"
                                            data-unit="<?php echo e($producto['unidad_medida']); ?>"
                                            data-dose="<?php echo e($producto['dosis_recomendada']); ?>"
                                            data-dose-unit="<?php echo e($producto['unidad_dosis']); ?>"
                                            data-application-unit="<?php echo e($producto['unidad_aplicacion']); ?>">
                                            <?php echo e($producto['nombre']); ?> · <?php echo e($producto['ingrediente_activo'] ?: $producto['tipo_producto']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="phytosanitary-stock-hint" data-fito-stock-hint>Seleccione un producto para ver stock disponible.</small>
                                <div class="phytosanitary-dose-hint" data-fito-dose-hint></div>
                            </label>
                            <section class="phytosanitary-dose-panel phytosanitary-field--wide" data-fito-dose-panel>
                                <div class="phytosanitary-dose-card">
                                    <span>Dosis recomendada</span>
                                    <strong data-fito-recommended-display>Seleccione producto</strong>
                                    <small>Solo lectura</small>
                                </div>
                                <label class="phytosanitary-dose-card">
                                    <span>Dosis aplicada</span>
                                    <input type="number" name="dosis_aplicada" class="form-control" min="0.01" step="0.01" data-fito-applied-dose required>
                                    <small>Unidad: <b data-fito-dose-unit>--</b></small>
                                </label>
                                <div class="phytosanitary-dose-card">
                                    <span>Cantidad sugerida</span>
                                    <strong data-fito-suggested-display>--</strong>
                                    <small>Solo lectura</small>
                                </div>
                                <label class="phytosanitary-dose-card">
                                    <span>Cantidad para entrega</span>
                                    <input type="number" name="cantidad_aplicada" class="form-control" min="0.01" step="0.01" data-fito-applied-quantity required>
                                    <small>Editable</small>
                                </label>
                                <div class="phytosanitary-dose-warning phytosanitary-dose-card--wide" data-fito-adjustment-warning hidden>
                                    ⚠️ La dosis aplicada es diferente de la dosis recomendada. Al guardar el tratamiento deberá justificar el motivo del cambio.
                                </div>
                                <label class="phytosanitary-dose-card phytosanitary-dose-card--wide" data-fito-adjustment-wrap hidden>
                                    <span>Motivo del ajuste</span>
                                    <textarea name="motivo_ajuste" class="form-control" rows="3" data-fito-adjustment-reason></textarea>
                                </label>
                            </section>
                            <label class="record-field-card">
                                <span>Fecha de aplicación</span>
                                <input type="date" name="fecha_aplicacion" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                            </label>
                            <label class="record-field-card">
                                <span>Estado resultante</span>
                                <select name="estado_resultante" class="form-select" required>
                                    <option value="En tratamiento">En tratamiento</option>
                                    <option value="Controlado">Controlado</option>
                                    <option value="Pendiente">Pendiente</option>
                                </select>
                            </label>
                            <label class="record-field-card phytosanitary-field--wide">
                                <span>Observaciones</span>
                                <textarea name="observaciones" class="form-control" rows="3"></textarea>
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn warehouse-modal-back" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn warehouse-modal-confirm">
                            <span class="material-symbols-outlined" aria-hidden="true">check</span>
                            <span>Registrar tratamiento</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="modal fade phytosanitary-modal" id="fitoDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <span class="admin-premium-modal__icon"><span class="material-symbols-outlined" aria-hidden="true">health_and_safety</span></span>
                <div>
                    <span class="farmer-kicker">Detalle fitosanitario</span>
                    <h2 class="modal-title">Historial del registro</h2>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body" data-fito-detail-content>
                <div class="text-center mt-4"><span class="material-symbols-outlined" aria-hidden="true">hourglass_top</span><p>Cargando...</p></div>
            </div>
        </div>
    </div>
</div>

<?php render_ada_chat(); ?>
<?php render_scripts([
    'js/admin-forms.js?v=' . filemtime(__DIR__ . '/js/admin-forms.js'),
    'js/fitosanitario.js?v=' . filemtime(__DIR__ . '/js/fitosanitario.js'),
]); ?>
</body>
</html>
