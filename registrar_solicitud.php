<?php
require_once 'conexion.php';
require_auth('Agricultor');

$id_usuario = $_SESSION['id_usuario'];

function request_return_path(): string
{
    $returnTo = $_POST['return_to'] ?? '';
    $refererPath = parse_url($_SERVER['HTTP_REFERER'] ?? '', PHP_URL_PATH);

    if ($returnTo === 'agricultor.php?tab=insumos') {
        return $returnTo;
    }

    if (is_string($refererPath) && basename($refererPath) === 'agricultor.php') {
        return 'agricultor.php?tab=insumos';
    }

    return 'registrar_solicitud.php';
}

function request_user_owns_lote(mysqli $conn, string $userId, int $loteId): bool
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

if (isset($_POST['solicitar_insumos_manual'])) {
    $returnPath = request_return_path();
    $observaciones = trim($_POST['observaciones'] ?? '');
    $hectareas = (float) ($_POST['hectareas'] ?? 0);
    $id_lote = (int) ($_POST['id_lote'] ?? 0);
    $productos = $_POST['productos'] ?? [];

    if ($id_lote <= 0 || $hectareas <= 0 || empty($productos) || !request_user_owns_lote($conn, $id_usuario, $id_lote)) {
        flash('error', 'Seleccione un lote, hectáreas válidas y al menos un insumo.');
        redirect($returnPath);
    }

    $areaLote = (float) db_value(
        $conn,
        "SELECT area FROM lotes WHERE id_lote = ?",
        "i",
        [$id_lote],
        0
    );

    if ($areaLote <= 0 || $hectareas > $areaLote) {
        flash('error', 'Las hectáreas solicitadas no pueden superar el área del lote.');
        redirect($returnPath);
    }

    $conn->begin_transaction();

    try {
        $registrados = 0;

        foreach ($productos as $producto) {
            $idInsumo = (int) ($producto['id_insumo'] ?? 0);
            $cantidadPorHectarea = (float) ($producto['cantidad'] ?? 0);

            if ($idInsumo <= 0 || $cantidadPorHectarea <= 0) {
                continue;
            }

            $insumo = db_fetch_one(
                $conn,
                "SELECT id_insumos, nombre, tipo FROM insumos_agricolas WHERE id_insumos = ?",
                "i",
                [$idInsumo]
            );

            if (!$insumo) {
                continue;
            }

            $cantidadTotal = $cantidadPorHectarea * $hectareas;
            db_execute(
                $conn,
                "INSERT INTO productos_solicitud (
                    id_agricultor, id_lote, id_insumos, etapa, nombre, cantidad_solicitada, observaciones, fecha, estado
                ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), 'Pendiente')",
                "siissds",
                [$id_usuario, $id_lote, $idInsumo, $insumo['tipo'], $insumo['nombre'], $cantidadTotal, $observaciones]
            );
            $registrados++;
        }

        if ($registrados === 0) {
            throw new RuntimeException('No se registró ningún insumo válido.');
        }

        $conn->commit();
        flash('mensaje', "Solicitud registrada correctamente con $registrados insumos.");
    } catch (Throwable $exception) {
        $conn->rollback();
        error_log('Error al registrar solicitud manual: ' . $exception->getMessage());
        flash('error', 'No se pudo registrar la solicitud manual.');
    }

    redirect($returnPath);
}

