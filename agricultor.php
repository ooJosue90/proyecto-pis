<?php
require_once 'conexion.php';
require_auth('Agricultor');

$id_usuario = $_SESSION['id_usuario'];

require_once __DIR__ . '/includes/farmer_helpers.php';
require_once __DIR__ . '/includes/farmer_actions.php';
require_once __DIR__ . '/includes/farmer_dashboard_data.php';

$farmerData = load_farmer_dashboard_data($conn, $id_usuario);
extract($farmerData, EXTR_SKIP);

$farmer_weekdays = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
$farmer_months = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
$farmer_today = $farmer_weekdays[(int) date('w')] . ', ' . date('j') . ' de ' . $farmer_months[(int) date('n') - 1] . ' de ' . date('Y');
?>
<?php render_head('Dashboard Agricultor', [
    'https://fonts.googleapis.com/css2?family=Raleway:wght@500;600;700;800;900&family=Roboto+Condensed:wght@400;500;600;700;800;900&display=swap',
    'assets/vendor/bootstrap/bootstrap.min.css',
    'assets/vendor/fontawesome/css/all.min.css',
    'css/admin.css?v=' . filemtime(__DIR__ . '/css/admin.css'),
], ['assets/vendor/chartjs/chart.umd.js']); ?>
<body class="farmer-dashboard-page admin-dashboard-page farmer-admin-page">
    <div class="admin-tablet-shell">
        <aside class="sidebar" id="mainSidebar" aria-label="Navegación principal">
            <div class="logo-container">
                <div class="admin-sidebar-logo">
                    <span class="material-symbols-outlined" aria-hidden="true">agriculture</span>
                </div>
                <span class="nav-label admin-sidebar-brand">SembriExport</span>
            </div>

            <nav class="app-sidebar-nav admin-reference-nav">
                <a class="nav-item app-sidebar-link active" href="agricultor.php" title="Dashboard">
                    <span class="material-symbols-outlined" aria-hidden="true">dashboard</span>
                    <span class="nav-label">Dashboard</span>
                </a>
                <a class="nav-item app-sidebar-link" href="calcular_insumos.php" title="Calculadora">
                    <span class="material-symbols-outlined" aria-hidden="true">calculate</span>
                    <span class="nav-label">Calculadora</span>
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
            <div class="container farmer-dashboard admin-dashboard mt-4">
    <?php render_flash_messages(); ?>

    <section class="farmer-page-heading admin-page-heading farmer-dashboard-hero">
        <div class="farmer-hero-copy">
            <span class="farmer-kicker">Panel agrícola</span>
            <h1>Resumen de Actividad</h1>
            <p><?php echo e($farmer_today); ?></p>
        </div>
        <div class="farmer-hero-status">
            <span class="farmer-hero-status-icon"><span class="material-symbols-outlined" aria-hidden="true">agriculture</span></span>
            <div>
                <small>Estado de operación</small>
                <strong><span class="material-symbols-outlined" aria-hidden="true">circle</span> Jornada activa</strong>
            </div>
        </div>
    </section>

    <section class="farmer-stats-grid" aria-label="Resumen por etapas">
        <article class="farmer-stat-card farmer-stat-card--total">
            <div class="farmer-stat-top">
                <span class="farmer-stat-icon"><span class="material-symbols-outlined" aria-hidden="true">location_on</span></span>
                <span class="farmer-stat-status">Registrados</span>
            </div>
            <strong><?php echo $total_lotes; ?></strong>
            <p>Total de lotes</p>
            <span class="farmer-stat-detail"><span class="material-symbols-outlined" aria-hidden="true">layers</span> Superficie bajo gestión</span>
        </article>

        <article class="farmer-stat-card farmer-stat-card--riego">
            <div class="farmer-stat-top">
                <span class="farmer-stat-icon"><span class="material-symbols-outlined" aria-hidden="true">water_drop</span></span>
                <span class="farmer-stat-status">Activo</span>
            </div>
            <strong><?php echo $etapas['Desarrollo']; ?></strong>
            <p>Lotes en desarrollo</p>
            <span class="farmer-stat-detail"><span class="material-symbols-outlined" aria-hidden="true">water</span> Crecimiento y gestión hídrica</span>
        </article>

        <article class="farmer-stat-card farmer-stat-card--siembra">
            <div class="farmer-stat-top">
                <span class="farmer-stat-icon"><span class="material-symbols-outlined" aria-hidden="true">eco</span></span>
                <span class="farmer-stat-status">En progreso</span>
            </div>
            <strong><?php echo $etapas['Siembra']; ?></strong>
            <p>Lotes en siembra</p>
            <span class="farmer-stat-detail"><span class="material-symbols-outlined" aria-hidden="true">agriculture</span> Desarrollo inicial</span>
        </article>

        <article class="farmer-stat-card farmer-stat-card--cosecha">
            <div class="farmer-stat-top">
                <span class="farmer-stat-icon"><span class="material-symbols-outlined" aria-hidden="true">inventory_2</span></span>
                <span class="farmer-stat-status">Planificado</span>
            </div>
            <strong><?php echo $etapas['Cosecha']; ?></strong>
            <p>Lotes en cosecha</p>
            <span class="farmer-stat-detail"><span class="material-symbols-outlined" aria-hidden="true">event_available</span> Etapa productiva</span>
        </article>
    </section>

    <div class="farmer-content-grid">
        <main class="farmer-main-panel">
            <div class="farmer-workflow-header">
                <div>
                    <span class="farmer-kicker">Operación diaria</span>
                    <h2>Gestión de registros</h2>
                    <p>Completa cada flujo con información mínima, fechas claras y trazabilidad del lote.</p>
                </div>
                <span class="farmer-workflow-badge"><span class="material-symbols-outlined" aria-hidden="true">task_alt</span> 3 flujos activos</span>
            </div>

            <ul class="nav nav-tabs farmer-tabs" id="agricultorTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="cultivo-tab" data-bs-toggle="tab" data-bs-target="#cultivo" type="button" role="tab" aria-controls="cultivo" aria-selected="true">
                        <span class="farmer-tab-index">01</span>
                        <span class="material-symbols-outlined" aria-hidden="true">agriculture</span>
                        <span class="farmer-tab-copy"><strong>Cultivo</strong><small>Alta productiva</small></span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="lote-tab" data-bs-toggle="tab" data-bs-target="#lote" type="button" role="tab" aria-controls="lote" aria-selected="false">
                        <span class="farmer-tab-index">02</span>
                        <span class="material-symbols-outlined" aria-hidden="true">location_on</span>
                        <span class="farmer-tab-copy"><strong>Lote</strong><small>Ubicación y etapas</small></span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="insumos-tab" data-bs-toggle="tab" data-bs-target="#insumos" type="button" role="tab" aria-controls="insumos" aria-selected="false">
                        <span class="farmer-tab-index">03</span>
                        <span class="material-symbols-outlined" aria-hidden="true">inventory_2</span>
                        <span class="farmer-tab-copy"><strong>Insumos</strong><small>Abastecimiento</small></span>
                    </button>
                </li>
            </ul>

            <div class="tab-content farmer-tab-content" id="agricultorTabContent">
                <div class="tab-pane fade show active" id="cultivo" role="tabpanel" aria-labelledby="cultivo-tab">
                    <form method="POST" class="farmer-form farmer-record-form">
                        <input type="hidden" name="accion" value="registrar_cultivo">
                        <div class="record-hero record-hero--crop">
                            <div class="record-hero-content">
                                <span class="farmer-kicker">Registro agrícola</span>
                                <h2>Registrar Cultivo</h2>
                                <p>Ingrese el tipo de cultivo y la fecha de siembra para iniciar el seguimiento productivo.</p>
                            </div>
                            <div class="record-hero-meta" aria-label="Detalle del flujo">
                                <span><strong>Datos mínimos</strong><small>Tipo y fecha</small></span>
                                <span><strong>Seguimiento</strong><small>Disponible al guardar</small></span>
                            </div>
                            <span class="record-hero-icon" aria-hidden="true"><span class="material-symbols-outlined">agriculture</span></span>
                        </div>

                        <section class="form-section form-section--primary">
                            <header class="form-section-header">
                                <span class="form-section-icon"><span class="material-symbols-outlined" aria-hidden="true">edit_square</span></span>
                                <div>
                                    <h3>Identificación del cultivo</h3>
                                    <p>Define el cultivo base que luego se podrá asociar a uno o más lotes.</p>
                                </div>
                            </header>
                            <div class="farmer-form-grid record-field-grid">
                                <label class="record-field-card">
                                    <span>Tipo de cultivo</span>
                                    <input type="text" name="tipo" class="form-control" placeholder="Ej. Mango, banano..." required>
                                </label>
                                <label class="record-field-card">
                                    <span>Fecha de siembra</span>
                                    <input type="date" name="fecha_siembra" class="form-control" required>
                                </label>
                            </div>
                        </section>

                        <footer class="form-action-bar">
                            <span><span class="material-symbols-outlined" aria-hidden="true">verified</span> Registro inicial para trazabilidad productiva</span>
                            <button type="submit" name="registrar_cultivo" class="btn farmer-submit farmer-action-button farmer-action-button--primary">
                                <span>Registrar cultivo</span>
                            </button>
                        </footer>
                    </form>
                </div>

                <div class="tab-pane fade" id="lote" role="tabpanel" aria-labelledby="lote-tab">
                    <form method="POST" class="farmer-form farmer-record-form">
                        <input type="hidden" name="accion" value="registrar_lote">
                        <div class="record-hero record-hero--lot">
                            <div class="record-hero-content">
                                <span class="farmer-kicker">Gestión de lotes</span>
                                <h2>Registrar Lote</h2>
                                <p>Asocie el lote a un cultivo, defina su ubicación y configure las etapas de trabajo.</p>
                            </div>
                            <div class="record-hero-meta" aria-label="Detalle del flujo">
                                <span><strong>Asignación</strong><small>Cultivo y ubicación</small></span>
                                <span><strong>Cronograma</strong><small>Etapas opcionales</small></span>
                            </div>
                            <span class="record-hero-icon" aria-hidden="true"><span class="material-symbols-outlined">location_on</span></span>
                        </div>

                        <section class="form-section form-section--primary">
                            <header class="form-section-header">
                                <span class="form-section-icon"><span class="material-symbols-outlined" aria-hidden="true">map</span></span>
                                <div>
                                    <h3>Datos principales del lote</h3>
                                    <p>Selecciona el cultivo, registra la ubicación operacional y delimita el área en hectáreas.</p>
                                </div>
                            </header>

                            <div class="farmer-form-grid record-field-grid lot-primary-grid">
                                <label class="record-field-card lot-crop-field">
                                    <span>Cultivo</span>
                                    <div class="ag-select" data-ag-select>
                                        <input type="hidden" name="id_cultivo" data-ag-select-value>
                                        <button type="button" class="ag-select-button" data-ag-select-button aria-haspopup="listbox" aria-expanded="false">
                                            <span class="material-symbols-outlined" aria-hidden="true">agriculture</span>
                                            <span data-ag-select-label>Selecciona cultivo para el lote</span>
                                            <span class="material-symbols-outlined" aria-hidden="true">keyboard_arrow_down</span>
                                        </button>
                                        <div class="ag-select-menu" data-ag-select-menu role="listbox">
                                            <?php if (!empty($cultivos)): ?>
                                                <?php foreach($cultivos as $c): ?>
                                                    <button type="button" class="ag-select-option" data-value="<?php echo e($c['id_cultivo']); ?>" role="option">
                                                        <span class="material-symbols-outlined" aria-hidden="true">eco</span>
                                                        <span><?php echo e($c['tipo']); ?> - <?php echo e($c['fecha_siembra']); ?></span>
                                                    </button>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <p class="ag-select-empty">Primero registre un cultivo.</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </label>
                                <label class="record-field-card">
                                    <span>Ubicación</span>
                                    <input type="text" name="ubicacion" class="form-control" placeholder="Ubicación del lote" required>
                                </label>
                                <label class="record-field-card">
                                    <span>Área</span>
                                    <input type="number" step="0.01" min="0.01" name="area" class="form-control" placeholder="Área ha" required>
                                </label>
                            </div>
                        </section>

                        <section class="lot-stage-planner" aria-labelledby="lot-stage-title">
                            <header class="lot-stage-planner__header">
                                <div>
                                    <span class="farmer-kicker">Cronograma productivo</span>
                                    <h3 id="lot-stage-title">Etapas y fechas del lote</h3>
                                    <p>Seleccione la etapa actual y defina los periodos estimados de trabajo.</p>
                                </div>
                                <div class="lot-stage-summary" aria-label="Resumen del cronograma">
                                    <span><strong>3</strong><small>Etapas</small></span>
                                    <span><strong>6</strong><small>Fechas</small></span>
                                    <b><span class="material-symbols-outlined" aria-hidden="true">calendar_month</span> Planificación</b>
                                </div>
                            </header>

                            <div class="lot-stage-grid">
                                <article class="lot-stage-card lot-stage-card--riego">
                                    <label class="lot-stage-toggle">
                                        <input type="checkbox" name="etapa_riego" value="1" class="form-check-input">
                                        <span class="lot-stage-step">01</span>
                                        <span class="lot-stage-icon"><span class="material-symbols-outlined" aria-hidden="true">water_drop</span></span>
                                        <span class="lot-stage-copy">
                                            <strong>Desarrollo</strong>
                                            <small>Crecimiento y gestión hídrica</small>
                                        </span>
                                        <span class="lot-stage-check"><span class="material-symbols-outlined" aria-hidden="true">check</span></span>
                                    </label>
                                    <div class="lot-stage-dates">
                                        <label>
                                            <span>Fecha de inicio</span>
                                            <input type="date" name="fecha_inicio_riego" class="form-control">
                                        </label>
                                        <label>
                                            <span>Fecha de finalización</span>
                                            <input type="date" name="fecha_fin_riego" class="form-control">
                                        </label>
                                    </div>
                                </article>

                                <article class="lot-stage-card lot-stage-card--siembra">
                                    <label class="lot-stage-toggle">
                                        <input type="checkbox" name="etapa_siembra" value="1" class="form-check-input">
                                        <span class="lot-stage-step">02</span>
                                        <span class="lot-stage-icon"><span class="material-symbols-outlined" aria-hidden="true">agriculture</span></span>
                                        <span class="lot-stage-copy">
                                            <strong>Siembra</strong>
                                            <small>Implantación y desarrollo inicial</small>
                                        </span>
                                        <span class="lot-stage-check"><span class="material-symbols-outlined" aria-hidden="true">check</span></span>
                                    </label>
                                    <div class="lot-stage-dates">
                                        <label>
                                            <span>Fecha de inicio</span>
                                            <input type="date" name="fecha_inicio_siembra" class="form-control">
                                        </label>
                                        <label>
                                            <span>Fecha de finalización</span>
                                            <input type="date" name="fecha_fin_siembra" class="form-control">
                                        </label>
                                    </div>
                                </article>

                                <article class="lot-stage-card lot-stage-card--cosecha">
                                    <label class="lot-stage-toggle">
                                        <input type="checkbox" name="etapa_cosecha" value="1" class="form-check-input">
                                        <span class="lot-stage-step">03</span>
                                        <span class="lot-stage-icon"><span class="material-symbols-outlined" aria-hidden="true">agriculture</span></span>
                                        <span class="lot-stage-copy">
                                            <strong>Cosecha</strong>
                                            <small>Recolección en curso, todavía no finalizada</small>
                                        </span>
                                        <span class="lot-stage-check"><span class="material-symbols-outlined" aria-hidden="true">check</span></span>
                                    </label>
                                    <div class="lot-stage-dates">
                                        <label>
                                            <span>Fecha de inicio</span>
                                            <input type="date" name="fecha_inicio_cosecha" class="form-control">
                                        </label>
                                        <label>
                                            <span>Fecha de finalización</span>
                                            <input type="date" name="fecha_fin_cosecha" class="form-control">
                                        </label>
                                    </div>
                                </article>
                            </div>
                        </section>
                        <footer class="form-action-bar">
                            <span><span class="material-symbols-outlined" aria-hidden="true">event_note</span> Las fechas pueden completarse por etapa según disponibilidad</span>
                            <button type="submit" name="registrar_lote" class="btn farmer-submit farmer-action-button farmer-action-button--primary">
                                <span>Registrar lote</span>
                            </button>
                        </footer>
                    </form>
                </div>

                <div class="tab-pane fade" id="insumos" role="tabpanel" aria-labelledby="insumos-tab">
                    <section class="supply-dashboard" aria-label="Solicitar insumos agrícolas">
                        <div class="record-hero supply-hero">
                            <div class="record-hero-content">
                                <span class="farmer-kicker">Abastecimiento agrícola</span>
                                <h2>Solicitar Insumos</h2>
                                <p>Planifique fertilizantes, materiales y productos por lote para mantener el flujo operativo del cultivo.</p>
                            </div>
                            <div class="record-hero-meta" aria-label="Detalle del flujo">
                                <span><strong>Solicitud</strong><small>Por lote y hectáreas</small></span>
                                <span><strong>Revisión</strong><small>Administración valida</small></span>
                            </div>
                            <span class="record-hero-icon supply-hero-icon" aria-hidden="true"><span class="material-symbols-outlined">assignment</span></span>
                        </div>

                        <div class="supply-workspace">
                            <form method="POST" action="agricultor.php" class="farmer-form supply-request-form" data-supply-form>
                                <input type="hidden" name="accion" value="solicitar_insumos_manual">
                                <div class="supply-flow-head">
                                    <span class="supply-flow-head__icon"><span class="material-symbols-outlined" aria-hidden="true">receipt_long</span></span>
                                    <div>
                                        <span class="farmer-kicker">Orden de insumos</span>
                                        <h3>Detalle de abastecimiento</h3>
                                        <p>Organiza el lote, la cobertura y los productos antes de enviar la solicitud.</p>
                                    </div>
                                </div>

                                <section class="form-section form-section--primary">
                                    <header class="form-section-header">
                                        <span class="form-section-icon"><span class="material-symbols-outlined" aria-hidden="true">assignment_add</span></span>
                                        <div>
                                            <h3>Alcance de la solicitud</h3>
                                            <p>Selecciona el lote y define cuántas hectáreas serán cubiertas con los insumos.</p>
                                        </div>
                                    </header>

                                    <div class="farmer-form-grid record-field-grid">
                                        <label class="record-field-card">
                                            <span>Lote para abastecimiento</span>
                                            <div class="ag-select" data-ag-select>
                                                <input type="hidden" name="id_lote" data-ag-select-value>
                                                <button type="button" class="ag-select-button" data-ag-select-button aria-haspopup="listbox" aria-expanded="false">
                                                    <span class="material-symbols-outlined" aria-hidden="true">location_on</span>
                                                    <span data-ag-select-label>Selecciona lote para solicitar insumos</span>
                                                    <span class="material-symbols-outlined" aria-hidden="true">keyboard_arrow_down</span>
                                                </button>
                                                <div class="ag-select-menu" data-ag-select-menu role="listbox">
                                                    <?php foreach($lotes as $l): ?>
                                                        <button type="button" class="ag-select-option" data-value="<?php echo e($l['id_lote']); ?>" role="option">
                                                            <span class="material-symbols-outlined" aria-hidden="true">location_on</span>
                                                            <span>Lote #<?php echo e($l['id_lote']); ?> - <?php echo e($l['ubicacion']); ?> (<?php echo e($l['area']); ?> ha)</span>
                                                        </button>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </label>

                                        <label class="record-field-card">
                                            <span>Cantidad de hectáreas</span>
                                            <input type="number" step="0.01" min="0.01" name="hectareas" class="form-control" placeholder="Ej. 2.5" required>
                                        </label>
                                    </div>
                                </section>

                                <section class="supply-products-panel">
                                    <div class="farmer-section-heading">
                                        <div>
                                            <span class="farmer-kicker">Productos requeridos</span>
                                            <h2>Insumos solicitados</h2>
                                            <p>Agregue cada insumo y la cantidad requerida por hectárea.</p>
                                        </div>
                                        <button type="button" class="btn farmer-add-button farmer-action-button farmer-action-button--compact" data-add-supply-product data-app-no-ripple>
                                            <span class="material-symbols-outlined" aria-hidden="true">add</span>
                                            <span>Agregar insumo</span>
                                        </button>
                                    </div>
                                    <div class="farmer-products-list" data-supply-products></div>
                                </section>

                                <section class="form-section form-section--notes">
                                    <label class="record-field-card">
                                        <span>Observaciones</span>
                                        <textarea name="observaciones" class="form-control" placeholder="Notas para bodega o administración"></textarea>
                                    </label>
                                </section>

                                <footer class="form-action-bar">
                                    <span><span class="material-symbols-outlined" aria-hidden="true">approval_delegation</span> La solicitud quedará pendiente de revisión</span>
                                    <button type="submit" name="solicitar_insumos_manual" class="btn farmer-submit farmer-action-button farmer-action-button--primary supply-submit">
                                        <span>Enviar solicitud</span>
                                    </button>
                                </footer>
                            </form>

                            <aside class="supply-side-summary">
                                <header class="supply-summary-header">
                                    <span class="supply-summary-header__icon"><span class="material-symbols-outlined" aria-hidden="true">inventory</span></span>
                                    <div>
                                        <span class="farmer-kicker">Resumen</span>
                                        <h3>Disponibilidad</h3>
                                    </div>
                                </header>
                                <div class="supply-summary-metrics">
                                    <article>
                                        <span>Lotes disponibles</span>
                                        <strong><?php echo $total_lotes; ?></strong>
                                    </article>
                                    <article>
                                        <span>Insumos disponibles</span>
                                        <strong><?php echo count($insumos); ?></strong>
                                    </article>
                                </div>
                                <div class="supply-summary-note">
                                    <span class="material-symbols-outlined" aria-hidden="true">info</span>
                                    <p>Las cantidades se calculan por hectárea y serán revisadas antes de despacho.</p>
                                </div>
                                <nav class="supply-summary-actions" aria-label="Acciones de apoyo">
                                    <a href="calcular_insumos.php">
                                        <span class="material-symbols-outlined" aria-hidden="true">calculate</span>
                                        <span><strong>Calcular cantidades</strong><small>Estimar insumos por lote</small></span>
                                    </a>
                                    <a href="historial_solicitudes.php">
                                        <span class="material-symbols-outlined" aria-hidden="true">history</span>
                                        <span><strong>Ver historial</strong><small>Consultar solicitudes previas</small></span>
                                    </a>
                                </nav>
                            </aside>
                        </div>
                    </section>
                </div>
            </div>

            <section class="farmer-lotes-card">
                <div class="farmer-section-heading">
                    <h2>Lotes Registrados</h2>
                    <span><?php echo $total_lotes; ?> registros</span>
                </div>
                <div class="farmer-lots-table-wrap">
                    <table class="table farmer-lots-table" data-app-table-owner="farmer-lots-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Cultivo</th>
                                <th>Ubicación</th>
                                <th>Área</th>
                                <th>Etapa</th>
                                <th>Estado</th>
                                <th>Fitosanitario</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($lotes)): ?>
                                <tr>
                                    <td colspan="8" class="farmer-empty-row">
                                        <span class="material-symbols-outlined" aria-hidden="true">info</span>
                                        No hay lotes registrados recientemente
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach($lotes as $l): ?>
                                <tr>
                                    <td><?php echo e($l['id_lote']); ?></td>
                                    <td><?php echo e($l['tipo_cultivo']); ?></td>
                                    <td><?php echo e($l['ubicacion']); ?></td>
                                    <td><?php echo e($l['area']); ?></td>
                                    <td><?php echo e(crop_stage_label((int) $l['etapa_actual'])); ?></td>
                                    <td>
                                        <span class="crop-status crop-status--<?php echo e(str_replace('_', '-', $l['estado_cultivo'])); ?>">
                                            <span class="material-symbols-outlined" aria-hidden="true"><?php echo e(crop_status_symbol($l['estado_cultivo'])); ?></span>
                                            <?php echo e(crop_status_label($l['estado_cultivo'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo e($l['problemas_fitosanitarios'] ?: '-'); ?></td>
                                    <td>
                                        <?php if ($l['estado_cultivo'] === 'en_cosecha'): ?>
                                            <button
                                                type="button"
                                                class="harvest-finish-button"
                                                data-bs-toggle="modal"
                                                data-bs-target="#finalizarCosechaModal"
                                                data-harvest-lot-id="<?php echo (int) $l['id_lote']; ?>"
                                                data-harvest-lot-name="<?php echo e($l['ubicacion'] . ' - ' . $l['tipo_cultivo']); ?>">
                                                <span class="material-symbols-outlined" aria-hidden="true">check_circle</span>
                                                <span>Registrar</span>
                                            </button>
                                        <?php elseif ($l['estado_cultivo'] === 'finalizado'): ?>
                                            <span class="harvest-closed-state"><span class="material-symbols-outlined" aria-hidden="true">lock</span> Cerrado</span>
                                        <?php else: ?>
                                            <span class="harvest-no-action">Sin acciones</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>

        <aside class="farmer-side-panel">
            <section class="farmer-field-card" aria-label="Estado general de lotes">
                <div class="farmer-map-frame">
                    <iframe
                        title="Mapa satelital del cultivo"
                        src="https://maps.google.com/maps?q=-2.170998,-79.922359&t=k&z=15&output=embed"
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"
                        allowfullscreen>
                    </iframe>
                </div>
                <div class="farmer-field-footer">
                    <div class="farmer-field-heading">
                        <h2>Estado General</h2>
                    </div>
                    <div class="farmer-chart-wrap">
                        <canvas id="etapaChart" class="farmer-chart"></canvas>
                    </div>
                    <div class="farmer-stage-legend">
                        <span><i class="farmer-dot farmer-dot--siembra"></i>Siembra</span>
                        <span><i class="farmer-dot farmer-dot--riego"></i>Desarrollo</span>
                        <span><i class="farmer-dot farmer-dot--cosecha"></i>Cosecha</span>
                    </div>
                </div>
            </section>

            <section class="farmer-weather-card">
                <div>
                    <span>Clima Actual</span>
                    <strong>24°C</strong>
                    <p>Cielos Despejados</p>
                </div>
                <span class="material-symbols-outlined" aria-hidden="true">sunny</span>
            </section>
        </aside>
    </div>
            </div>
        </main>
    </div>

<div class="modal fade harvest-premium-modal" id="finalizarCosechaModal" tabindex="-1" aria-labelledby="finalizarCosechaTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form method="POST" action="cosecha_acciones.php">
                <input type="hidden" name="accion" value="guardar_cosecha">
                <input type="hidden" name="id_lote" data-harvest-lot-input>
                <div class="modal-header">
                    <span class="harvest-modal-icon" aria-hidden="true">
                        <span class="material-symbols-outlined" aria-hidden="true">agriculture</span>
                    </span>
                    <div class="harvest-modal-heading">
                        <span class="farmer-kicker">Cierre productivo</span>
                        <h2 class="modal-title" id="finalizarCosechaTitle">Registrar cosecha</h2>
                        <p>Registra la producción del lote para validación administrativa.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="harvest-lot-summary">
                        <span class="harvest-lot-summary__icon"><span class="material-symbols-outlined" aria-hidden="true">location_on</span></span>
                        <div>
                            <small>Lote seleccionado</small>
                            <strong data-harvest-lot-label></strong>
                        </div>
                        <span class="harvest-lot-summary__status"><span class="material-symbols-outlined" aria-hidden="true">agriculture</span> En cosecha</span>
                    </div>

                    <div class="harvest-form-grid">
                        <label class="harvest-field">
                            <span><span class="material-symbols-outlined" aria-hidden="true">scale</span> Cantidad total (kg)</span>
                            <input type="number" name="cantidad_total_kg" min="0.01" step="0.01" class="form-control" placeholder="Ej. 1250" required>
                        </label>
                        <label class="harvest-field">
                            <span><span class="material-symbols-outlined" aria-hidden="true">workspace_premium</span> Calidad primera (kg)</span>
                            <input type="number" name="calidad_primera_kg" min="0" step="0.01" class="form-control" value="0" required>
                        </label>
                        <label class="harvest-field">
                            <span><span class="material-symbols-outlined" aria-hidden="true">layers</span> Calidad segunda (kg)</span>
                            <input type="number" name="calidad_segunda_kg" min="0" step="0.01" class="form-control" value="0" required>
                        </label>
                        <label class="harvest-field">
                            <span><span class="material-symbols-outlined" aria-hidden="true">recycling</span> Descarte (kg)</span>
                            <input type="number" name="descarte_kg" min="0" step="0.01" class="form-control" value="0" required>
                        </label>
                        <label class="harvest-field harvest-field--wide">
                            <span><span class="material-symbols-outlined" aria-hidden="true">event_available</span> Fecha real de cosecha</span>
                            <input type="date" name="fecha_cosecha" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </label>
                        <label class="harvest-field harvest-field--wide">
                            <span><span class="material-symbols-outlined" aria-hidden="true">note_alt</span> Observación <small>Opcional</small></span>
                            <textarea name="observaciones" class="form-control" rows="3" placeholder="Calidad, clasificación, pérdidas u otra novedad de la cosecha"></textarea>
                        </label>
                    </div>

                    <div class="harvest-close-notice">
                        <span><span class="material-symbols-outlined" aria-hidden="true">lock</span></span>
                        <div>
                            <strong>Validación requerida</strong>
                            <p>Al confirmar, la cosecha quedará en estado Registrada para revisión del administrador.</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <span class="harvest-modal-security"><span class="material-symbols-outlined" aria-hidden="true">verified_user</span> Registro protegido por validación</span>
                    <button type="button" class="harvest-modal-cancel" data-bs-dismiss="modal">Volver</button>
                    <button type="submit" class="harvest-modal-submit farmer-action-button farmer-action-button--compact">
                        <span class="material-symbols-outlined" aria-hidden="true">check_circle</span> Registrar cosecha
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="js/calcular_insumos.js" defer></script>
<script>
const supplyInsumosOptions = <?php echo json_encode(array_map(static function ($insumo) {
    return [
        'id' => $insumo['id_insumos'],
        'label' => $insumo['nombre'] . ' (' . $insumo['cantidad'] . ' ' . $insumo['unidad_medida'] . ')',
    ];
}, $insumos), JSON_UNESCAPED_UNICODE); ?>;
const farmerStageTotals = <?php echo json_encode(array_values($etapas)); ?>;

</script>
<script src="js/farmer-dashboard.js?v=<?= filemtime(__DIR__ . '/js/farmer-dashboard.js'); ?>" defer></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('button, a').forEach(function (element) {
        element.addEventListener('mousedown', function () { element.classList.add('admin-pressed'); });
        element.addEventListener('mouseup', function () { element.classList.remove('admin-pressed'); });
        element.addEventListener('mouseleave', function () { element.classList.remove('admin-pressed'); });
    });

    const menu = document.querySelector('[data-admin-account-menu]');
    const trigger = menu?.querySelector('[data-admin-account-trigger]');

    if (!menu || !trigger) {
        return;
    }

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
<?php render_ada_chat(); ?>
<script src="assets/vendor/bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>
