<?php
require_once 'conexion.php';
require_auth('Administrador');

// Procesar acciones AJAX
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    switch ($_POST['action']) {
        case 'crear_proveedor':
            $nombre = trim($_POST['nombre'] ?? '');
            $ruc = trim($_POST['ruc_cedula'] ?? '');
            $telefono = trim($_POST['telefono'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $direccion = trim($_POST['direccion'] ?? '');
            
            if (empty($nombre)) {
                echo json_encode(['success' => false, 'message' => 'El nombre del proveedor es requerido']);
                exit;
            }

            if (empty($ruc)) {
                echo json_encode(['success' => false, 'message' => 'El RUC/Cédula es requerido']);
                exit;
            }
            
            // Verificar si el proveedor ya existe (por nombre, email o ruc_cedula)
            $check = $conn->prepare("SELECT id_proveedor FROM proveedor WHERE Nombre = ? OR email = ? OR ruc_cedula = ?");
            $check->bind_param("sss", $nombre, $email, $ruc);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                echo json_encode(['success' => false, 'message' => 'Ya existe un proveedor con ese nombre, RUC o email']);
                exit;
            }
            
            // Insertar el nuevo proveedor
            $stmt = $conn->prepare("INSERT INTO proveedor (Nombre, ruc_cedula, telefono, email, direccion) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $nombre, $ruc, $telefono, $email, $direccion);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Proveedor creado exitosamente', 'id' => $conn->insert_id]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al crear proveedor: ' . $stmt->error]);
            }
            exit;
            
        case 'editar_proveedor':
            $id = intval($_POST['id_proveedor']);
            $nombre = trim($_POST['nombre'] ?? '');
            $telefono = trim($_POST['telefono'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $direccion = trim($_POST['direccion'] ?? '');
            
            if (empty($nombre)) {
                echo json_encode(['success' => false, 'message' => 'El nombre del proveedor es requerido']);
                exit;
            }
            
            $stmt = $conn->prepare("UPDATE proveedor SET Nombre=?, telefono=?, email=?, direccion=? WHERE id_proveedor=?");
            $stmt->bind_param("ssssi", $nombre, $telefono, $email, $direccion, $id);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Proveedor actualizado exitosamente']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al actualizar: ' . $stmt->error]);
            }
            exit;
            
        case 'eliminar_proveedor':
            $id = intval($_POST['id_proveedor']);
            
            // Verificar si tiene pedidos asociados
            $check = $conn->prepare("SELECT COUNT(*) as count FROM pedidos WHERE id_proveedor = ?");
            $check->bind_param("i", $id);
            $check->execute();
            $count = $check->get_result()->fetch_assoc()['count'];
            
            if ($count > 0) {
                echo json_encode(['success' => false, 'message' => "No se puede eliminar: tiene $count pedido(s) asociado(s)"]);
                exit;
            }
            
            $stmt = $conn->prepare("DELETE FROM proveedor WHERE id_proveedor=?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Proveedor eliminado exitosamente']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al eliminar: ' . $stmt->error]);
            }
            exit;
            
        case 'crear_pedido':
            $id_proveedor = intval($_POST['id_proveedor']);
            $id_usuario = trim($_POST['id_usuario']);
            $id_insumo = intval($_POST['id_insumo'] ?? 0);
            $cantidad = floatval($_POST['cantidad']);
            $observaciones = trim($_POST['observaciones'] ?? '');
            
            if ($cantidad <= 0 || $id_proveedor <= 0 || empty($id_usuario) || $id_insumo <= 0) {
                echo json_encode(['success' => false, 'message' => 'Todos los campos son requeridos y deben ser válidos']);
                exit;
            }

            $proveedorExiste = (int) db_value(
                $conn,
                'SELECT COUNT(*) FROM proveedor WHERE id_proveedor = ?',
                'i',
                [$id_proveedor],
                0
            );
            $usuarioExiste = (int) db_value(
                $conn,
                'SELECT COUNT(*) FROM usuarios WHERE id_usuario = ?',
                's',
                [$id_usuario],
                0
            );
            $insumo = db_fetch_one(
                $conn,
                'SELECT id_insumos, nombre, unidad_medida FROM insumos_agricolas WHERE id_insumos = ?',
                'i',
                [$id_insumo]
            );

            if (!$proveedorExiste || !$usuarioExiste || !$insumo) {
                echo json_encode(['success' => false, 'message' => 'Proveedor, usuario o producto no válido']);
                exit;
            }

            $nombre_producto = $insumo['nombre'];
            $unidad_medida = $insumo['unidad_medida'] ?: 'unid';
            $stmt = $conn->prepare(
                "INSERT INTO pedidos (
                    id_proveedor, id_usuario, id_insumo, nombre_producto, cantidad,
                    unidad_medida, observaciones, estado, fecha
                 ) VALUES (?, ?, ?, ?, ?, ?, ?, 'Pendiente', NOW())"
            );
            $stmt->bind_param(
                "isisdss",
                $id_proveedor,
                $id_usuario,
                $id_insumo,
                $nombre_producto,
                $cantidad,
                $unidad_medida,
                $observaciones
            );

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Pedido creado exitosamente', 'id' => $conn->insert_id]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al crear pedido: ' . $stmt->error]);
            }
            exit;

        case 'editar_pedido':
            $id = intval($_POST['id_pedido'] ?? 0);
            $id_proveedor = intval($_POST['id_proveedor'] ?? 0);
            $id_usuario = trim($_POST['id_usuario'] ?? '');
            $id_insumo = intval($_POST['id_insumo'] ?? 0);
            $cantidad = floatval($_POST['cantidad'] ?? 0);
            $observaciones = trim($_POST['observaciones'] ?? '');

            if ($id <= 0 || $cantidad <= 0 || $id_proveedor <= 0 || empty($id_usuario) || $id_insumo <= 0) {
                echo json_encode(['success' => false, 'message' => 'Todos los campos son requeridos y deben ser válidos']);
                exit;
            }

            $insumo = db_fetch_one(
                $conn,
                'SELECT nombre, unidad_medida FROM insumos_agricolas WHERE id_insumos = ?',
                'i',
                [$id_insumo]
            );

            if (!$insumo) {
                echo json_encode(['success' => false, 'message' => 'El producto seleccionado no existe']);
                exit;
            }

            $proveedorExiste = (int) db_value(
                $conn,
                'SELECT COUNT(*) FROM proveedor WHERE id_proveedor = ?',
                'i',
                [$id_proveedor],
                0
            );
            $usuarioExiste = (int) db_value(
                $conn,
                'SELECT COUNT(*) FROM usuarios WHERE id_usuario = ?',
                's',
                [$id_usuario],
                0
            );
            if (!$proveedorExiste || !$usuarioExiste) {
                echo json_encode(['success' => false, 'message' => 'Proveedor o usuario responsable no válido']);
                exit;
            }

            $stmt = $conn->prepare(
                'UPDATE pedidos
                 SET id_proveedor = ?, id_usuario = ?, id_insumo = ?, nombre_producto = ?,
                     cantidad = ?, unidad_medida = ?, observaciones = ?
                 WHERE id_pedidos = ? AND estado = \'Pendiente\''
            );
            $nombre_producto = $insumo['nombre'];
            $unidad_medida = $insumo['unidad_medida'] ?: 'unid';
            $stmt->bind_param(
                'isisdssi',
                $id_proveedor,
                $id_usuario,
                $id_insumo,
                $nombre_producto,
                $cantidad,
                $unidad_medida,
                $observaciones,
                $id
            );

            if ($stmt->execute() && $stmt->affected_rows === 1) {
                echo json_encode(['success' => true, 'message' => 'Pedido actualizado exitosamente']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Solo se pueden editar pedidos pendientes']);
            }
            exit;

        case 'cancelar_pedido':
            $id = intval($_POST['id_pedido'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Pedido inválido']);
                exit;
            }

            $actualizados = db_execute(
                $conn,
                "UPDATE pedidos SET estado = 'Cancelado'
                 WHERE id_pedidos = ? AND estado = 'Pendiente'",
                'i',
                [$id]
            );

            echo json_encode($actualizados === 1
                ? ['success' => true, 'message' => 'Pedido cancelado correctamente']
                : ['success' => false, 'message' => 'Solo se pueden cancelar pedidos pendientes']);
            exit;
    }
    
    echo json_encode(['success' => false, 'message' => 'Acción no reconocida']);
    exit;
}

