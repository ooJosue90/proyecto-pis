<?php
require_once 'conexion.php';
require_auth('Agricultor');

$id_usuario = $_SESSION['id_usuario'];

function calculator_user_owns_lote(mysqli $conn, string $userId, int $loteId): bool
{
    return (int) db_value(
        $conn,
        "SELECT COUNT(*)
         FROM lotes l
         INNER JOIN cultivos c ON l.id_cultivo = c.id_cultivo
         WHERE l.id_lote = ? AND c.id_usuario = ?",
        "is",
        [$loteId, $userId],
        0
    ) > 0;
}

function calculator_insumos_for_area(float $area): array
{
    $insumos_por_hectarea = [
        'Siembra' => [
            ['nombre' => 'Plántulas injertadas', 'cantidad_ha' => 167, 'unidad' => 'árboles'],
            ['nombre' => 'Compost orgánico', 'cantidad_ha' => 5000, 'unidad' => 'kg'],
            ['nombre' => 'Fosfato diamónico (DAP)', 'cantidad_ha' => 100, 'unidad' => 'kg'],
            ['nombre' => 'Cal agrícola', 'cantidad_ha' => 200, 'unidad' => 'kg'],
            ['nombre' => 'Estacas de tutorado', 'cantidad_ha' => 167, 'unidad' => 'unidades'],
        ],
        'Riego' => [
            ['nombre' => 'Fertilizante NPK (20-20-20)', 'cantidad_ha' => 198, 'unidad' => 'kg'],
            ['nombre' => 'Quelatos de micronutrientes', 'cantidad_ha' => 10, 'unidad' => 'kg'],
            ['nombre' => 'Bioestimulantes (algas + aminoácidos)', 'cantidad_ha' => 5.25, 'unidad' => 'litros'],
            ['nombre' => 'Cinta de goteo o microaspersores', 'cantidad_ha' => 10000, 'unidad' => 'metros lineales'],
            ['nombre' => 'Medidor de pH y CE', 'cantidad_ha' => 1, 'unidad' => 'kit'],
        ],
        'Cosecha' => [
            ['nombre' => 'Cajas plásticas ventiladas', 'cantidad_ha' => 300, 'unidad' => 'unidades'],
            ['nombre' => 'Solución desinfectante (NaClO)', 'cantidad_ha' => 50, 'unidad' => 'litros'],
            ['nombre' => 'Etiquetas y mallas', 'cantidad_ha' => 300, 'unidad' => 'unidades'],
            ['nombre' => 'Tijeras de poda', 'cantidad_ha' => 5, 'unidad' => 'unidades'],
        ],
    ];

    $totales = [];
    foreach ($insumos_por_hectarea as $etapa => $insumos) {
        foreach ($insumos as $insumo) {
            $totales[] = [
                'etapa' => $etapa,
                'nombre' => $insumo['nombre'],
                'cantidad_total' => $insumo['cantidad_ha'] * $area,
                'unidad' => $insumo['unidad'],
            ];
        }
    }

    return $totales;
}

if (isset($_GET['id_lote'])) {
    header('Content-Type: application/json; charset=utf-8');

    $id_lote = intval($_GET['id_lote']);
    if ($id_lote <= 0 || !calculator_user_owns_lote($conn, $id_usuario, $id_lote)) {
        echo json_encode(['error' => 'Lote no encontrado o no autorizado']);
        exit();
    }

    $area = (float) db_value(
        $conn,
        "SELECT area FROM lotes WHERE id_lote = ?",
        "i",
        [$id_lote],
        0
    );

    echo json_encode([
        'area' => $area,
        'insumos' => calculator_insumos_for_area($area),
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

$lotes = db_fetch_all($conn, "
    SELECT l.*, c.tipo AS tipo_cultivo
    FROM lotes l
    INNER JOIN cultivos c ON l.id_cultivo = c.id_cultivo
    WHERE c.id_usuario = ?
    ORDER BY l.id_lote DESC
", "s", [$id_usuario]);

$totalArea = array_reduce($lotes, static fn ($total, $lote) => $total + (float) $lote['area'], 0.0);
$baseRecommendations = count(calculator_insumos_for_area(1));
?>
<?php render_head('Calculadora de Insumos', [
    'https://fonts.googleapis.com/css2?family=Raleway:wght@500;600;700;800;900&family=Roboto+Condensed:wght@400;500;600;700;800;900&display=swap',
    'assets/vendor/bootstrap/bootstrap.min.css',
    'assets/vendor/fontawesome/css/all.min.css',
    'css/admin.css?v=' . filemtime(__DIR__ . '/css/admin.css'),
]); ?>
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
                <a class="nav-item app-sidebar-link" href="agricultor.php" title="Dashboard">
                    <span class="material-symbols-outlined" aria-hidden="true">dashboard</span>
                    <span class="nav-label">Dashboard</span>
                </a>
                <a class="nav-item app-sidebar-link active" href="calcular_insumos.php" title="Calculadora">
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
                                <a href="agricultor.php?tab=insumos">
                                    <span class="material-symbols-outlined" aria-hidden="true">playlist_add_check</span>
                                    <span><strong>Solicitar insumos</strong><small>Enviar pedido a revisión</small></span>
                                </a>
                                <a href="historial_solicitudes.php">
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

<script src="js/calcular_insumos.js?v=<?php echo filemtime(__DIR__ . '/js/calcular_insumos.js'); ?>"></script>
<?php render_ada_chat(); ?>
<?php render_scripts(); ?>
</body>
</html>