// Procesar solicitud automática basada en insumos calculados
if (isset($_POST['solicitar_insumos_automatico'])) {
    $returnPath = request_return_path();
    $id_lote = (int) ($_POST['id_lote'] ?? 0);
    $observaciones = trim($_POST['observaciones'] ?? '');

    // Obtener insumos calculados para el lote
    $insumos_json = $_POST['insumos_json'] ?? '[]';
    $insumos = json_decode($insumos_json, true);

    if ($id_lote > 0 && request_user_owns_lote($conn, $id_usuario, $id_lote) && $insumos && is_array($insumos)) {
        $conn->begin_transaction();

        try {
            $registrados = 0;
            foreach ($insumos as $insumo) {
                $nombre = trim($insumo['nombre'] ?? '');
                $cantidad = (float) ($insumo['cantidad_total'] ?? 0);

                if ($nombre === '' || $cantidad <= 0) {
                    continue;
                }

                $insumoDb = db_fetch_one(
                    $conn,
                    "SELECT id_insumos, tipo FROM insumos_agricolas WHERE nombre = ? LIMIT 1",
                    "s",
                    [$nombre]
                );

                if ($insumoDb) {
                    db_execute(
                        $conn,
                        "INSERT INTO productos_solicitud (
                            id_agricultor, id_lote, id_insumos, etapa, nombre, cantidad_solicitada, observaciones, fecha, estado
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), 'Pendiente')",
                        "siissds",
                        [$id_usuario, $id_lote, (int) $insumoDb['id_insumos'], $insumoDb['tipo'], $nombre, $cantidad, $observaciones]
                    );
                    $registrados++;
                }
            }

            if ($registrados === 0) {
                throw new RuntimeException('No se encontraron insumos válidos para registrar.');
            }

            $conn->commit();
            flash('mensaje', "Solicitud automática registrada correctamente con $registrados insumos.");
        } catch (Throwable $exception) {
            $conn->rollback();
            error_log('Error al registrar solicitud automática: ' . $exception->getMessage());
            flash('error', 'No se pudo registrar la solicitud automática.');
        }
    } else {
        flash('error', 'No se pudo registrar la solicitud automática.');
    }

    redirect($returnPath);
}