// Obtener proveedores
$proveedores = $conn->query("SELECT * FROM proveedor ORDER BY Nombre");

// Obtener pedidos con información de proveedor y usuario
$pedidos = $conn->query("
    SELECT p.*, 
           pr.Nombre as proveedor_nombre,
           pr.telefono as proveedor_telefono,
           u.nombre as usuario_nombre
    FROM pedidos p
    LEFT JOIN proveedor pr ON p.id_proveedor = pr.id_proveedor
    LEFT JOIN usuarios u ON p.id_usuario = u.id_usuario
    ORDER BY p.fecha DESC
");

// Obtener usuarios para el formulario
$usuarios = $conn->query("SELECT id_usuario, nombre FROM usuarios ORDER BY nombre");
$insumos = $conn->query("
    SELECT id_insumos, nombre, unidad_medida, cantidad
    FROM insumos_agricolas
    ORDER BY nombre
");

// Estadísticas
$stats = $conn->query("
    SELECT 
        (SELECT COUNT(*) FROM proveedor) as total_proveedores,
        (SELECT COUNT(*) FROM pedidos) as total_pedidos,
        (SELECT COUNT(*) FROM pedidos WHERE estado = 'Pendiente') as pedidos_pendientes
")->fetch_assoc();
?>

<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4><i class="fas fa-truck"></i> Gestión de Proveedores y Pedidos</h4>
            </div>
            <div class="card-body">
                <!-- Estadísticas -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card bg-primary text-white">
                            <div class="card-body text-center">
                                <i class="fas fa-truck fa-2x mb-2"></i>
                                <h3><?php echo $stats['total_proveedores'] ?: 0; ?></h3>
                                <p class="mb-0">Proveedores</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-success text-white">
                            <div class="card-body text-center">
                                <i class="fas fa-cart-shopping fa-2x mb-2"></i>
                                <h3><?php echo $stats['total_pedidos'] ?: 0; ?></h3>
                                <p class="mb-0">Total Pedidos</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-warning text-white">
                            <div class="card-body text-center">
                                <i class="fas fa-calendar-day fa-2x mb-2"></i>
                                <h3><?php echo $stats['pedidos_pendientes'] ?: 0; ?></h3>
                                <p class="mb-0">Pedidos Pendientes</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabs -->
                <ul class="nav nav-tabs" id="proveedorPedidoTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="proveedores-tab" data-bs-toggle="tab" data-bs-target="#proveedores-section" type="button">
                            <i class="fas fa-truck"></i> Proveedores
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="pedidos-tab" data-bs-toggle="tab" data-bs-target="#pedidos-section" type="button">
                            <i class="fas fa-cart-shopping"></i> Pedidos
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="proveedorPedidoTabsContent">
                    <!-- Sección de Proveedores -->
                    <div class="tab-pane fade show active" id="proveedores-section">
                        <div class="mt-3">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5>Lista de Proveedores</h5>
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrearProveedor">
                                    <i class="fas fa-plus"></i> Nuevo Proveedor
                                </button>
                            </div>

                            <?php if ($proveedores && $proveedores->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>ID</th>
                                            <th>Nombre</th>
                                            <th>Teléfono</th>
                                            <th>Email</th>
                                            <th>Dirección</th>
                                            <th>Pedidos</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($proveedor = $proveedores->fetch_assoc()): ?>
                                        <?php
                                        // Contar pedidos del proveedor
                                        $pedidos_count = (int) db_value(
                                            $conn,
                                            "SELECT COUNT(*) FROM pedidos WHERE id_proveedor = ?",
                                            "i",
                                            [(int) $proveedor['id_proveedor']],
                                            0
                                        );
                                        ?>
                                        <tr>
                                            <td><?php echo $proveedor['id_proveedor']; ?></td>
                                            <td><strong><?php echo htmlspecialchars($proveedor['Nombre']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($proveedor['telefono'] ?: 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($proveedor['email'] ?: 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($proveedor['direccion'] ?: 'N/A'); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $pedidos_count > 0 ? 'success' : 'secondary'; ?>">
                                                    <?php echo $pedidos_count; ?> pedidos
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary" 
                                                        onclick="editarProveedor(<?php echo $proveedor['id_proveedor']; ?>, '<?php echo htmlspecialchars($proveedor['Nombre'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($proveedor['telefono'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($proveedor['email'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($proveedor['direccion'], ENT_QUOTES); ?>')">
                                                    <i class="fas fa-pen-to-square"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger <?php echo $pedidos_count > 0 ? 'disabled' : ''; ?>" 
                                                        onclick="eliminarProveedor(<?php echo $proveedor['id_proveedor']; ?>)"
                                                        <?php echo $pedidos_count > 0 ? 'title="No se puede eliminar: tiene pedidos asociados"' : ''; ?>>
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                            <div class="alert alert-info text-center">
                                <i class="fas fa-truck fa-3x mb-3"></i>
                                <h5>No hay proveedores registrados</h5>
                                <p>Comience agregando proveedores para poder crear pedidos.</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Sección de Pedidos -->
                    <div class="tab-pane fade" id="pedidos-section">
                        <div class="mt-3">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5>Lista de Pedidos</h5>
                                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalCrearPedido">
                                    <i class="fas fa-plus"></i> Nuevo Pedido
                                </button>
                            </div>

                            <?php if ($pedidos && $pedidos->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>ID</th>
                                            <th>Fecha</th>
                                            <th>Proveedor</th>
                                            <th>Producto</th>
                                            <th>Cantidad</th>
                                            <th>Usuario</th>
                                            <th>Estado</th>
                                            <th>Observación</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($pedido = $pedidos->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $pedido['id_pedidos']; ?></td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($pedido['fecha'])); ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($pedido['proveedor_nombre'] ?: 'N/A'); ?></strong>
                                                <?php if ($pedido['proveedor_telefono']): ?>
                                                <br><small class="text-muted"><?php echo $pedido['proveedor_telefono']; ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($pedido['nombre_producto']); ?></td>
                                            <td>
                                                <span class="badge bg-primary fs-6">
                                                    <?php echo $pedido['cantidad']; ?> <?php echo htmlspecialchars($pedido['unidad_medida']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($pedido['usuario_nombre'] ?: 'N/A'); ?></td>
                                            <td>
                                                <?php
                                                $pedidoBadge = $pedido['estado'] === 'Recibido'
                                                    ? 'success'
                                                    : ($pedido['estado'] === 'Cancelado' ? 'secondary' : 'warning');
                                                ?>
                                                <span class="badge bg-<?php echo $pedidoBadge; ?> admin-order-status admin-order-status--<?php echo strtolower($pedido['estado']); ?>">
                                                    <?php echo htmlspecialchars($pedido['estado']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($pedido['observaciones'] ?: 'Sin observación'); ?></td>
                                            <td class="text-nowrap">
                                                <?php if ($pedido['estado'] === 'Pendiente'): ?>
                                                <button
                                                        type="button"
                                                        class="btn btn-sm btn-outline-primary"
                                                        data-edit-order
                                                        data-order-id="<?php echo (int) $pedido['id_pedidos']; ?>"
                                                        data-provider-id="<?php echo (int) $pedido['id_proveedor']; ?>"
                                                        data-user-id="<?php echo htmlspecialchars($pedido['id_usuario'], ENT_QUOTES); ?>"
                                                        data-item-id="<?php echo (int) $pedido['id_insumo']; ?>"
                                                        data-quantity="<?php echo htmlspecialchars($pedido['cantidad'], ENT_QUOTES); ?>"
                                                        data-observations="<?php echo htmlspecialchars($pedido['observaciones'] ?? '', ENT_QUOTES); ?>"
                                                        title="Editar pedido">
                                                    <i class="fas fa-pen"></i> Editar
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger"
                                                        onclick="cancelarPedido(<?php echo $pedido['id_pedidos']; ?>)">
                                                    <i class="fas fa-ban"></i> Cancelar
                                                </button>
                                                <?php else: ?>
                                                    <span class="text-muted">Sin acciones</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                            <div class="alert alert-info text-center">
                                <i class="fas fa-cart-shopping fa-3x mb-3"></i>
                                <h5>No hay pedidos registrados</h5>
                                <p>Los pedidos a proveedores aparecerán aquí.</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Crear Proveedor -->
<div class="modal fade admin-premium-modal admin-purchase-modal" id="modalCrearProveedor" tabindex="-1" aria-labelledby="modalCrearProveedorTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="formCrearProveedor">
                <div class="modal-header">
                    <span class="admin-premium-modal__icon admin-purchase-modal__icon">
                        <i class="fas fa-building-circle-check"></i>
                    </span>
                    <div class="admin-premium-modal__heading">
                        <span class="farmer-kicker">Red de abastecimiento</span>
                        <h5 class="modal-title" id="modalCrearProveedorTitle">Crear proveedor</h5>
                        <p>Registra un aliado comercial para tus próximos pedidos.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="admin-purchase-modal__notice">
                        <span><i class="fas fa-shield-halved"></i></span>
                        <div>
                            <strong>Información comercial verificada</strong>
                            <p>Los campos marcados son necesarios para identificar y contactar al proveedor.</p>
                        </div>
                    </div>
                    <div class="admin-purchase-modal__grid">
                        <div class="admin-purchase-field">
                            <label for="crear_nombre">Nombre comercial <b>*</b></label>
                            <div class="admin-purchase-field__control">
                                <i class="fas fa-building"></i>
                                <input type="text" class="form-control" id="crear_nombre" name="nombre" placeholder="Ej. Agroinsumos del Pacífico" autocomplete="organization" required>
                            </div>
                        </div>
                        <div class="admin-purchase-field">
                            <label for="crear_ruc">RUC / Cédula <b>*</b></label>
                            <div class="admin-purchase-field__control">
                                <i class="fas fa-id-card"></i>
                                <input type="text" class="form-control" id="crear_ruc" name="ruc_cedula" placeholder="Número de identificación" inputmode="numeric" required>
                            </div>
                        </div>
                        <div class="admin-purchase-field">
                            <label for="crear_telefono">Teléfono <b>*</b></label>
                            <div class="admin-purchase-field__control">
                                <i class="fas fa-phone"></i>
                                <input type="tel" class="form-control" id="crear_telefono" name="telefono" placeholder="Ej. 099 123 4567" autocomplete="tel" required>
                            </div>
                        </div>
                        <div class="admin-purchase-field">
                            <label for="crear_email">Correo electrónico <b>*</b></label>
                            <div class="admin-purchase-field__control">
                                <i class="fas fa-envelope"></i>
                                <input type="email" class="form-control" id="crear_email" name="email" placeholder="ventas@proveedor.com" autocomplete="email" required>
                            </div>
                        </div>
                        <div class="admin-purchase-field admin-purchase-field--wide">
                            <label for="crear_direccion">Dirección <small>Opcional</small></label>
                            <div class="admin-purchase-field__control">
                                <i class="fas fa-location-dot"></i>
                                <input type="text" class="form-control" id="crear_direccion" name="direccion" placeholder="Ciudad, sector y referencia" autocomplete="street-address">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <span class="admin-premium-modal__security">
                        <i class="fas fa-lock"></i> Datos protegidos
                    </span>
                    <button type="button" class="btn admin-purchase-modal__cancel" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary admin-purchase-modal__submit">
                        <i class="fas fa-plus"></i> Guardar proveedor
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Proveedor -->
<div class="modal fade" id="modalEditarProveedor" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Proveedor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formEditarProveedor">
                <div class="modal-body">
                    <input type="hidden" name="id_proveedor" id="edit_proveedor_id">
                    <div class="mb-3">
                        <label class="form-label">Nombre *</label>
                        <input type="text" class="form-control" name="nombre" id="edit_proveedor_nombre" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Teléfono</label>
                        <input type="text" class="form-control" name="telefono" id="edit_proveedor_telefono">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" id="edit_proveedor_email">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Dirección</label>
                        <textarea class="form-control" name="direccion" id="edit_proveedor_direccion" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Actualizar Proveedor</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Pedido -->
<div class="modal fade" id="modalEditarPedido" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-pen-to-square"></i> Editar Pedido</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="formEditarPedido">
                <input type="hidden" name="id_pedido" id="edit_pedido_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Proveedor *</label>
                        <select class="form-control" name="id_proveedor" id="edit_pedido_proveedor" required>
                            <option value="">Seleccionar proveedor</option>
                            <?php
                            $proveedores->data_seek(0);
                            while ($prov = $proveedores->fetch_assoc()):
                            ?>
                            <option value="<?php echo $prov['id_proveedor']; ?>">
                                <?php echo htmlspecialchars($prov['Nombre']); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Usuario Responsable *</label>
                        <select class="form-control" name="id_usuario" id="edit_pedido_usuario" required>
                            <option value="">Seleccionar usuario</option>
                            <?php
                            $usuarios->data_seek(0);
                            while ($user = $usuarios->fetch_assoc()):
                            ?>
                            <option value="<?php echo $user['id_usuario']; ?>">
                                <?php echo htmlspecialchars($user['nombre']); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Producto *</label>
                        <select class="form-control" name="id_insumo" id="edit_pedido_insumo" required>
                            <option value="">Seleccionar producto</option>
                            <?php
                            $insumos->data_seek(0);
                            while ($insumo = $insumos->fetch_assoc()):
                            ?>
                                <option value="<?php echo (int) $insumo['id_insumos']; ?>">
                                    <?php echo htmlspecialchars($insumo['nombre']); ?>
                                    (<?php echo htmlspecialchars($insumo['unidad_medida'] ?: 'sin unidad'); ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Cantidad *</label>
                        <input type="number" step="0.01" min="0.01" class="form-control" name="cantidad" id="edit_pedido_cantidad" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Observación</label>
                        <textarea class="form-control" name="observaciones" id="edit_pedido_observaciones" rows="3" maxlength="1000"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-floppy-disk"></i> Guardar cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Crear Pedido -->
<div class="modal fade admin-premium-modal admin-purchase-modal admin-purchase-modal--order" id="modalCrearPedido" tabindex="-1" aria-labelledby="modalCrearPedidoTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="formCrearPedido">
                <div class="modal-header">
                    <span class="admin-premium-modal__icon admin-purchase-modal__icon">
                        <i class="fas fa-cart-flatbed"></i>
                    </span>
                    <div class="admin-premium-modal__heading">
                        <span class="farmer-kicker">Abastecimiento inteligente</span>
                        <h5 class="modal-title" id="modalCrearPedidoTitle">Nuevo pedido</h5>
                        <p>Organiza la compra y deja lista la recepción de insumos.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="admin-purchase-modal__notice">
                        <span><i class="fas fa-boxes-stacked"></i></span>
                        <div>
                            <strong>Pedido con trazabilidad</strong>
                            <p>Se registrará como pendiente hasta que bodega confirme la recepción.</p>
                        </div>
                    </div>
                    <div class="admin-purchase-modal__grid">
                        <div class="admin-purchase-field">
                            <label for="crear_pedido_proveedor">Proveedor <b>*</b></label>
                            <div class="admin-purchase-field__control">
                                <i class="fas fa-truck-field"></i>
                                <select class="form-select" id="crear_pedido_proveedor" name="id_proveedor" data-purchase-select data-select-icon="fa-truck-field" data-option-icon="fa-building" data-select-label="Seleccionar proveedor" required>
                                    <option value="">Seleccionar proveedor</option>
                            <?php
                            $proveedores->data_seek(0);
                            while ($prov = $proveedores->fetch_assoc()):
                            ?>
                            <option value="<?php echo $prov['id_proveedor']; ?>">
                                <?php echo htmlspecialchars($prov['Nombre']); ?>
                            </option>
                            <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        <div class="admin-purchase-field">
                            <label for="crear_pedido_usuario">Responsable <b>*</b></label>
                            <div class="admin-purchase-field__control">
                                <i class="fas fa-user-check"></i>
                                <select class="form-select" id="crear_pedido_usuario" name="id_usuario" data-purchase-select data-select-icon="fa-user-check" data-option-icon="fa-user-tie" data-select-label="Seleccionar responsable" required>
                                    <option value="">Seleccionar responsable</option>
                            <?php
                            $usuarios->data_seek(0);
                            while ($user = $usuarios->fetch_assoc()):
                            ?>
                            <option value="<?php echo $user['id_usuario']; ?>">
                                <?php echo htmlspecialchars($user['nombre']); ?>
                            </option>
                            <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        <div class="admin-purchase-field">
                            <label for="crear_pedido_producto">Producto <b>*</b></label>
                            <div class="admin-purchase-field__control">
                                <i class="fas fa-seedling"></i>
                                <select class="form-select" id="crear_pedido_producto" name="id_insumo" data-purchase-select data-select-icon="fa-seedling" data-option-icon="fa-box-open" data-select-label="Seleccionar producto" required>
                                    <option value="">Seleccionar producto</option>
                            <?php
                            $insumos->data_seek(0);
                            while ($insumo = $insumos->fetch_assoc()):
                            ?>
                                <option value="<?php echo (int) $insumo['id_insumos']; ?>">
                                    <?php echo htmlspecialchars($insumo['nombre']); ?>
                                    (<?php echo htmlspecialchars($insumo['unidad_medida'] ?: 'sin unidad'); ?>,
                                    stock: <?php echo htmlspecialchars($insumo['cantidad']); ?>)
                                </option>
                            <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        <div class="admin-purchase-field">
                            <label for="crear_pedido_cantidad">Cantidad <b>*</b></label>
                            <div class="admin-purchase-field__control">
                                <i class="fas fa-arrow-up-9-1"></i>
                                <input type="number" step="0.01" min="0.01" class="form-control" id="crear_pedido_cantidad" name="cantidad" placeholder="0.00" required>
                            </div>
                        </div>
                        <div class="admin-purchase-field admin-purchase-field--wide">
                            <label for="crear_pedido_observaciones">Observación <small>Opcional</small></label>
                            <div class="admin-purchase-field__control admin-purchase-field__control--textarea">
                                <i class="fas fa-clipboard-list"></i>
                                <textarea class="form-control" id="crear_pedido_observaciones" name="observaciones" rows="3" maxlength="1000" placeholder="Detalle, fecha esperada o instrucciones para la recepción"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <span class="admin-premium-modal__security">
                        <i class="fas fa-clock-rotate-left"></i> Registro auditable
                    </span>
                    <button type="button" class="btn admin-purchase-modal__cancel" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success admin-purchase-modal__submit">
                        <i class="fas fa-paper-plane"></i> Crear pedido
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php $conn->close(); ?>
