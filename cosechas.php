<?php
require_once 'conexion.php';
require_once __DIR__ . '/includes/cosecha_helpers.php';
require_once __DIR__ . '/includes/cosecha_data.php';

cosecha_require_role(['Agricultor', 'Bodeguero']);

$role = (string) $_SESSION['rol'];
$id_usuario = (string) $_SESSION['id_usuario'];
$lotes = $role === 'Agricultor' ? cosecha_lotes_for_farmer($conn, $id_usuario) : [];
$cosechas = cosecha_records($conn, $role, $id_usuario);
$metrics = cosecha_metrics($conn);
$qualityTotal = $metrics['kg_primera'] + $metrics['kg_segunda'] + $metrics['kg_descarte'];
$firstQualityRate = $qualityTotal > 0 ? ($metrics['kg_primera'] / $qualityTotal) * 100 : 0;
?>
<?php render_head('Cosecha', [
    'https://fonts.googleapis.com/css2?family=Raleway:wght@500;600;700;800;900&family=Roboto+Condensed:wght@400;500;600;700;800;900&display=swap',
    'assets/vendor/bootstrap/bootstrap.min.css',
    'assets/vendor/fontawesome/css/all.min.css',
    'css/admin.css?v=' . filemtime(__DIR__ . '/css/admin.css'),
]); ?>
<body class="farmer-dashboard-page <?php echo $role === 'Agricultor' ? 'admin-dashboard-page farmer-admin-page' : 'warehouse-dashboard-page'; ?>">
<?php if ($role === 'Agricultor'): ?>
    <div class="admin-tablet-shell">
        <aside class="sidebar" id="mainSidebar" aria-label="Navegación principal">
            <div class="logo-container">
                <div class="admin-sidebar-logo">
                    <span class="material-symbols-outlined" aria-hidden="true">agriculture</span>
                </div>
                <span class="nav-label admin-sidebar-brand">SembriExport</span>
            </div>

            <nav class="app-sidebar-nav admin-reference-nav">
                <a class="nav-item app-sidebar-link" href="agricultor.php" title="Dashboard">
                    <span class="material-symbols-outlined" aria-hidden="true">dashboard</span>
                    <span class="nav-label">Dashboard</span>
                </a>
                <a class="nav-item app-sidebar-link" href="calcular_insumos.php" title="Calculadora">
                    <span class="material-symbols-outlined" aria-hidden="true">calculate</span>
                    <span class="nav-label">Calculadora</span>
                </a>
                <a class="nav-item app-sidebar-link" href="fitosanitario.php" title="Fitosanitario">
                    <span class="material-symbols-outlined" aria-hidden="true">health_and_safety</span>
                    <span class="nav-label">Fitosanitario</span>
                </a>
                <a class="nav-item app-sidebar-link active" href="cosechas.php" title="Cosecha">
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
                        <p>Gestiona tu jornada agrícola con Verdeagro ERP</p>
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
                                <strong>Agricultor</strong>
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
<?php else: ?>
    <?php render_app_nav('fas fa-wheat-awn', 'Cosecha'); ?>
<?php endif; ?>

