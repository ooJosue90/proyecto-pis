<?php
require_once 'conexion.php';
require_auth('Administrador');

// Si es una petición AJAX, procesarla inmediatamente
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');

    $action = $_POST['action'];

    if ($action == 'crear') {
        $nombre = trim($_POST['nombre'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $contrasena = trim($_POST['contrasena'] ?? '');
        $rol = trim($_POST['rol'] ?? '');
        $cedula = trim($_POST['cedula'] ?? '');

        // Validaciones
        if (empty($nombre) || empty($email) || empty($contrasena) || empty($rol)) {
            echo json_encode(['success' => false, 'message' => 'Todos los campos marcados con * son requeridos']);
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'El email no es válido']);
            exit;
        }

        if (strlen($contrasena) < 6) {
            echo json_encode(['success' => false, 'message' => 'La contraseña debe tener al menos 6 caracteres']);
            exit;
        }

        // Verificar email único
        $check_email = $conn->prepare("SELECT id_usuario FROM usuarios WHERE email = ?");
        $check_email->bind_param("s", $email);
        $check_email->execute();
        if ($check_email->get_result()->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'El email ya está registrado']);
            exit;
        }

        // Verificar cédula única si se proporciona
        if (!empty($cedula)) {
            $check_cedula = $conn->prepare("SELECT id_usuario FROM usuarios WHERE id_usuario = ?");
            $check_cedula->bind_param("s", $cedula);
            $check_cedula->execute();
            if ($check_cedula->get_result()->num_rows > 0) {
                echo json_encode(['success' => false, 'message' => 'La cédula ya está registrada']);
                exit;
            }
        }

        // Hashear la contraseña por seguridad
        $contrasena_final = password_hash($contrasena, PASSWORD_DEFAULT);

        // Generar ID único si no hay cédula
        if (empty($cedula)) {
            $cedula = 'U' . date('Ymd') . strtoupper(substr(uniqid(), -6));
        }

        $sql = "INSERT INTO usuarios (id_usuario, nombre, email, contrasena, rol, fecha_registro) VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssss", $cedula, $nombre, $email, $contrasena_final, $rol);
        $user_id = $cedula;

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Usuario creado exitosamente', 'user_id' => $user_id]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al crear usuario: ' . $stmt->error]);
        }
        exit;
    }

    if ($action == 'editar') {
        $id = trim($_POST['id_usuario']);
        $nombre = trim($_POST['nombre'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $rol = trim($_POST['rol'] ?? '');
        $nueva_contrasena = trim($_POST['nueva_contrasena'] ?? ''); // ¡CORRECCIÓN AQUÍ!

        if (empty($nombre) || empty($email) || empty($rol)) {
            echo json_encode(['success' => false, 'message' => 'Todos los campos son requeridos']);
            exit;
        }

        // Validar nueva contraseña si se proporciona
        if (!empty($nueva_contrasena) && strlen($nueva_contrasena) < 6) {
            echo json_encode(['success' => false, 'message' => 'La nueva contraseña debe tener al menos 6 caracteres']);
            exit;
        }

        // Verificar email único (excluyendo el usuario actual)
        $check_email = $conn->prepare("SELECT id_usuario FROM usuarios WHERE email = ? AND id_usuario != ?");
        $check_email->bind_param("ss", $email, $id);
        $check_email->execute();
        if ($check_email->get_result()->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'El email ya está registrado en otro usuario']);
            exit;
        }

        // ¡CORRECCIÓN PRINCIPAL: Actualizar con o sin contraseña!
        if (!empty($nueva_contrasena)) {
            $hash = password_hash($nueva_contrasena, PASSWORD_DEFAULT);
            // Si hay nueva contraseña, incluirla en la actualización
            $stmt = $conn->prepare("UPDATE usuarios SET nombre=?, email=?, rol=?, contrasena=? WHERE id_usuario=?");
            $stmt->bind_param("sssss", $nombre, $email, $rol, $hash, $id);
        } else {
            // Si no hay nueva contraseña, no actualizar el campo contraseña
            $stmt = $conn->prepare("UPDATE usuarios SET nombre=?, email=?, rol=? WHERE id_usuario=?");
            $stmt->bind_param("ssss", $nombre, $email, $rol, $id);
        }

        if ($stmt->execute()) {
            $message = 'Usuario actualizado exitosamente';
            if (!empty($nueva_contrasena)) {
                $message .= ' (nueva contraseña guardada)';
            }
            echo json_encode(['success' => true, 'message' => $message]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar: ' . $stmt->error]);
        }
        exit;
    }

    if ($action == 'eliminar') {
        $id = trim($_POST['id_usuario']);

        if ($id == '1') {
            echo json_encode(['success' => false, 'message' => 'No se puede eliminar el administrador principal']);
            exit;
        }

        $stmt = $conn->prepare("DELETE FROM usuarios WHERE id_usuario=?");
        $stmt->bind_param("s", $id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Usuario eliminado exitosamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al eliminar: ' . $stmt->error]);
        }
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Acción no reconocida']);
    exit;
}

$usuarios = $conn->query("SELECT * FROM usuarios ORDER BY id_usuario");
?>

<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4><i class="fas fa-users"></i> Gestión de Usuarios</h4>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrearUsuario" data-app-no-ripple>
                    <i class="fas fa-plus"></i> Nuevo Usuario
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>ID/Cédula</th>
                                <th>Nombre</th>
                                <th>Email</th>
                                <th>Contraseña</th>
                                <th>Rol</th>
                                <th>Fecha Registro</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($usuario = $usuarios->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($usuario['id_usuario']) ?></td>
                                    <td><?= htmlspecialchars($usuario['nombre']) ?></td>
                                    <td><?= htmlspecialchars($usuario['email']) ?></td>
                                    <td>
                                        <span class="badge bg-secondary">********</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $usuario['rol'] == 'Administrador' ? 'primary' : ($usuario['rol'] == 'Agricultor' ? 'success' : 'warning') ?>">
                                            <?= $usuario['rol'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        if (isset($usuario['fecha_registro'])) {
                                            echo date('d/m/Y', strtotime($usuario['fecha_registro']));
                                        } else {
                                            echo 'N/A';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" onclick="editarUsuario('<?= htmlspecialchars($usuario['id_usuario']) ?>','<?= htmlspecialchars($usuario['nombre']) ?>','<?= htmlspecialchars($usuario['email']) ?>','<?= $usuario['rol'] ?>')">
                                            <i class="fas fa-user-pen"></i>
                                        </button>
                                        <?php if ($usuario['id_usuario'] != '1'): ?>
                                            <button
                                                class="btn btn-sm btn-outline-danger"
                                                type="button"
                                                data-user-id="<?= htmlspecialchars($usuario['id_usuario'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-user-name="<?= htmlspecialchars($usuario['nombre'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-user-email="<?= htmlspecialchars($usuario['email'], ENT_QUOTES, 'UTF-8') ?>"
                                                onclick="eliminarUsuario(this)"
                                                aria-label="Eliminar usuario <?= htmlspecialchars($usuario['nombre'], ENT_QUOTES, 'UTF-8') ?>"
                                            >
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
