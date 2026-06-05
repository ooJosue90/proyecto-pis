<?php
require_once 'conexion.php';

try {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($email) || empty($password)) {
            throw new Exception("Email y contraseña son obligatorios");
        }

        $sql = "SELECT * FROM usuarios WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['contrasena'])) {
                login_user($user);
                redirect(dashboard_for_role($user['rol']));
            }

            if (isset($user['contrasena']) && $password === $user['contrasena'] && !password_verify($password, $user['contrasena'])) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt_update = $conn->prepare("UPDATE usuarios SET contrasena = ? WHERE id_usuario = ?");
                $stmt_update->bind_param("ss", $hash, $user['id_usuario']);
                $stmt_update->execute();
                $stmt_update->close();

                login_user(array_merge($user, ['contrasena' => $hash]));
                redirect(dashboard_for_role($user['rol']));
            }

            throw new Exception("Credenciales inválidas");
        } else {
            throw new Exception("Usuario no encontrado");
        }

        $stmt->close();
    } else {
        throw new Exception("No se recibieron datos de login");
    }
} catch (Exception $e) {
    redirect('login.html?type=danger&notification=' . rawurlencode($e->getMessage()));
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
