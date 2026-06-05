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
            $nombre_producto = trim($_POST['nombre_producto'] ?? '');
            $cantidad = floatval($_POST['cantidad']);
            $unidad_medida = trim($_POST['unidad_medida'] ?? '');
            
            if (empty($nombre_producto) || $cantidad <= 0 || $id_proveedor <= 0 || empty($id_usuario)) {
                echo json_encode(['success' => false, 'message' => 'Todos los campos son requeridos y deben ser válidos']);
                exit;
            }
            
            $stmt = $conn->prepare("INSERT INTO pedidos (id_proveedor, id_usuario, nombre_producto, cantidad, unidad_medida, fecha) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("issds", $id_proveedor, $id_usuario, $nombre_producto, $cantidad, $unidad_medida);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Pedido creado exitosamente', 'id' => $conn->insert_id]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al crear pedido: ' . $stmt->error]);
            }
            exit;
            
        case 'eliminar_pedido':
            $id = intval($_POST['id_pedido']);
            
            // Verificar si está asociado a una factura
            $check = $conn->prepare("SELECT COUNT(*) as count FROM factura WHERE id_pedidos = ?");
            $check->bind_param("i", $id);
            $check->execute();
            $count = $check->get_result()->fetch_assoc()['count'];
            
            if ($count > 0) {
                echo json_encode(['success' => false, 'message' => "No se puede eliminar: tiene factura(s) asociada(s)"]);
                exit;
            }
            
            $stmt = $conn->prepare("DELETE FROM pedidos WHERE id_pedidos=?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Pedido eliminado exitosamente']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al eliminar: ' . $stmt->error]);
            }
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

// Estadísticas
$stats = $conn->query("
    SELECT 
        (SELECT COUNT(*) FROM proveedor) as total_proveedores,
        (SELECT COUNT(*) FROM pedidos) as total_pedidos,
        (SELECT COUNT(*) FROM pedidos WHERE DATE(fecha) = CURDATE()) as pedidos_hoy
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
                                <i class="fas fa-shopping-cart fa-2x mb-2"></i>
                                <h3><?php echo $stats['total_pedidos'] ?: 0; ?></h3>
                                <p class="mb-0">Total Pedidos</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-warning text-white">
                            <div class="card-body text-center">
                                <i class="fas fa-calendar-day fa-2x mb-2"></i>
                                <h3><?php echo $stats['pedidos_hoy'] ?: 0; ?></h3>
                                <p class="mb-0">Pedidos Hoy</p>
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
                            <i class="fas fa-shopping-cart"></i> Pedidos
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
                                                    <i class="fas fa-edit"></i>
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
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($pedido = $pedidos->fetch_assoc()): ?>
                                        <?php
                                        // Verificar si tiene factura
                                        $facturado = (int) db_value(
                                            $conn,
                                            "SELECT COUNT(*) FROM factura WHERE id_pedidos = ?",
                                            "i",
                                            [(int) $pedido['id_pedidos']],
                                            0
                                        ) > 0;
                                        ?>
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
                                                <span class="badge bg-<?php echo $facturado ? 'success' : 'warning'; ?>">
                                                    <?php echo $facturado ? 'Facturado' : 'Pendiente'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-danger <?php echo $facturado ? 'disabled' : ''; ?>" 
                                                        onclick="eliminarPedido(<?php echo $pedido['id_pedidos']; ?>)"
                                                        <?php echo $facturado ? 'title="No se puede eliminar: tiene factura asociada"' : ''; ?>>
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
                                <i class="fas fa-shopping-cart fa-3x mb-3"></i>
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
<div class="modal fade" id="modalCrearProveedor" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="formCrearProveedor">
        <div class="modal-header">
          <h5 class="modal-title">Crear Proveedor</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="crear_nombre" class="form-label">Nombre</label>
            <input type="text" class="form-control" id="crear_nombre" name="nombre" required>
          </div>

          <div class="mb-3">
            <label for="crear_ruc" class="form-label">RUC / Cédula</label>
            <input type="text" class="form-control" id="crear_ruc" name="ruc_cedula" required>
          </div>

          <div class="mb-3">
            <label for="crear_telefono" class="form-label">Teléfono</label>
            <input type="text" class="form-control" id="crear_telefono" name="telefono" required>
          </div>

          <div class="mb-3">
            <label for="crear_email" class="form-label">Email</label>
            <input type="email" class="form-control" id="crear_email" name="email" required>
          </div>

          <div class="mb-3">
            <label for="crear_direccion" class="form-label">Dirección</label>
            <input type="text" class="form-control" id="crear_direccion" name="direccion">
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Guardar</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
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

<!-- Modal Crear Pedido -->
<div class="modal fade" id="modalCrearPedido" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nuevo Pedido</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formCrearPedido">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Proveedor *</label>
                        <select class="form-control" name="id_proveedor" required>
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
                        <select class="form-control" name="id_usuario" required>
                            <option value="">Seleccionar usuario</option>
                            <?php while ($user = $usuarios->fetch_assoc()): ?>
                            <option value="<?php echo $user['id_usuario']; ?>">
                                <?php echo htmlspecialchars($user['nombre']); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Producto *</label>
                        <input type="text" class="form-control" name="nombre_producto" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Cantidad *</label>
                        <input type="number" step="0.01" min="0.01" class="form-control" name="cantidad" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Unidad de Medida *</label>
                        <select class="form-control" name="unidad_medida" required>
                            <option value="">Seleccionar unidad</option>
                            <option value="kg">Kilogramos</option>
                            <option value="lb">Libras</option>
                            <option value="ton">Toneladas</option>
                            <option value="L">Litros</option>
                            <option value="gal">Galones</option>
                            <option value="unid">Unidades</option>
                            <option value="caja">Cajas</option>
                            <option value="saco">Sacos</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Crear Pedido</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php $conn->close(); ?>
