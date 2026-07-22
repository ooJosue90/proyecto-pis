<?php
declare(strict_types=1);
$projectRoot = dirname(__DIR__, 4);
require_once $projectRoot . '/app/Shared/Views/layout.php';

?>
<?php render_head('Calculadora de Insumos', [
    'https://fonts.googleapis.com/css2?family=Raleway:wght@500;600;700;800;900&family=Roboto+Condensed:wght@400;500;600;700;800;900&display=swap',
    'assets/vendor/bootstrap/bootstrap.min.css',
    'assets/vendor/fontawesome/css/all.min.css',
    'css/admin.css?v=' . filemtime($projectRoot . '/css/admin.css'),
]); ?>
<body class="farmer-dashboard-page admin-dashboard-page farmer-admin-page">
    <div class="admin-tablet-shell">
        <aside class="sidebar" id="mainSidebar" aria-label="Navegación principal">
            <div class="logo-container">
                <div class="admin-sidebar-logo">
                    <i class="fas fa-seedling" aria-hidden="true"></i>
                </div>
                <span class="nav-label admin-sidebar-brand">SembriExport</span>
            </div>

            <nav class="app-sidebar-nav admin-reference-nav">
                <a class="nav-item app-sidebar-link" href="<?= e(\App\Core\Url::route('/dashboard/agricultor')); ?>" title="Dashboard">
                    <span class="material-symbols-outlined" aria-hidden="true">dashboard</span>
                    <span class="nav-label">Dashboard</span>
                </a>
                <a class="nav-item app-sidebar-link active" href="<?= e(\App\Core\Url::route('/insumos/calculadora')); ?>" title="Calculadora">
                    <span class="material-symbols-outlined" aria-hidden="true">calculate</span>
                    <span class="nav-label">Calculadora</span>
                </a>
                <a class="nav-item app-sidebar-link" href="<?= e(\App\Core\Url::route('/solicitudes/historial')); ?>" title="Historial">
                    <span class="material-symbols-outlined" aria-hidden="true">route</span>
                    <span class="nav-label">Historial</span>
                </a>
                <a class="nav-item app-sidebar-link" href="<?= e(\App\Core\Url::route('/plagas')); ?>" title="Fitosanitario">
                    <span class="material-symbols-outlined" aria-hidden="true">health_and_safety</span>
                    <span class="nav-label">Fitosanitario</span>
                </a>
            </nav>

            <div class="admin-sidebar-actions">
                <?php render_logout_control(); ?>
            </div>
        </aside>

        <main class="admin-inner-container">
            <header class="admin-reference-topbar">
                <div class="admin-topbar-user">
                    <span class="admin-topbar-avatar"><?php echo e(app_user_initials()); ?></span>
                    <div>
                        <h2>Saludos, <?php echo e(current_user_name()); ?></h2>
                        <p>Calcula insumos por lote antes de solicitar a bodega.</p>
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
                            <?php render_logout_control('dropdown'); ?>
                        </div>
                    </div>
                </div>
            </header>

            <div class="container farmer-dashboard admin-dashboard mt-4">
                <?php render_flash_messages(); ?>

                <section class="farmer-page-heading admin-page-heading farmer-dashboard-hero">
                    <div class="farmer-hero-copy">
                        <span class="farmer-kicker">Planificación técnica</span>
                        <h1>Calculadora de Insumos</h1>
                        <p>Seleccione un lote para estimar materiales, fertilizantes y recursos por etapa productiva.</p>
                    </div>
                    <div class="farmer-hero-status">
                        <span class="farmer-hero-status-icon"><span class="material-symbols-outlined" aria-hidden="true">functions</span></span>
                        <div>
                            <small>Lotes disponibles</small>
                            <strong><span class="material-symbols-outlined" aria-hidden="true">circle</span> <?php echo count($lotes); ?> para calcular</strong>
                        </div>
                    </div>
                </section>

                <section class="farmer-stats-grid calculator-stats-grid" aria-label="Resumen de calculadora">
                    <article class="farmer-stat-card farmer-stat-card--total">
                        <div class="farmer-stat-top">
                            <span class="farmer-stat-icon"><span class="material-symbols-outlined" aria-hidden="true">pin_drop</span></span>
                            <span class="farmer-stat-status">Registrados</span>
                        </div>
                        <strong><?php echo count($lotes); ?></strong>
                        <p>Lotes disponibles</p>
                        <span class="farmer-stat-detail"><span class="material-symbols-outlined" aria-hidden="true">rule</span> Listos para estimar</span>
                    </article>

                    <article class="farmer-stat-card farmer-stat-card--riego">
                        <div class="farmer-stat-top">
                            <span class="farmer-stat-icon"><span class="material-symbols-outlined" aria-hidden="true">straighten</span></span>
                            <span class="farmer-stat-status">Superficie</span>
                        </div>
                        <strong><?php echo number_format($totalArea, 2); ?></strong>
                        <p>Hectáreas registradas</p>
                        <span class="farmer-stat-detail"><span class="material-symbols-outlined" aria-hidden="true">conversion_path</span> Área bajo cálculo</span>
                    </article>

                    <article class="farmer-stat-card farmer-stat-card--siembra">
                        <div class="farmer-stat-top">
                            <span class="farmer-stat-icon"><span class="material-symbols-outlined" aria-hidden="true">checklist</span></span>
                            <span class="farmer-stat-status">Base técnica</span>
                        </div>
                        <strong><?php echo $baseRecommendations; ?></strong>
                        <p>Insumos referenciales</p>
                        <span class="farmer-stat-detail"><span class="material-symbols-outlined" aria-hidden="true">recommend</span> Por hectárea</span>
                    </article>

                    <article class="farmer-stat-card farmer-stat-card--cosecha">
                        <div class="farmer-stat-top">
                            <span class="farmer-stat-icon"><span class="material-symbols-outlined" aria-hidden="true">account_tree</span></span>
                            <span class="farmer-stat-status">Productivo</span>
                        </div>
                        <strong>3</strong>
                        <p>Etapas de cálculo</p>
                        <span class="farmer-stat-detail"><span class="material-symbols-outlined" aria-hidden="true">schema</span> Siembra, riego y cosecha</span>
                    </article>
                </section>

                <section class="calculator-dashboard">
                    <div class="calculator-workspace">
                        <div class="calculator-main-card farmer-record-form">
                            <div class="record-hero calculator-record-hero">
                                <div class="record-hero-content">
                                    <span class="farmer-kicker">Estimación por lote</span>
                                    <h2>Plan de insumos</h2>
                                    <p>El cálculo usa el área registrada del lote y agrupa los resultados por etapa.</p>
                                </div>
                                <div class="record-hero-meta" aria-label="Detalle del cálculo">
                                    <span><strong><?php echo $baseRecommendations; ?></strong><small>insumos base</small></span>
                                    <span><strong>3</strong><small>etapas</small></span>
                                </div>
                                <span class="record-hero-icon" aria-hidden="true"><span class="material-symbols-outlined">query_stats</span></span>
                            </div>

                            <div class="calculator-control-panel">
                                <label class="record-field-card calculator-lot-card">
                                    <span>Lote de cultivo</span>
                                    <div class="ag-select" data-calc-select>
                                        <input type="hidden" id="selectorLote" data-calc-select-value>
                                        <button type="button" class="ag-select-button" data-calc-select-button aria-haspopup="listbox" aria-expanded="false">
                                            <span class="material-symbols-outlined" aria-hidden="true">travel_explore</span>
                                            <span data-calc-select-label>Selecciona un lote para calcular insumos</span>
                                            <span class="material-symbols-outlined" aria-hidden="true">keyboard_arrow_down</span>
                                        </button>
                                        <div class="ag-select-menu" data-calc-select-menu role="listbox">
                                            <?php foreach ($lotes as $lote): ?>
                                                <button type="button" class="ag-select-option" data-value="<?php echo e($lote['id_lote']); ?>" role="option">
                                                    <span class="material-symbols-outlined" aria-hidden="true">grass</span>
                                                    <span>
                                                        Lote #<?php echo e($lote['id_lote']); ?> - <?php echo e($lote['ubicacion']); ?>
                                                        (<?php echo e($lote['tipo_cultivo']); ?>, <?php echo e($lote['area']); ?> ha)
                                                    </span>
                                                </button>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </label>
                            </div>

                            <div id="insumosCalculados" class="farmer-results-panel calculator-results-panel">
                                <div class="calculator-empty-state">
                                    <span><span class="material-symbols-outlined" aria-hidden="true">tips_and_updates</span></span>
                                    <h2>Seleccione un lote</h2>
                                    <p>Los insumos calculados aparecerán agrupados por Siembra, Riego y Cosecha.</p>
                                </div>
                            </div>
                        </div>

                        <aside class="calculator-side-panel supply-side-summary">
                            <header class="supply-summary-header">
                                <span class="supply-summary-header__icon"><span class="material-symbols-outlined" aria-hidden="true">fact_check</span></span>
                                <div>
                                    <span class="farmer-kicker">Acciones</span>
                                    <h3>Después del cálculo</h3>
                                </div>
                            </header>
                            <div class="supply-summary-metrics">
                                <article>
                                    <span>Lotes disponibles</span>
                                    <strong><?php echo count($lotes); ?></strong>
                                </article>
                                <article>
                                    <span>Área total</span>
                                    <strong><?php echo number_format($totalArea, 2); ?></strong>
                                </article>
                            </div>
                            <div class="supply-summary-note">
                                <span class="material-symbols-outlined" aria-hidden="true">lightbulb</span>
                                <p>La estimación es referencial y puede ajustarse antes de enviar la solicitud.</p>
                            </div>
                            <nav class="supply-summary-actions" aria-label="Acciones de calculadora">
                                <a href="<?= e(\App\Core\Url::route('/dashboard/agricultor', ['tab' => 'insumos'])); ?>">
                                    <span class="material-symbols-outlined" aria-hidden="true">playlist_add_check</span>
                                    <span><strong>Solicitar insumos</strong><small>Enviar pedido a revisión</small></span>
                                </a>
                                <a href="<?= e(\App\Core\Url::route('/solicitudes/historial')); ?>">
                                    <span class="material-symbols-outlined" aria-hidden="true">manage_history</span>
                                    <span><strong>Ver historial</strong><small>Consultar solicitudes previas</small></span>
                                </a>
                            </nav>
                        </aside>
                    </div>
                </section>
            </div>
        </main>
    </div>

<script src="js/calcular_insumos.js?v=<?php echo filemtime($projectRoot . '/js/calcular_insumos.js'); ?>"></script>
<?php render_ada_chat(); ?>
<?php render_scripts(); ?>
</body>
</html>