// Obtener lotes del agricultor
$lotes = db_fetch_all($conn, "
    SELECT l.*, c.tipo AS tipo_cultivo
    FROM lotes l
    LEFT JOIN cultivos c ON l.id_cultivo=c.id_cultivo
    WHERE c.id_usuario = ?
    ORDER BY l.id_lote DESC
", "s", [$id_usuario]);

// Obtener insumos disponibles
$insumos = db_fetch_all($conn, "SELECT id_insumos, nombre, cantidad, unidad_medida FROM insumos_agricolas ORDER BY nombre");
$solicitudes = db_fetch_all(
    $conn,
    "SELECT * FROM productos_solicitud WHERE id_agricultor = ? ORDER BY fecha DESC",
    "s",
    [$id_usuario]
);
?>
<?php render_head('Registrar Solicitud de Insumos', [], ['https://cdn.jsdelivr.net/npm/chart.js']); ?>
<body class="farmer-dashboard-page">
<?php render_app_nav('fas fa-seedling', current_user_name() . ' - Solicitudes', [
    ['href' => 'agricultor.php', 'label' => 'Dashboard', 'icon' => 'fas fa-table-columns', 'class' => 'btn btn-success btn-sm'],
    ['href' => 'logout.php', 'label' => 'Salir', 'icon' => 'fas fa-sign-out-alt', 'class' => 'btn btn-outline-light btn-sm'],
]); ?>
<div class="container farmer-dashboard mt-4">
    <?php render_flash_messages(); ?>

    <section class="farmer-page-heading">
        <div>
            <span class="farmer-kicker">Solicitudes</span>
            <h1>Registrar Solicitud de Insumos</h1>
        </div>
        <a href="agricultor.php" class="btn btn-success farmer-primary-action">
            <i class="fas fa-table-columns"></i>
            Dashboard
        </a>
    </section>

    <section class="farmer-main-panel farmer-section-panel">
        <ul class="nav nav-tabs farmer-tabs" id="solicitudTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="manual-tab" data-bs-toggle="tab" data-bs-target="#manual" type="button" role="tab" aria-controls="manual" aria-selected="true">Solicitud Manual</button>
            </li>
        </ul>

        <div class="tab-content farmer-tab-content" id="solicitudTabContent">
            <div class="tab-pane fade show active" id="manual" role="tabpanel" aria-labelledby="manual-tab">
                <form method="POST" id="form-solicitar-manual" class="farmer-form">
                    <div class="farmer-form-grid">
                        <label for="id_lote_manual">
                            <span>Selecciona Lote</span>
                            <select id="id_lote_manual" name="id_lote" class="form-select" required>
                                <option value="">Selecciona lote</option>
                                <?php foreach($lotes as $l): ?>
                                    <option value="<?php echo e($l['id_lote']); ?>" data-area="<?php echo e($l['area']); ?>">
                                        Lote #<?php echo e($l['id_lote']); ?> - <?php echo e($l['ubicacion']); ?> (<?php echo e($l['area']); ?> ha)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>

                        <label for="hectareas">
                            <span>Cantidad de Hectáreas</span>
                            <input type="number" step="0.01" min="0.01" id="hectareas" name="hectareas" class="form-control" placeholder="Ingrese cantidad de hectáreas" required>
                        </label>
                    </div>

                    <div class="farmer-dynamic-section">
                        <div class="farmer-section-heading">
                            <h2>Insumos solicitados</h2>
                            <button type="button" id="add-producto" class="btn btn-outline-primary farmer-add-button">
                                <i class="fas fa-plus"></i>
                                Agregar Insumo
                            </button>
                        </div>
                        <div id="productos-container" class="farmer-products-list">
                            <!-- Aquí se agregarán dinámicamente los insumos -->
                        </div>
                    </div>

                    <label>
                        <span>Observaciones</span>
                        <textarea name="observaciones" class="form-control" placeholder="Observaciones"></textarea>
                    </label>

                    <button type="submit" name="solicitar_insumos_manual" class="btn btn-success w-100 farmer-submit">
                        <i class="fas fa-paper-plane"></i> Enviar Solicitud Manual
                    </button>
                </form>
            </div>
        </div>
    </section>

    <section class="farmer-main-panel farmer-history-panel">
        <div class="farmer-lotes-card">
            <div class="farmer-section-heading">
                <h2>Historial de Solicitudes</h2>
                <span><?php echo count($solicitudes); ?> registros</span>
            </div>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Insumo</th>
                            <th>Cantidad</th>
                            <th>Observaciones</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($solicitudes)): ?>
                            <?php foreach ($solicitudes as $sol): ?>
                                <tr>
                                    <td><?php echo e($sol['id_producto_solicitud']); ?></td>
                                    <td><?php echo e($sol['nombre']); ?></td>
                                    <td><?php echo e($sol['cantidad_solicitada']); ?></td>
                                    <td><?php echo e($sol['observaciones']); ?></td>
                                    <td><?php echo e($sol['fecha']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $sol['estado'] == 'Pendiente' ? 'warning' : ($sol['estado'] == 'Aprobado' ? 'primary' : ($sol['estado'] == 'Entregado' ? 'success' : 'danger')); ?>">
                                            <?php echo e($sol['estado']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="farmer-empty-row">
                                    <i class="fas fa-circle-info"></i>
                                    No hay solicitudes registradas
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>

<script>
const insumosOptions = <?php echo json_encode(array_map(static function ($insumo) {
    return [
        'id' => $insumo['id_insumos'],
        'label' => $insumo['nombre'] . ' (' . $insumo['cantidad'] . ' ' . $insumo['unidad_medida'] . ')',
    ];
}, $insumos), JSON_UNESCAPED_UNICODE); ?>;

function escapeHtml(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function buildInsumoOptions() {
    return '<option value="">Selecciona insumo</option>' + insumosOptions.map((insumo) => (
        `<option value="${escapeHtml(insumo.id)}">${escapeHtml(insumo.label)}</option>`
    )).join('');
}

// JavaScript para agregar productos dinámicamente en solicitud manual
let productoIndex = 1;
document.getElementById('add-producto').addEventListener('click', function() {
    const container = document.getElementById('productos-container');
    const newItem = document.createElement('div');
    newItem.className = 'producto-item farmer-product-row';
    newItem.innerHTML = `
        <div class="farmer-product-grid">
            <label>
                <span>Insumo</span>
                <select name="productos[${productoIndex}][id_insumo]" class="form-select" required>
                    ${buildInsumoOptions()}
                </select>
            </label>
            <label>
                <span>Cantidad por hectárea</span>
                <input type="number" step="0.01" min="0.01" name="productos[${productoIndex}][cantidad]" class="form-control" placeholder="Cantidad por hectárea" required>
            </label>
            <button type="button" class="btn btn-danger btn-sm remove-producto" aria-label="Eliminar insumo"><i class="fas fa-trash"></i></button>
        </div>
    `;
    container.appendChild(newItem);
    productoIndex++;

    // Agregar evento para eliminar producto
    newItem.querySelector('.remove-producto').addEventListener('click', function() {
        container.removeChild(newItem);
    });
});

document.getElementById('add-producto').click();

const loteManual = document.getElementById('id_lote_manual');
const hectareasManual = document.getElementById('hectareas');

loteManual.addEventListener('change', function() {
    const selected = this.options[this.selectedIndex];
    const area = selected?.dataset.area || '';
    hectareasManual.max = area;

    if (area && Number(hectareasManual.value) > Number(area)) {
        hectareasManual.value = area;
    }
});

</script>
<?php render_ada_chat(); ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/app-ui.js"></script>
</body>
</html>
