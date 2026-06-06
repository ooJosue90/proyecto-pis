<?php
require_once 'conexion.php';
require_auth('Administrador');

function admin_purchase_tables_ready(mysqli $conn): bool
{
    foreach (['facturas_compra', 'factura_compra_detalle'] as $table) {
        $escaped = $conn->real_escape_string($table);
        if ($conn->query("SHOW TABLES LIKE '{$escaped}'")->num_rows === 0) {
            return false;
        }
    }

    return true;
}

$tablesReady = admin_purchase_tables_ready($conn);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    header('Content-Type: application/json; charset=utf-8');

    if (!$tablesReady) {
        echo json_encode(['success' => false, 'message' => 'Primero ejecute facturas_compra.sql en phpMyAdmin.']);
        exit;
    }

    $acciones = [
        'aprobar_factura' => 'Aprobada',
        'rechazar_factura' => 'Rechazada',
    ];
    $action = (string) ($_POST['action'] ?? '');
    $idFactura = (int) ($_POST['id_factura_compra'] ?? 0);

    if (!isset($acciones[$action])) {
        echo json_encode(['success' => false, 'message' => 'No tienes permisos para realizar esta acción.']);
        exit;
    }

    if ($idFactura <= 0) {
        echo json_encode(['success' => false, 'message' => 'Factura inválida.']);
        exit;
    }

    try {
        $nuevoEstado = $acciones[$action];
        $actualizadas = db_execute(
            $conn,
            "UPDATE facturas_compra
             SET estado = ?, fecha_revision = NOW(), id_usuario_revision = ?
             WHERE id_factura_compra = ? AND estado = 'Registrada'",
            'ssi',
            [$nuevoEstado, $_SESSION['id_usuario'], $idFactura]
        );

        if ($actualizadas !== 1) {
            echo json_encode([
                'success' => false,
                'message' => 'Acción no permitida para el estado actual de la factura.',
            ]);
            exit;
        }

        echo json_encode([
            'success' => true,
            'message' => $nuevoEstado === 'Aprobada' ? 'Factura aprobada.' : 'Factura rechazada.',
        ]);
    } catch (Throwable $exception) {
        error_log('Error revisando factura de compra: ' . $exception->getMessage());
        echo json_encode(['success' => false, 'message' => 'No se pudo revisar la factura.']);
    }

    exit;
}

$proveedores = db_fetch_all($conn, 'SELECT id_proveedor, Nombre FROM proveedor ORDER BY Nombre');
$facturas = [];
$stats = [
    'total_facturas' => 0,
    'total_monto' => 0,
    'total_aprobado' => 0,
    'registradas' => 0,
    'aprobadas' => 0,
    'rechazadas' => 0,
];

$filtroProveedor = (int) ($_GET['id_proveedor'] ?? 0);
$filtroEstado = trim((string) ($_GET['estado'] ?? ''));
$fechaDesde = trim((string) ($_GET['fecha_desde'] ?? ''));
$fechaHasta = trim((string) ($_GET['fecha_hasta'] ?? ''));
$estadosValidos = ['Registrada', 'Aprobada', 'Rechazada', 'Anulada'];

if ($tablesReady) {
    $where = [];
    $types = '';
    $params = [];

    if ($filtroProveedor > 0) {
        $where[] = 'fc.id_proveedor = ?';
        $types .= 'i';
        $params[] = $filtroProveedor;
    }

    if (in_array($filtroEstado, $estadosValidos, true)) {
        $where[] = 'fc.estado = ?';
        $types .= 's';
        $params[] = $filtroEstado;
    }

    if ($fechaDesde !== '') {
        $where[] = 'fc.fecha >= ?';
        $types .= 's';
        $params[] = $fechaDesde;
    }

    if ($fechaHasta !== '') {
        $where[] = 'fc.fecha <= ?';
        $types .= 's';
        $params[] = $fechaHasta;
    }

    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    $facturas = db_fetch_all(
        $conn,
        "SELECT fc.*, p.Nombre AS proveedor_nombre, u.nombre AS bodeguero_nombre,
                ur.nombre AS revisor_nombre
         FROM facturas_compra fc
         JOIN proveedor p ON fc.id_proveedor = p.id_proveedor
         JOIN usuarios u ON fc.id_usuario = u.id_usuario
         LEFT JOIN usuarios ur ON fc.id_usuario_revision = ur.id_usuario
         {$whereSql}
         ORDER BY fc.fecha DESC, fc.id_factura_compra DESC",
        $types,
        $params
    );

    $stats = db_fetch_one(
        $conn,
        "SELECT COUNT(*) AS total_facturas,
                COALESCE(SUM(total), 0) AS total_monto,
                COALESCE(SUM(CASE WHEN estado = 'Aprobada' THEN total ELSE 0 END), 0) AS total_aprobado,
                SUM(CASE WHEN estado = 'Registrada' THEN 1 ELSE 0 END) AS registradas,
                SUM(CASE WHEN estado = 'Aprobada' THEN 1 ELSE 0 END) AS aprobadas,
                SUM(CASE WHEN estado = 'Rechazada' THEN 1 ELSE 0 END) AS rechazadas
         FROM facturas_compra"
    ) ?: $stats;
}
?>

