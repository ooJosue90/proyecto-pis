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
?>
<?php render_head('Calculadora de Insumos'); ?>
<body class="farmer-dashboard-page">
<?php render_app_nav('fas fa-calculator', 'Calculadora de Insumos'); ?>
<div class="container farmer-dashboard mt-4">
    <?php render_flash_messages(); ?>

    <section class="farmer-page-heading farmer-dashboard-hero">
        <div class="farmer-hero-copy">
            <span class="farmer-kicker">Planificación técnica</span>
            <h1>Calculadora de Insumos</h1>
            <p>Seleccione un lote para estimar materiales, fertilizantes y recursos por etapa productiva.</p>
        </div>
        <div class="farmer-hero-status">
            <span class="farmer-hero-status-icon"><i class="fas fa-calculator"></i></span>
            <div>
                <small>Lotes disponibles</small>
                <strong><i class="fas fa-circle"></i> <?php echo count($lotes); ?> para calcular</strong>
            </div>
        </div>
    </section>

    <section class="farmer-main-panel farmer-section-panel">
        <div class="farmer-tab-content calculator-dashboard">
            <div class="calculator-workspace">
                <div class="calculator-main-card">
                    <label class="record-field-card calculator-lot-card">
                        <span>Lote de cultivo</span>
                        <div class="ag-select" data-calc-select>
                            <input type="hidden" id="selectorLote" data-calc-select-value>
                            <button type="button" class="ag-select-button" data-calc-select-button aria-haspopup="listbox" aria-expanded="false">
                                <i class="fas fa-location-dot"></i>
                                <span data-calc-select-label>Selecciona un lote para calcular insumos</span>
                                <i class="fas fa-chevron-down"></i>
                            </button>
                            <div class="ag-select-menu" data-calc-select-menu role="listbox">
                                <?php foreach ($lotes as $lote): ?>
                                    <button type="button" class="ag-select-option" data-value="<?php echo e($lote['id_lote']); ?>" role="option">
                                        <i class="fas fa-seedling"></i>
                                        <span>
                                            Lote #<?php echo e($lote['id_lote']); ?> - <?php echo e($lote['ubicacion']); ?>
                                            (<?php echo e($lote['tipo_cultivo']); ?>, <?php echo e($lote['area']); ?> ha)
                                        </span>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </label>

                    <div id="insumosCalculados" class="farmer-results-panel calculator-results-panel">
                        <div class="calculator-empty-state">
                            <span><i class="fas fa-circle-info"></i></span>
                            <h2>Seleccione un lote</h2>
                            <p>Los insumos calculados aparecerán agrupados por Siembra, Riego y Cosecha.</p>
                        </div>
                    </div>
                </div>

                <aside class="calculator-side-panel">
                    <article>
                        <span>Lotes disponibles</span>
                        <strong><?php echo count($lotes); ?></strong>
                    </article>
                    <article>
                        <span>Etapas de cálculo</span>
                        <strong>3</strong>
                    </article>
                    <a href="agricultor.php?tab=insumos" class="farmer-action-button farmer-action-button--compact">
                        <i class="fas fa-paper-plane"></i>
                        Solicitar desde el panel
                    </a>
                    <a href="historial_solicitudes.php" class="farmer-action-button farmer-action-button--compact">
                        <i class="fas fa-clock-rotate-left"></i>
                        Ver historial
                    </a>
                </aside>
            </div>
        </div>
    </section>
</div>

<script src="js/calcular_insumos.js"></script>
<?php render_ada_chat(); ?>
<?php render_scripts(); ?>
</body>
</html>
