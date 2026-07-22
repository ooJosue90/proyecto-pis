<?php

declare(strict_types=1);

use App\Core\Url;
use App\Shared\Helpers\Html;
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Recuperar contraseña - SEMBRIEXPORT</title>
    <link rel="icon" type="image/x-icon" href="<?= Html::escape(Url::root('assets/mango.ico')) ?>">
    <link href="<?= Html::escape(Url::root('assets/vendor/bootstrap/bootstrap.min.css')) ?>" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link href="<?= Html::escape(Url::root('css/material-icons.css?v=20260713-google-icons')) ?>" rel="stylesheet">
    <link href="<?= Html::escape(Url::root('css/auth.css?v=20260722-auth-links')) ?>" rel="stylesheet">
</head>
<body class="auth-page auth-login-page">
<main class="auth-shell auth-login-shell">
    <section class="auth-aside auth-login-aside">
        <a class="auth-login-brand" href="<?= Html::escape(Url::root('index.html')) ?>"><span class="public-brand-mark"><i class="fas fa-seedling"></i></span><span>SEMBRIEXPORT</span></a>
        <div class="auth-login-story"><span class="auth-login-eyebrow"><i class="fas fa-key"></i> Recuperación segura</span><h1>Recupera el acceso sin fricción.</h1><p>Solicita las instrucciones de recuperación y vuelve al flujo administrativo del sistema.</p></div>
        <div class="auth-login-metrics" aria-label="Garantías de recuperación"><div><strong>Seguro</strong><span>Solicitud verificada</span></div><div><strong>Rápido</strong><span>Notificación al administrador</span></div><div><strong>Privado</strong><span>Acceso protegido</span></div></div>
    </section>
    <section class="auth-panel auth-login-panel">
        <div class="auth-login-panel-top">
            <a class="auth-login-home" href="<?= Html::escape(Url::route('/login')) ?>"><i class="fas fa-arrow-left"></i> Volver al inicio de sesión</a>
            <span class="auth-login-security"><i class="fas fa-shield-alt"></i> Conexión segura</span>
        </div>
        <div class="auth-login-card">
            <div class="auth-login-heading"><span class="auth-kicker"><i class="fas fa-shield-alt"></i> Recuperación</span><h1 class="auth-title">Recuperar contraseña</h1><p class="auth-subtitle">Escribe tu correo electrónico registrado para notificar al administrador.</p></div>
            <?php if ($success): ?><div class="alert alert-success" role="status"><?= Html::escape($success) ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-danger" role="alert"><?= Html::escape($error) ?></div><?php endif; ?>
            <form class="auth-login-form" action="<?= Html::escape(Url::route('/password/forgot')) ?>" method="post">
                <input type="hidden" name="_token" value="<?= Html::escape($csrfToken) ?>">
                <div class="auth-field"><label for="inputEmail" class="form-label">Correo electrónico</label><div class="auth-input-wrap"><i class="fas fa-envelope" aria-hidden="true"></i><input type="email" class="form-control" id="inputEmail" name="email" placeholder="usuario@sembriexport.com" autocomplete="email" maxlength="100" required autofocus></div></div>
                <button type="submit" class="btn btn-primary auth-login-submit"><span>Enviar solicitud</span><i class="fas fa-paper-plane"></i></button>
                <p class="auth-login-privacy"><i class="fas fa-shield-halved"></i> Tu solicitud se procesa de forma privada y segura.</p>
            </form>
        </div>
        <p class="auth-login-footer">&copy; 2026 SEMBRIEXPORT. Plataforma de gestión agrícola.</p>
    </section>
</main>
<script src="<?= Html::escape(Url::root('js/app-ui.js?v=20260605-notifications')) ?>"></script>
<script src="<?= Html::escape(Url::root('assets/vendor/bootstrap/bootstrap.bundle.min.js')) ?>"></script>
</body>
</html>
