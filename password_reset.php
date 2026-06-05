<?php
require_once 'conexion.php';

try {

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $email = trim($_POST['email']);

        if (empty($email)) {
            throw new Exception("El correo electrónico es obligatorio");
        }

        // Verificar si el email existe en la base de datos
        $sql = "SELECT * FROM usuarios WHERE email = ?";
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            throw new Exception("Error preparando consulta: " . $conn->error);
        }

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $user_name = $user['nombre'];

            // Insertar notificación para el administrador
            $mensaje_notif = "El usuario $user_name ($email) ha solicitado restablecer su contraseña.";
            $stmt_notif = $conn->prepare("INSERT INTO notificaciones (mensaje) VALUES (?)");
            $stmt_notif->bind_param("s", $mensaje_notif);
            $stmt_notif->execute();
            $stmt_notif->close();

            // Obtener email del administrador
            $sql_admin = "SELECT email FROM usuarios WHERE rol = 'Administrador' LIMIT 1";
            $stmt_admin = $conn->prepare($sql_admin);
            $stmt_admin->execute();
            $result_admin = $stmt_admin->get_result();

            if ($result_admin->num_rows > 0) {
                $admin = $result_admin->fetch_assoc();
                $admin_email = $admin['email'];

                // Enviar notificación al administrador
                $subject = "Solicitud de restablecimiento de contraseña";
                $message = "El usuario $user_name ($email) ha solicitado restablecer su contraseña.";
                $headers = "From: noreply@sembriexport.com";

                // Nota: En un entorno local, mail() puede no funcionar sin configuración SMTP
                mail($admin_email, $subject, $message, $headers);
            }

            $stmt_admin->close();

            redirect(
                'login.html?type=success&notification='
                . rawurlencode('Se ha notificado al administrador sobre su solicitud de restablecimiento de contraseña.')
            );
        } else {
            throw new Exception("El correo electrónico no está registrado");
        }

        $stmt->close();
    } else {
        throw new Exception("No se recibieron datos");
    }
} catch (Exception $e) {
    redirect('password.html?type=danger&notification=' . rawurlencode($e->getMessage()));
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
