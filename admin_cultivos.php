<?php
require_once 'conexion.php';
require_auth('Administrador');

// Procesar acciones AJAX
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    
    switch ($_POST['action']) {
        case 'eliminar_cultivo':
            $id = intval($_POST['id_cultivo']);
            
            try {
                // Verificar si hay lotes asociados
                $check_lotes = $conn->prepare("SELECT COUNT(*) as count FROM lotes WHERE id_cultivo = ?");
                $check_lotes->bind_param("i", $id);
                $check_lotes->execute();
                $result = $check_lotes->get_result();
                $count = $result->fetch_assoc()['count'];
                
                if ($count > 0) {
                    // Opción 1: Informar al usuario que hay lotes asociados
                    echo json_encode([
                        'success' => false, 
                        'message' => "No se puede eliminar el cultivo porque tiene $count lote(s) asociado(s). Elimine primero los lotes."
                    ]);
                    exit;
                    
                    // Opción 2 (comentada): Eliminar en cascada
                    /*
                    // Primero eliminar los lotes asociados
                    $delete_lotes = $conn->prepare("DELETE FROM lotes WHERE id_cultivo = ?");
                    $delete_lotes->bind_param("i", $id);
                    $delete_lotes->execute();
                    */
                }
                
                // Eliminar el cultivo
                $sql = "DELETE FROM cultivos WHERE id_cultivo = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $id);

                if ($stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'Cultivo eliminado exitosamente']);
                } else {
                    throw new Exception('Error al ejecutar la eliminación: ' . $stmt->error);
                }
                
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error al eliminar cultivo: ' . $e->getMessage()]);
            }
            exit;

        case 'eliminar_lote':
            $id = intval($_POST['id_lote']);
            
            try {
                $sql = "DELETE FROM lotes WHERE id_lote = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $id);

                if ($stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'Lote eliminado exitosamente']);
                } else {
                    throw new Exception('Error al ejecutar la eliminación: ' . $stmt->error);
                }
                
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error al eliminar lote: ' . $e->getMessage()]);
            }
            exit;
    }
    
    echo json_encode(['success' => false, 'message' => 'Acción no reconocida']);
    exit;
}