<div class="row mt-4 admin-invoice-module">
    <div class="col-12">
        <?php if (!$tablesReady): ?>
            <div class="alert alert-warning">
                <i class="fas fa-triangle-exclamation"></i>
                El módulo requiere ejecutar <strong>facturas_compra.sql</strong> en phpMyAdmin.
            </div>
        <?php else: ?>
            <div class="card mb-4 admin-invoice-card">
                <div class="card-header admin-invoice-header">
                    <div>
                        <span class="admin-invoice-kicker">Control financiero</span>
                        <h4><i class="fas fa-file-invoice-dollar"></i> Facturas de Compra</h4>
                    </div>
                    <span class="admin-invoice-count"><?php echo (int) $stats['registradas']; ?> por revisar</span>
                </div>
                <div class="card-body">
                    <div class="admin-invoice-stats">
                        <div class="admin-invoice-stat">
                            <div class="card bg-primary text-white">
                                <div class="card-body text-center">
                                    <h3><?php echo (int) $stats['total_facturas']; ?></h3>
                                    <p class="mb-0">Facturas</p>
                                </div>
                            </div>
                        </div>
                        <div class="admin-invoice-stat">
                            <div class="card bg-info text-white">
                                <div class="card-body text-center">
                                    <h3>$<?php echo number_format((float) $stats['total_monto'], 2); ?></h3>
                                    <p class="mb-0">Total registrado</p>
                                </div>
                            </div>
                        </div>
                        <div class="admin-invoice-stat">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <h3>$<?php echo number_format((float) $stats['total_aprobado'], 2); ?></h3>
                                    <p class="mb-0">Total aprobado</p>
                                </div>
                            </div>
                        </div>
                        <div class="admin-invoice-stat">
                            <div class="card bg-warning text-white">
                                <div class="card-body text-center">
                                    <h3><?php echo (int) $stats['registradas']; ?></h3>
                                    <p class="mb-0">Por revisar</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form class="admin-invoice-filters" id="purchaseInvoiceFilters">
                        <div class="admin-invoice-filter admin-invoice-filter--provider">
                            <label class="form-label">Proveedor</label>
                            <select name="id_proveedor" class="form-select">
                                <option value="">Todos</option>
                                <?php foreach ($proveedores as $proveedor): ?>
                                    <option value="<?php echo e($proveedor['id_proveedor']); ?>" <?php echo $filtroProveedor === (int) $proveedor['id_proveedor'] ? 'selected' : ''; ?>>
                                        <?php echo e($proveedor['Nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="admin-invoice-filter">
                            <label class="form-label">Estado</label>
                            <select name="estado" class="form-select">
                                <option value="">Todos</option>
                                <?php foreach ($estadosValidos as $estado): ?>
                                    <option value="<?php echo e($estado); ?>" <?php echo $filtroEstado === $estado ? 'selected' : ''; ?>><?php echo e($estado); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="admin-invoice-filter">
                            <label class="form-label">Desde</label>
                            <input type="date" name="fecha_desde" class="form-control" value="<?php echo e($fechaDesde); ?>">
                        </div>
                        <div class="admin-invoice-filter">
                            <label class="form-label">Hasta</label>
                            <input type="date" name="fecha_hasta" class="form-control" value="<?php echo e($fechaHasta); ?>">
                        </div>
                        <div class="admin-invoice-filter-actions">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filtrar</button>
                            <button type="button" class="btn btn-outline-secondary" data-clear-invoice-filters>Limpiar</button>
                        </div>
                    </form>

                    <div class="table-responsive admin-invoice-table-wrap">
                        <table class="table table-striped table-hover align-middle admin-invoice-table">
                            <thead class="table-dark">
                                <tr>
                                    <th>Pedido</th>
                                    <th>Número</th>
                                    <th>Fecha</th>
                                    <th>Proveedor</th>
                                    <th>Bodeguero</th>
                                    <th>Total</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($facturas): ?>
                                    <?php foreach ($facturas as $factura): ?>
                                        <?php
                                        $badge = $factura['estado'] === 'Aprobada'
                                            ? 'success'
                                            : ($factura['estado'] === 'Registrada'
                                                ? 'warning'
                                                : ($factura['estado'] === 'Anulada' ? 'secondary' : 'danger'));
                                        ?>
                                        <tr>
                                            <td><?php echo $factura['id_pedido'] ? '#' . (int) $factura['id_pedido'] : 'Sin pedido'; ?></td>
                                            <td><strong><?php echo e($factura['numero_factura']); ?></strong></td>
                                            <td><?php echo date('d/m/Y', strtotime($factura['fecha'])); ?></td>
                                            <td><?php echo e($factura['proveedor_nombre']); ?></td>
                                            <td><?php echo e($factura['bodeguero_nombre']); ?></td>
                                            <td><strong>$<?php echo number_format((float) $factura['total'], 2); ?></strong></td>
                                            <td><span class="badge bg-<?php echo $badge; ?>"><?php echo e($factura['estado']); ?></span></td>
                                            <td class="admin-invoice-actions">
                                                <button class="btn btn-sm btn-outline-info" onclick="verDetallesFactura(<?php echo (int) $factura['id_factura_compra']; ?>)">
                                                    <i class="fas fa-eye"></i> Ver
                                                </button>
                                                <?php if ($factura['estado'] === 'Registrada'): ?>
                                                    <button
                                                        type="button"
                                                        class="btn btn-sm btn-success"
                                                        data-admin-invoice-action="aprobar_factura"
                                                        data-invoice-id="<?php echo (int) $factura['id_factura_compra']; ?>"
                                                        data-invoice-number="<?php echo e($factura['numero_factura']); ?>"
                                                        data-invoice-provider="<?php echo e($factura['proveedor_nombre']); ?>"
                                                        data-invoice-total="$<?php echo number_format((float) $factura['total'], 2); ?>">
                                                        <i class="fas fa-check"></i> Aprobar
                                                    </button>
                                                    <button
                                                        type="button"
                                                        class="btn btn-sm btn-danger"
                                                        data-admin-invoice-action="rechazar_factura"
                                                        data-invoice-id="<?php echo (int) $factura['id_factura_compra']; ?>"
                                                        data-invoice-number="<?php echo e($factura['numero_factura']); ?>"
                                                        data-invoice-provider="<?php echo e($factura['proveedor_nombre']); ?>"
                                                        data-invoice-total="$<?php echo number_format((float) $factura['total'], 2); ?>">
                                                        <i class="fas fa-times"></i> Rechazar
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="8" class="app-empty-state">No hay facturas que coincidan con los filtros.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade warehouse-confirm-modal" id="adminInvoiceConfirmModal" tabindex="-1" aria-labelledby="adminInvoiceConfirmTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="adminInvoiceConfirmForm">
                <input type="hidden" id="adminInvoiceConfirmId">
                <input type="hidden" id="adminInvoiceConfirmAction">

                <div class="modal-header">
                    <span class="warehouse-modal-icon" data-admin-invoice-modal-icon>
                        <i class="fas fa-file-circle-check"></i>
                    </span>
                    <div>
                        <span class="farmer-kicker">Revisión administrativa</span>
                        <h2 class="modal-title" id="adminInvoiceConfirmTitle">Aprobar factura</h2>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <div class="modal-body">
                    <p data-admin-invoice-modal-message>Revise la factura antes de continuar.</p>
                    <div class="warehouse-modal-summary">
                        <div>
                            <span>Número</span>
                            <strong data-admin-invoice-modal-number></strong>
                        </div>
                        <div>
                            <span>Proveedor</span>
                            <strong data-admin-invoice-modal-provider></strong>
                        </div>
                        <div>
                            <span>Total</span>
                            <strong data-admin-invoice-modal-total></strong>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn warehouse-modal-back" data-bs-dismiss="modal">Volver</button>
                    <button type="submit" class="btn warehouse-modal-confirm" data-admin-invoice-modal-confirm>
                        <i class="fas fa-check"></i>
                        <span>Confirmar aprobación</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalDetallesFactura" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalles de la Factura de Compra</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detallesFacturaContent"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