<div class="container farmer-dashboard harvest-module <?php echo $role === 'Agricultor' ? 'admin-dashboard' : 'warehouse-dashboard'; ?> mt-4">
    <?php render_flash_messages(); ?>

    <section class="harvest-hero farmer-page-heading <?php echo $role === 'Agricultor' ? 'admin-page-heading farmer-dashboard-hero' : 'warehouse-page-heading'; ?>">
        <div class="<?php echo $role === 'Agricultor' ? 'farmer-hero-copy' : ''; ?>">
            <span class="farmer-kicker">Producción agrícola</span>
            <h1>Control de Cosecha</h1>
            <p><?php echo $role === 'Agricultor'
                ? 'Registra y consulta la producción cosechada por lote.'
                : 'Consulta cosechas validadas y registra la recepción del producto cosechado.'; ?></p>
        </div>
        <?php if ($role !== 'Agricultor'): ?>
            <div class="warehouse-hero-status">
                <span><i class="fas fa-wheat-awn"></i></span>
                <div>
                    <small>Total validado</small>
                    <strong><?php echo number_format($metrics['kg_validados'], 2); ?> kg</strong>
                </div>
            </div>
        <?php endif; ?>
    </section>

    <section class="harvest-metrics-grid" aria-label="Indicadores de cosecha">
        <article class="harvest-metric-card harvest-metric-card--pending">
            <span class="harvest-metric-card__icon"><i class="fas fa-clock"></i></span>
            <div>
                <span>Pendientes</span>
                <strong><?php echo $metrics['cosechas_pendientes']; ?></strong>
                <small>En revisión administrativa</small>
            </div>
        </article>
        <article class="harvest-metric-card harvest-metric-card--validated">
            <span class="harvest-metric-card__icon"><i class="fas fa-scale-balanced"></i></span>
            <div>
                <span>Kg validados</span>
                <strong><?php echo number_format($metrics['kg_validados'], 2); ?></strong>
                <small>Validada o recibida</small>
            </div>
        </article>
        <article class="harvest-metric-card harvest-metric-card--first">
            <span class="harvest-metric-card__icon"><i class="fas fa-award"></i></span>
            <div>
                <span>Kg primera</span>
                <strong><?php echo number_format($metrics['kg_primera'], 2); ?></strong>
                <small><?php echo number_format($firstQualityRate, 1); ?>% de calidad clasificada</small>
            </div>
        </article>
        <article class="harvest-metric-card harvest-metric-card--control">
            <span class="harvest-metric-card__icon"><i class="fas fa-layer-group"></i></span>
            <div>
                <span>Segunda + descarte</span>
                <strong><?php echo number_format($metrics['kg_segunda'] + $metrics['kg_descarte'], 2); ?></strong>
                <small>Kg bajo seguimiento</small>
            </div>
        </article>
    </section>

    <?php if ($role === 'Agricultor'): ?>
        <section class="harvest-panel harvest-form-panel mb-4" aria-labelledby="harvest-form-title">
            <div class="harvest-panel__heading">
                <div>
                    <span class="farmer-kicker">Registro de campo</span>
                    <h5 id="harvest-form-title"><i class="fas fa-plus-circle"></i> Registrar cosecha</h5>
                </div>
                <span class="harvest-count-badge"><?php echo count($lotes); ?> lotes disponibles</span>
            </div>
            <div class="harvest-panel__body">
                <form method="POST" action="cosecha_acciones.php" class="farmer-form">
                    <input type="hidden" name="accion" value="guardar_cosecha">
                    <input type="hidden" name="id_cosecha" value="" data-cosecha-edit-id>
                    <div class="harvest-register-grid">
                        <div class="harvest-field harvest-field--wide">
                            <label class="form-label">Lote</label>
                            <select name="id_lote" class="form-select" required data-cosecha-lote>
                                <option value="">Seleccione un lote en cosecha</option>
                                <?php foreach ($lotes as $lote): ?>
                                    <option value="<?php echo (int) $lote['id_lote']; ?>">
                                        Lote #<?php echo (int) $lote['id_lote']; ?> - <?php echo e($lote['ubicacion']); ?> (<?php echo e($lote['cultivo'] ?: 'Sin cultivo'); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Solo aparecen lotes en etapa En cosecha y sin cosecha activa.</small>
                        </div>
                        <div class="harvest-field">
                            <label class="form-label">Fecha de cosecha</label>
                            <input type="date" name="fecha_cosecha" class="form-control" value="<?php echo date('Y-m-d'); ?>" required data-cosecha-fecha>
                        </div>
                        <div class="harvest-field">
                            <label class="form-label">Total cosechado (kg)</label>
                            <input type="number" name="cantidad_total_kg" class="form-control" min="0.01" step="0.01" required data-cosecha-total>
                        </div>
                        <div class="harvest-field">
                            <label class="form-label">Calidad primera (kg)</label>
                            <input type="number" name="calidad_primera_kg" class="form-control" min="0" step="0.01" value="0" required data-cosecha-primera>
                        </div>
                        <div class="harvest-field">
                            <label class="form-label">Calidad segunda (kg)</label>
                            <input type="number" name="calidad_segunda_kg" class="form-control" min="0" step="0.01" value="0" required data-cosecha-segunda>
                        </div>
                        <div class="harvest-field">
                            <label class="form-label">Descarte (kg)</label>
                            <input type="number" name="descarte_kg" class="form-control" min="0" step="0.01" value="0" required data-cosecha-descarte>
                        </div>
                        <div class="harvest-field harvest-field--wide">
                            <label class="form-label">Observaciones</label>
                            <textarea name="observaciones" class="form-control" rows="3" data-cosecha-observaciones></textarea>
                        </div>
                    </div>
                    <div class="d-flex gap-2 justify-content-end mt-3">
                        <button type="button" class="btn btn-outline-secondary d-none" data-cosecha-cancel-edit>Cancelar edición</button>
                        <button type="submit" class="btn harvest-save-button">
                            Guardar cosecha
                        </button>
                    </div>
                </form>
            </div>
        </section>
    <?php endif; ?>

    <section class="harvest-panel">
        <div class="harvest-panel__heading">
            <div>
                <span class="farmer-kicker">Seguimiento</span>
                <h5><i class="fas fa-clock-rotate-left"></i> Historial de cosechas</h5>
            </div>
            <span class="harvest-count-badge"><?php echo count($cosechas); ?> registros</span>
        </div>
        <div class="harvest-panel__body">
            <div class="table-responsive harvest-table-wrap">
            <table class="table table-hover align-middle harvest-table" data-app-table data-app-table-owner="harvest-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Fecha</th>
                        <th>Lote</th>
                        <th>Total</th>
                        <th>Primera</th>
                        <th>Segunda</th>
                        <th>Descarte</th>
                        <th>Estado</th>
                        <th>Observaciones</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$cosechas): ?>
                        <tr><td colspan="10" class="app-empty-state">No hay cosechas registradas.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($cosechas as $cosecha): ?>
                        <tr>
                            <td><?php echo (int) $cosecha['id_cosecha']; ?></td>
                            <td><?php echo e(date('d/m/Y', strtotime($cosecha['fecha_cosecha']))); ?></td>
                            <td><?php echo e($cosecha['lote_ubicacion']); ?><br><small><?php echo e($cosecha['cultivo_tipo'] ?: 'Sin cultivo'); ?></small></td>
                            <td><strong><?php echo number_format((float) $cosecha['cantidad_total_kg'], 2); ?> kg</strong></td>
                            <td><?php echo number_format((float) $cosecha['calidad_primera_kg'], 2); ?> kg</td>
                            <td><?php echo number_format((float) $cosecha['calidad_segunda_kg'], 2); ?> kg</td>
                            <td><?php echo number_format((float) $cosecha['descarte_kg'], 2); ?> kg</td>
                            <td>
                                <span class="badge bg-<?php echo e(cosecha_estado_badge($cosecha['estado'])); ?>">
                                    <i class="<?php echo e(cosecha_estado_icon($cosecha['estado'])); ?>"></i>
                                    <?php echo e($cosecha['estado']); ?>
                                </span>
                            </td>
                            <td><?php echo e($cosecha['observaciones'] ?: 'Sin observaciones'); ?></td>
                            <td>
                                <?php if ($role === 'Agricultor' && $cosecha['estado'] === 'Registrada'): ?>
                                    <button
                                        type="button"
                                        class="btn btn-sm harvest-action-button harvest-action-button--edit"
                                        data-cosecha-edit
                                        data-id="<?php echo (int) $cosecha['id_cosecha']; ?>"
                                        data-lote="<?php echo (int) $cosecha['id_lote']; ?>"
                                        data-lote-label="Lote #<?php echo (int) $cosecha['id_lote']; ?> - <?php echo e($cosecha['lote_ubicacion']); ?> (<?php echo e($cosecha['cultivo_tipo'] ?: 'Sin cultivo'); ?>)"
                                        data-fecha="<?php echo e($cosecha['fecha_cosecha']); ?>"
                                        data-total="<?php echo e($cosecha['cantidad_total_kg']); ?>"
                                        data-primera="<?php echo e($cosecha['calidad_primera_kg']); ?>"
                                        data-segunda="<?php echo e($cosecha['calidad_segunda_kg']); ?>"
                                        data-descarte="<?php echo e($cosecha['descarte_kg']); ?>"
                                        data-observaciones="<?php echo e($cosecha['observaciones']); ?>">
                                        <i class="fas fa-pen"></i> Editar
                                    </button>
                                <?php elseif ($role === 'Bodeguero' && $cosecha['estado'] === 'Validada'): ?>
                                    <form method="POST" action="cosecha_acciones.php">
                                        <input type="hidden" name="accion" value="recibir_cosecha">
                                        <input type="hidden" name="id_cosecha" value="<?php echo (int) $cosecha['id_cosecha']; ?>">
                                        <button type="submit" class="btn btn-sm harvest-action-button harvest-action-button--receive" data-confirm-message="¿Marcar esta cosecha como recibida?">
                                            <i class="fas fa-warehouse"></i> Recibir
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-muted">Sin acciones</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>
    </section>
</div>

<?php if ($role === 'Agricultor'): ?>
        </main>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const menu = document.querySelector('[data-admin-account-menu]');
        const trigger = menu?.querySelector('[data-admin-account-trigger]');

        if (!menu || !trigger) return;

        const closeMenu = function () {
            menu.classList.remove('is-open');
            trigger.setAttribute('aria-expanded', 'false');
        };

        trigger.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            const open = menu.classList.toggle('is-open');
            trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
        });

        document.addEventListener('click', function (event) {
            if (!menu.contains(event.target)) {
                closeMenu();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeMenu();
            }
        });
    });
    </script>
<?php endif; ?>

<?php render_ada_chat(); ?>
<?php render_scripts(['js/cosecha.js?v=' . filemtime(__DIR__ . '/js/cosecha.js')]); ?>
</body>
</html>
