<?php
require_once 'conexion.php';
require_auth('Administrador');

// Procesar acciones AJAX
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json; charset=utf-8');

    $accionesPermitidas = [
        'aprobar_solicitud' => ['estado' => 'Aprobado', 'mensaje' => 'Solicitud aprobada'],
        'rechazar_solicitud' => ['estado' => 'Rechazado', 'mensaje' => 'Solicitud rechazada'],
    ];
    $action = (string) $_POST['action'];
    $id = (int) ($_POST['id_solicitud'] ?? 0);

    if (!isset($accionesPermitidas[$action])) {
        echo json_encode(['success' => false, 'message' => 'No tienes permisos para realizar esta acción.']);
        exit;
    }

    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Solicitud inválida.']);
        exit;
    }

    try {
        $nuevoEstado = $accionesPermitidas[$action]['estado'];
        $stmt = $conn->prepare(
            "UPDATE productos_solicitud
             SET estado = ?
             WHERE id_producto_solicitud = ? AND estado = 'Pendiente'"
        );
        $stmt->bind_param("si", $nuevoEstado, $id);
        $stmt->execute();
        $actualizadas = $stmt->affected_rows;
        $stmt->close();

        if ($actualizadas !== 1) {
            echo json_encode([
                'success' => false,
                'message' => 'Acción no permitida para el estado actual de la solicitud.',
            ]);
            exit;
        }

        echo json_encode(['success' => true, 'message' => $accionesPermitidas[$action]['mensaje']]);
    } catch (Throwable $exception) {
        error_log('Error al procesar solicitud como administrador: ' . $exception->getMessage());
        echo json_encode(['success' => false, 'message' => 'No se pudo procesar la solicitud.']);
    }

    exit;
}

// Obtener solicitudes con información del agricultor
$solicitudes = $conn->query("
    SELECT ps.*, u.nombre as agricultor_nombre, u.email as agricultor_email
    FROM productos_solicitud ps
    JOIN usuarios u ON ps.id_agricultor = u.id_usuario
    ORDER BY ps.fecha DESC
");

$stats_solicitudes = $conn->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN estado = 'Pendiente' THEN 1 ELSE 0 END) as pendientes,
        SUM(CASE WHEN estado = 'Entregado' THEN 1 ELSE 0 END) as entregadas,
        SUM(CASE WHEN estado = 'Rechazado' THEN 1 ELSE 0 END) as rechazadas
    FROM productos_solicitud
")->fetch_assoc();
?>

<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4><i class="fas fa-clipboard-list"></i> Gestión de Solicitudes de Productos</h4>
            </div>
            <div class="card-body">
                <!-- Estadísticas -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body text-center">
                                <i class="fas fa-list fa-2x mb-2"></i>
                                <h3><?php echo $stats_solicitudes['total'] ?: 0; ?></h3>
                                <p class="mb-0">Total Solicitudes</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body text-center">
                                <i class="fas fa-clock fa-2x mb-2"></i>
                                <h3><?php echo $stats_solicitudes['pendientes'] ?: 0; ?></h3>
                                <p class="mb-0">Pendientes</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body text-center">
                                <i class="fas fa-check fa-2x mb-2"></i>
                                <h3><?php echo $stats_solicitudes['entregadas'] ?: 0; ?></h3>
                                <p class="mb-0">Entregadas</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-danger text-white">
                            <div class="card-body text-center">
                                <i class="fas fa-times fa-2x mb-2"></i>
                                <h3><?php echo $stats_solicitudes['rechazadas'] ?: 0; ?></h3>
                                <p class="mb-0">Rechazadas</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabla de solicitudes -->
                <?php if ($solicitudes && $solicitudes->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Fecha</th>
                                <th>Agricultor</th>
                                <th>Producto Solicitado</th>
                                <th>Cantidad</th>
                                <th>Estado</th>
                                <th>Observaciones</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($solicitud = $solicitudes->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $solicitud['id_producto_solicitud']; ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($solicitud['fecha'])); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($solicitud['agricultor_nombre']); ?></strong>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($solicitud['agricultor_email']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($solicitud['nombre']); ?></td>
                                <td><?php echo $solicitud['cantidad_solicitada']; ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $solicitud['estado'] == 'Pendiente' ? 'warning' :
                                            ($solicitud['estado'] == 'Aprobado' ? 'primary' :
                                            ($solicitud['estado'] == 'Entregado' ? 'success' :
                                            ($solicitud['estado'] == 'Cancelado' ? 'secondary' : 'danger')));
                                    ?>">
                                        <?php echo $solicitud['estado']; ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($solicitud['observaciones'] ?: 'Ninguna'); ?></td>
                                <td>
                                    <?php if ($solicitud['estado'] == 'Pendiente'): ?>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-success"
                                        data-admin-request-action="aprobar"
                                        data-request-id="<?php echo $solicitud['id_producto_solicitud']; ?>"
                                        data-farmer="<?php echo htmlspecialchars($solicitud['agricultor_nombre'], ENT_QUOTES, 'UTF-8'); ?>"
                                        data-product="<?php echo htmlspecialchars($solicitud['nombre'], ENT_QUOTES, 'UTF-8'); ?>"
                                        data-quantity="<?php echo htmlspecialchars($solicitud['cantidad_solicitada'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <i class="fas fa-check"></i> Aprobar
                                    </button>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-danger"
                                        data-admin-request-action="rechazar"
                                        data-request-id="<?php echo $solicitud['id_producto_solicitud']; ?>"
                                        data-farmer="<?php echo htmlspecialchars($solicitud['agricultor_nombre'], ENT_QUOTES, 'UTF-8'); ?>"
                                        data-product="<?php echo htmlspecialchars($solicitud['nombre'], ENT_QUOTES, 'UTF-8'); ?>"
                                        data-quantity="<?php echo htmlspecialchars($solicitud['cantidad_solicitada'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <i class="fas fa-times"></i> Rechazar
                                    </button>
                                    <?php else: ?>
                                    <span class="badge bg-secondary">Procesada</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-clipboard-list fa-3x mb-3"></i>
                    <h5>No hay solicitudes registradas</h5>
                    <p>Las solicitudes de los agricultores aparecerán aquí.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="modal fade warehouse-confirm-modal" id="adminRequestConfirmModal" tabindex="-1" aria-labelledby="adminRequestConfirmTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="adminRequestConfirmForm">
                <input type="hidden" id="adminRequestConfirmId">
                <input type="hidden" id="adminRequestConfirmAction">

                <div class="modal-header">
                    <span class="warehouse-modal-icon" data-admin-request-modal-icon>
                        <i class="fas fa-check"></i>
                    </span>
                    <div>
                        <span class="farmer-kicker">Confirmación administrativa</span>
                        <h2 class="modal-title" id="adminRequestConfirmTitle">Aprobar solicitud</h2>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <div class="modal-body">
                    <p data-admin-request-modal-message>Revise la información antes de continuar.</p>
                    <div class="warehouse-modal-summary">
                        <div>
                            <span>Agricultor</span>
                            <strong data-admin-request-modal-farmer></strong>
                        </div>
                        <div>
                            <span>Producto</span>
                            <strong data-admin-request-modal-product></strong>
                        </div>
                        <div>
                            <span>Cantidad</span>
                            <strong data-admin-request-modal-quantity></strong>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn warehouse-modal-back" data-bs-dismiss="modal">Volver</button>
                    <button type="submit" class="btn warehouse-modal-confirm" data-admin-request-modal-confirm>
                        <i class="fas fa-check"></i>
                        <span>Confirmar aprobación</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