// Obtener cultivos con información del agricultor
$cultivos = $conn->query("
    SELECT c.*, u.nombre as agricultor_nombre,
           COUNT(l.id_lote) AS total_lotes,
           SUM(CASE WHEN l.estado_cultivo = 'en_cosecha' THEN 1 ELSE 0 END) AS lotes_en_cosecha,
           SUM(CASE WHEN l.estado_cultivo = 'finalizado' THEN 1 ELSE 0 END) AS lotes_finalizados,
           SUM(CASE WHEN l.estado_cultivo = 'cancelado' THEN 1 ELSE 0 END) AS lotes_cancelados
    FROM cultivos c
    LEFT JOIN usuarios u ON c.id_usuario = u.id_usuario
    LEFT JOIN lotes l ON c.id_cultivo = l.id_cultivo
    GROUP BY c.id_cultivo
    ORDER BY c.fecha_siembra DESC
");

// Obtener lotes con información del cultivo y agricultor
$lotes = $conn->query("
    SELECT l.*, c.tipo as cultivo_tipo, u.nombre as agricultor_nombre 
    FROM lotes l 
    LEFT JOIN cultivos c ON l.id_cultivo = c.id_cultivo 
    LEFT JOIN usuarios u ON c.id_usuario = u.id_usuario 
    ORDER BY l.id_lote DESC
");

// Obtener estadísticas de cultivos
$stats_cultivos = $conn->query("
    SELECT 
        COUNT(*) as total_cultivos,
        COUNT(DISTINCT tipo) as tipos_diferentes
    FROM cultivos
")->fetch_assoc();

$stats_lotes = $conn->query("
    SELECT 
        COUNT(*) as total_lotes
    FROM lotes
")->fetch_assoc();

// Calcular área total (intentaremos extraer números del campo 'area')
$area_total_query = $conn->query("
    SELECT area FROM lotes WHERE area IS NOT NULL AND area != ''
");

$area_total = 0;
if ($area_total_query) {
    while ($row = $area_total_query->fetch_assoc()) {
        // Extraer números del campo area usando expresiones regulares
        $area_text = $row['area'];
        preg_match_all('/\d+(?:\.\d+)?/', $area_text, $matches);
        if (!empty($matches[0])) {
            $area_total += floatval($matches[0][0]); // Tomar el primer número encontrado
        }
    }
}
?>

<section class="admin-crops">
    <header class="admin-crops__header">
        <span class="admin-crops__header-icon"><i class="fas fa-seedling"></i></span>
        <div>
            <span class="admin-section-eyebrow">Producción agrícola</span>
            <h4>Gestión de cultivos y lotes</h4>
            <p>Supervisa ciclos, terrenos y responsables desde una vista operativa.</p>
        </div>
    </header>

    <div class="admin-crops__metrics">
        <article class="admin-crops__metric admin-crops__metric--crop">
            <span class="admin-crops__metric-icon"><i class="fas fa-leaf"></i></span>
            <div><span>Cultivos</span><strong><?php echo $stats_cultivos['total_cultivos'] ?: 0; ?></strong><small>Registros activos</small></div>
        </article>
        <article class="admin-crops__metric admin-crops__metric--type">
            <span class="admin-crops__metric-icon"><i class="fas fa-layer-group"></i></span>
            <div><span>Tipos</span><strong><?php echo $stats_cultivos['tipos_diferentes'] ?: 0; ?></strong><small>Variedades registradas</small></div>
        </article>
        <article class="admin-crops__metric admin-crops__metric--lot">
            <span class="admin-crops__metric-icon"><i class="fas fa-map-location-dot"></i></span>
            <div><span>Lotes</span><strong><?php echo $stats_lotes['total_lotes'] ?: 0; ?></strong><small>Superficie productiva</small></div>
        </article>
        <article class="admin-crops__metric admin-crops__metric--area">
            <span class="admin-crops__metric-icon"><i class="fas fa-maximize"></i></span>
            <div><span>Área aprox.</span><strong><?php echo number_format($area_total, 1); ?></strong><small>Total reportado</small></div>
        </article>
    </div>

    <ul class="nav admin-crops__tabs" id="cultivoTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="cultivos-tab" data-bs-toggle="tab" data-bs-target="#cultivos-section" type="button">
                <i class="fas fa-seedling"></i> Cultivos
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="lotes-tab" data-bs-toggle="tab" data-bs-target="#lotes-section" type="button">
                <i class="fas fa-map-location-dot"></i> Lotes
            </button>
        </li>
    </ul>

    <div class="tab-content admin-crops__content" id="cultivoTabsContent">
        <div class="tab-pane fade show active" id="cultivos-section">
            <section class="admin-crops__panel">
                <div class="admin-crops__panel-heading">
                    <div><span class="admin-section-eyebrow">Registro productivo</span><h5>Cultivos registrados</h5></div>
                    <span class="admin-crops__count"><?php echo $cultivos ? $cultivos->num_rows : 0; ?> resultados</span>
                </div>
                <?php if ($cultivos && $cultivos->num_rows > 0): ?>
                    <div class="admin-crops__notice">
                        <span><i class="fas fa-triangle-exclamation"></i></span>
                        <p><strong>Eliminación protegida</strong> No se puede eliminar un cultivo con lotes asociados.</p>
                    </div>
                    <div class="table-responsive admin-crops__table-wrap">
                        <table class="table align-middle admin-crops__table" data-app-table-owner="admin-crops-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Tipo de cultivo</th>
                                    <th>Fecha siembra</th>
                                    <th>Agricultor</th>
                                    <th>Estado</th>
                                    <th>Lotes</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($cultivo = $cultivos->fetch_assoc()): ?>
                                <?php 
                                $lotes_count = (int) db_value(
                                    $conn,
                                    "SELECT COUNT(*) FROM lotes WHERE id_cultivo = ?",
                                    "i",
                                    [(int) $cultivo['id_cultivo']],
                                    0
                                );
                                ?>
                                <tr>
                                    <td><strong>#<?php echo (int) $cultivo['id_cultivo']; ?></strong></td>
                                    <td><span class="admin-crop-tag"><i class="fas fa-leaf"></i><?php echo htmlspecialchars($cultivo['tipo']); ?></span></td>
                                    <td><?php echo date('d/m/Y', strtotime($cultivo['fecha_siembra'])); ?></td>
                                    <td><?php echo htmlspecialchars($cultivo['agricultor_nombre'] ?: 'No asignado'); ?></td>
                                    <td>
                                        <?php 
                                        if ((int) $cultivo['lotes_en_cosecha'] > 0) {
                                            echo '<span class="admin-crop-status admin-crop-status--harvest"><i></i>En cosecha</span>';
                                        } elseif ((int) $cultivo['total_lotes'] > 0
                                            && (int) $cultivo['lotes_finalizados'] === (int) $cultivo['total_lotes']) {
                                            echo '<span class="admin-crop-status admin-crop-status--finished"><i></i>Finalizado</span>';
                                        } elseif ((int) $cultivo['total_lotes'] > 0
                                            && (int) $cultivo['lotes_cancelados'] === (int) $cultivo['total_lotes']) {
                                            echo '<span class="admin-crop-status admin-crop-status--cancelled"><i></i>Cancelado</span>';
                                        } else {
                                            echo '<span class="admin-crop-status admin-crop-status--active"><i></i>Activo</span>';
                                        }
                                        ?>
                                    </td>
                                    <td><span class="admin-crop-lots"><?php echo $lotes_count; ?> lote(s)</span></td>
                                    <td>
                                        <div class="admin-crops__actions">
                                            <button class="admin-crops__action admin-crops__action--view" onclick="verDetallesCultivo(<?php echo (int) $cultivo['id_cultivo']; ?>)" aria-label="Ver cultivo <?php echo htmlspecialchars($cultivo['tipo'], ENT_QUOTES, 'UTF-8'); ?>">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button
                                                type="button"
                                                class="admin-crops__action admin-crops__action--delete"
                                                data-admin-crop-delete="cultivo"
                                                data-record-id="<?php echo (int) $cultivo['id_cultivo']; ?>"
                                                data-record-name="<?php echo htmlspecialchars($cultivo['tipo'], ENT_QUOTES, 'UTF-8'); ?>"
                                                <?php echo $lotes_count > 0 ? 'disabled title="No se puede eliminar: tiene lotes asociados"' : ''; ?>>
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="app-empty-state">No hay cultivos registrados.</div>
                <?php endif; ?>
            </section>
        </div>

        <div class="tab-pane fade" id="lotes-section">
            <section class="admin-crops__panel">
                <div class="admin-crops__panel-heading">
                    <div><span class="admin-section-eyebrow">Superficie productiva</span><h5>Lotes registrados</h5></div>
                    <span class="admin-crops__count"><?php echo $lotes ? $lotes->num_rows : 0; ?> resultados</span>
                </div>
                <?php if ($lotes && $lotes->num_rows > 0): ?>
                    <div class="table-responsive admin-crops__table-wrap">
                        <table class="table align-middle admin-crops__table" data-app-table-owner="admin-lots-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Ubicación</th>
                                    <th>Área/Zona</th>
                                    <th>Cultivo</th>
                                    <th>Agricultor</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($lote = $lotes->fetch_assoc()): ?>
                                <tr>
                                    <td><strong>#<?php echo (int) $lote['id_lote']; ?></strong></td>
                                    <td><strong><?php echo htmlspecialchars($lote['ubicacion']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($lote['area']); ?></td>
                                    <td><span class="admin-crop-tag admin-crop-tag--lot"><i class="fas fa-seedling"></i><?php echo htmlspecialchars($lote['cultivo_tipo'] ?: 'Sin cultivo'); ?></span></td>
                                    <td><?php echo htmlspecialchars($lote['agricultor_nombre'] ?: 'No asignado'); ?></td>
                                    <td>
                                        <div class="admin-crops__actions">
                                            <button class="admin-crops__action admin-crops__action--view" onclick="verDetalleLote(<?php echo (int) $lote['id_lote']; ?>)" aria-label="Ver lote <?php echo htmlspecialchars($lote['ubicacion'], ENT_QUOTES, 'UTF-8'); ?>">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button
                                                type="button"
                                                class="admin-crops__action admin-crops__action--delete"
                                                data-admin-crop-delete="lote"
                                                data-record-id="<?php echo (int) $lote['id_lote']; ?>"
                                                data-record-name="<?php echo htmlspecialchars($lote['ubicacion'], ENT_QUOTES, 'UTF-8'); ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="app-empty-state">No hay lotes registrados.</div>
                <?php endif; ?>
            </section>
        </div>
    </div>
</section>

<!-- Modal para detalles del cultivo -->
<div class="modal fade admin-premium-modal admin-crop-detail-modal" id="modalDetallesCultivo" tabindex="-1" aria-labelledby="modalDetallesCultivoTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <span class="admin-premium-modal__icon"><i class="fas fa-seedling"></i></span>
                <div class="admin-premium-modal__heading">
                    <span class="farmer-kicker">Producción agrícola</span>
                    <h2 class="modal-title" id="modalDetallesCultivoTitle">Detalle del cultivo</h2>
                    <p>Estado, responsable y ciclo actual de producción.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body" id="detallesCultivoContent">
                <!-- Contenido cargado dinámicamente -->
            </div>
            <div class="modal-footer">
                <span class="admin-premium-modal__security"><i class="fas fa-leaf"></i> Información agrícola</span>
                <button type="button" class="btn admin-premium-modal__close" data-bs-dismiss="modal">
                    <i class="fas fa-xmark"></i> Cerrar detalle
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para detalles del lote -->
<div class="modal fade admin-premium-modal admin-crop-detail-modal" id="modalDetalleLote" tabindex="-1" aria-labelledby="modalDetalleLoteTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <span class="admin-premium-modal__icon"><i class="fas fa-map-location-dot"></i></span>
                <div class="admin-premium-modal__heading">
                    <span class="farmer-kicker">Superficie productiva</span>
                    <h2 class="modal-title" id="modalDetalleLoteTitle">Detalle del lote</h2>
                    <p>Ubicación, área y cultivo asignado al terreno.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body" id="detalleLoteContent">
                <!-- Contenido cargado dinámicamente -->
            </div>
            <div class="modal-footer">
                <span class="admin-premium-modal__security"><i class="fas fa-location-dot"></i> Registro territorial</span>
                <button type="button" class="btn admin-premium-modal__close" data-bs-dismiss="modal">
                    <i class="fas fa-xmark"></i> Cerrar detalle
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para confirmar eliminación -->
<div class="modal fade admin-premium-modal admin-delete-modal" id="adminCropDeleteModal" tabindex="-1" aria-labelledby="adminCropDeleteTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="adminCropDeleteForm">
                <input type="hidden" id="adminCropDeleteType">
                <input type="hidden" id="adminCropDeleteId">

                <div class="modal-header">
                    <span class="admin-delete-modal__icon"><i class="fas fa-trash-can"></i></span>
                    <div class="admin-premium-modal__heading">
                        <span class="farmer-kicker">Acción irreversible</span>
                        <h2 class="modal-title" id="adminCropDeleteTitle">Eliminar registro</h2>
                        <p>Esta acción retirará el registro del sistema.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <div class="modal-body">
                    <div class="admin-delete-modal__warning">
                        <i class="fas fa-triangle-exclamation"></i>
                        <p>
                            <strong data-admin-delete-question>¿Desea continuar?</strong>
                            <span>Compruebe la información antes de confirmar.</span>
                        </p>
                    </div>
                    <div class="admin-delete-modal__record">
                        <span data-admin-delete-label>Registro</span>
                        <strong data-admin-delete-name></strong>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn warehouse-modal-back" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn admin-delete-modal__confirm" data-admin-delete-confirm data-skip-loading="1">
                        <i class="fas fa-trash-can"></i>
                        <span>Eliminar definitivamente</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
