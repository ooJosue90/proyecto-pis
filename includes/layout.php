<?php

require_once __DIR__ . '/helpers.php';

function render_head(string $title, array $extraStyles = [], array $extraScripts = []): void
{
    $appName = app_config('app.name', 'SembriExport');
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title); ?> | <?= e($appName); ?></title>
    <script>
        (function () {
            try {
                var savedTheme = localStorage.getItem('theme') || localStorage.getItem('appTheme');
                var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                var preference = ['light', 'dark', 'night', 'auto'].indexOf(savedTheme) >= 0 ? savedTheme : 'auto';
                document.documentElement.dataset.themePreference = preference;
                document.documentElement.dataset.theme = preference === 'auto'
                    ? (prefersDark ? 'dark' : 'light')
                    : preference;
            } catch (error) {
                document.documentElement.dataset.theme = 'light';
                document.documentElement.dataset.themePreference = 'auto';
            }
        })();
    </script>
    <link rel="icon" type="image/x-icon" href="assets/mango.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/dashboard.css?v=<?= filemtime(__DIR__ . '/../css/dashboard.css'); ?>" rel="stylesheet">
    <link href="asistente/asistente_virtual.css?v=<?= filemtime(__DIR__ . '/../asistente/asistente_virtual.css'); ?>" rel="stylesheet">
    <?php foreach ($extraStyles as $href): ?>
        <link href="<?= e($href); ?>" rel="stylesheet">
    <?php endforeach; ?>
    <link href="css/theme.css?v=<?= filemtime(__DIR__ . '/../css/theme.css'); ?>" rel="stylesheet">
    <?php foreach ($extraScripts as $src): ?>
        <script src="<?= e($src); ?>"></script>
    <?php endforeach; ?>
    <script src="js/app-ui.js?v=<?= filemtime(__DIR__ . '/../js/app-ui.js'); ?>" defer></script>
</head>
<?php
}

function app_user_initials(): string
{
    $name = trim(current_user_name());
    if ($name === '') {
        return 'US';
    }

    $parts = preg_split('/\s+/', $name);
    $first = substr($parts[0] ?? 'U', 0, 1);
    $second = substr($parts[1] ?? ($parts[0] ?? 'S'), 0, 1);

    return strtoupper($first . $second);
}

function app_nav_items(): array
{
    $role = $_SESSION['rol'] ?? '';

    if ($role === 'Administrador') {
        return [
            ['label' => 'Dashboard', 'icon' => 'fas fa-chart-pie', 'tab' => '#dashboard', 'active' => true],
            ['label' => 'Usuarios', 'icon' => 'fas fa-users', 'tab' => '#usuarios'],
            ['label' => 'Solicitudes', 'icon' => 'fas fa-clipboard-list', 'tab' => '#solicitudes'],
            ['label' => 'Movimientos', 'icon' => 'fas fa-right-left', 'tab' => '#movimientos'],
            ['label' => 'Facturas', 'icon' => 'fas fa-file-invoice-dollar', 'tab' => '#facturas'],
            ['label' => 'Cultivos', 'icon' => 'fas fa-seedling', 'tab' => '#cultivos'],
            ['label' => 'Reportes', 'icon' => 'fas fa-chart-column', 'tab' => '#reportes'],
            ['label' => 'Proveedores', 'icon' => 'fas fa-truck', 'tab' => '#pedidos-proveedores'],
        ];
    }

    if ($role === 'Agricultor') {
        return [
            ['label' => 'Dashboard', 'icon' => 'fas fa-chart-pie', 'href' => 'agricultor.php'],
            ['label' => 'Calculadora', 'icon' => 'fas fa-calculator', 'href' => 'calcular_insumos.php'],
        ];
    }

    if ($role === 'Bodeguero') {
        return [
            ['label' => 'Bodega', 'icon' => 'fas fa-warehouse', 'href' => 'bodeguero.php'],
            ['label' => 'Facturas', 'icon' => 'fas fa-file-invoice-dollar', 'href' => 'bodeguero_facturas.php'],
            ['label' => 'Solicitudes', 'icon' => 'fas fa-clipboard-check', 'href' => 'imprimir_solicitudes.php'],
        ];
    }

    return [];
}

function render_app_nav(string $icon, string $label, array $actions = []): void
{
    $role = $_SESSION['rol'] ?? 'Invitado';
    $items = app_nav_items();
    $initials = app_user_initials();
    ?>
<aside class="app-sidebar" aria-label="Navegación principal">
    <div class="app-sidebar-header">
        <span class="app-brand-mark" aria-hidden="true"></span>
        <div class="app-brand-copy">
            <p class="app-brand-title"><?= e(app_config('app.name', 'SembriExport')); ?></p>
            <p class="app-brand-subtitle"><?= e($role); ?></p>
        </div>
    </div>

    <nav class="app-sidebar-nav">
        <div class="app-nav-section">Principal</div>
        <?php foreach ($items as $item): ?>
            <?php if (!empty($item['tab'])): ?>
                <button class="app-sidebar-link <?= !empty($item['active']) ? 'active' : ''; ?>" type="button" data-app-tab="<?= e($item['tab']); ?>">
                    <i class="<?= e($item['icon']); ?>"></i>
                    <span><?= e($item['label']); ?></span>
                </button>
            <?php else: ?>
                <a class="app-sidebar-link" href="<?= e($item['href']); ?>">
                    <i class="<?= e($item['icon']); ?>"></i>
                    <span><?= e($item['label']); ?></span>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>
</aside>

<div class="app-mobile-overlay" data-app-mobile-close></div>

<header class="app-topbar">
    <div class="app-topbar-left">
        <button type="button" class="app-icon-button" data-app-sidebar-toggle aria-label="Alternar menú">
            <i class="fas fa-bars"></i>
        </button>
        <div>
            <span class="app-page-kicker">Panel de control</span>
            <span class="app-page-title"><?= e($label); ?></span>
        </div>
    </div>

    <div class="app-topbar-actions">
        <div class="app-breadcrumb">
            <span><?= e(app_config('app.name', 'SembriExport')); ?></span>
            <i class="fas fa-chevron-right"></i>
            <span><?= e($role); ?></span>
        </div>
        <div class="app-appearance" data-app-appearance>
            <button type="button"
                    class="app-appearance-toggle"
                    data-app-theme-toggle
                    aria-label="Cambiar apariencia"
                    aria-haspopup="true"
                    aria-expanded="false">
                <i class="fas fa-circle-half-stroke" data-app-theme-icon aria-hidden="true"></i>
                <span class="app-appearance-label">Apariencia</span>
                <span class="app-appearance-current" data-app-theme-label>Automático</span>
                <i class="fas fa-chevron-down app-appearance-chevron" aria-hidden="true"></i>
            </button>
            <div class="app-appearance-menu" data-app-theme-menu role="menu" aria-label="Seleccionar apariencia">
                <span class="app-appearance-menu-title">Apariencia</span>
                <button type="button" class="app-theme-option" data-theme-value="light" role="menuitemradio" aria-checked="false">
                    <span class="app-theme-option-icon app-theme-option-icon--light"><i class="fas fa-sun"></i></span>
                    <span><strong>Claro</strong><small>Diseño actual y luminoso</small></span>
                    <i class="fas fa-check app-theme-check"></i>
                </button>
                <button type="button" class="app-theme-option" data-theme-value="dark" role="menuitemradio" aria-checked="false">
                    <span class="app-theme-option-icon app-theme-option-icon--dark"><i class="fas fa-moon"></i></span>
                    <span><strong>Oscuro</strong><small>Grises oscuros y contraste suave</small></span>
                    <i class="fas fa-check app-theme-check"></i>
                </button>
                <button type="button" class="app-theme-option" data-theme-value="night" role="menuitemradio" aria-checked="false">
                    <span class="app-theme-option-icon app-theme-option-icon--night"><i class="fas fa-star"></i></span>
                    <span><strong>Noche</strong><small>Negro profundo y verde agrícola</small></span>
                    <i class="fas fa-check app-theme-check"></i>
                </button>
                <button type="button" class="app-theme-option" data-theme-value="auto" role="menuitemradio" aria-checked="false">
                    <span class="app-theme-option-icon app-theme-option-icon--auto"><i class="fas fa-circle-half-stroke"></i></span>
                    <span><strong>Automático</strong><small>Usar la apariencia del sistema</small></span>
                    <i class="fas fa-check app-theme-check"></i>
                </button>
            </div>
        </div>
        <div class="app-user-chip">
            <span class="app-avatar"><?= e($initials); ?></span>
            <span><?= e(current_user_name()); ?></span>
        </div>
        <a class="app-icon-button" href="logout.php" aria-label="Cerrar sesión">
            <i class="fas fa-arrow-right-from-bracket"></i>
        </a>
    </div>
</header>
<?php
}

function render_flash_messages(): void
{
    $messages = [
        'mensaje' => ['success', 'fa-check', 'Operación completada'],
        'error' => ['danger', 'fa-exclamation', 'No se pudo completar'],
        'error_entrega' => ['warning', 'fa-exclamation', 'Requiere atención'],
    ];

    $notifications = [];
    foreach ($messages as $key => [$type, $icon, $title]) {
        $message = flash($key);
        if ($message === null) {
            continue;
        }

        $notifications[] = compact('type', 'icon', 'title', 'message');
    }

    if (!$notifications) {
        return;
    }
    ?>
    <div class="app-notification-stack" data-app-notification-stack aria-live="polite" aria-atomic="true">
        <?php foreach ($notifications as $notification): ?>
            <div class="app-notification app-notification--<?= e($notification['type']); ?>"
                 data-app-notification
                 data-duration="4500"
                 role="<?= $notification['type'] === 'danger' ? 'alert' : 'status'; ?>">
                <span class="app-notification__icon" aria-hidden="true">
                    <i class="fas <?= e($notification['icon']); ?>"></i>
                </span>
                <span class="app-notification__content">
                    <strong class="app-notification__title"><?= e($notification['title']); ?></strong>
                    <span class="app-notification__message"><?= e($notification['message']); ?></span>
                </span>
                <button class="app-notification__close" type="button" data-app-notification-close aria-label="Cerrar notificación">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
}

function render_ada_chat(): void
{
    if (empty($_SESSION['rol'])) {
        return;
    }

    $role = $_SESSION['rol'];
    $userName = current_user_name();
    $capabilities = [
        ['icon' => '🌱', 'label' => 'Cultivos'],
        ['icon' => '📍', 'label' => 'Lotes'],
        ['icon' => '📋', 'label' => 'Solicitudes'],
        ['icon' => '🐛', 'label' => 'Plagas'],
    ];
    $quickActions = [
        ['label' => 'Mi monitoreo', 'prompt' => 'Muéstrame todos los datos de monitoreo de mis lotes'],
        ['label' => 'Mis solicitudes', 'prompt' => 'Muéstrame mis solicitudes registradas'],
        ['label' => 'Mis lotes', 'prompt' => 'Muéstrame mis lotes registrados'],
        ['label' => 'Actividades pendientes', 'prompt' => 'Muéstrame mis actividades agrícolas pendientes'],
    ];

    if ($role === 'Administrador') {
        $capabilities = [
            ['icon' => '👥', 'label' => 'Usuarios'],
            ['icon' => '📦', 'label' => 'Inventario'],
            ['icon' => '📄', 'label' => 'Facturas'],
            ['icon' => '📊', 'label' => 'Reportes'],
        ];
        $quickActions = [
            ['label' => 'Monitoreo general', 'prompt' => 'Muéstrame todos los datos de monitoreo de los lotes'],
            ['label' => 'Usuarios', 'prompt' => 'Lista los usuarios registrados en el sistema'],
            ['label' => 'Actividad reciente', 'prompt' => 'Muéstrame la actividad agrícola reciente y las tareas pendientes'],
            ['label' => 'Resumen general', 'prompt' => 'Muéstrame el resumen general del sistema'],
        ];
    } elseif ($role === 'Bodeguero') {
        $capabilities = [
            ['icon' => '📦', 'label' => 'Inventario'],
            ['icon' => '🧪', 'label' => 'Insumos'],
            ['icon' => '📄', 'label' => 'Facturas'],
            ['icon' => '📋', 'label' => 'Solicitudes'],
        ];
        $quickActions = [
            ['label' => 'Ver inventario', 'prompt' => 'Muéstrame el inventario de insumos'],
            ['label' => 'Insumos bajos', 'prompt' => 'Muéstrame los insumos con stock bajo en inventario'],
            ['label' => 'Facturas', 'prompt' => 'Muéstrame las facturas registradas'],
            ['label' => 'Solicitudes pendientes', 'prompt' => 'Muéstrame las solicitudes pendientes por atender'],
        ];
    }
    ?>
    <div class="ada-chat" data-ada-chat data-endpoint="asistente/asistente_virtual.php">
        <button class="ada-chat__toggle" type="button" data-ada-toggle aria-label="Abrir ADA" aria-expanded="false">
            <img src="assets/img/ada-avatar.jpg" alt="ADA">
        </button>

        <section class="ada-chat__window" aria-label="Chat de ADA">
            <header class="ada-chat__header">
                <div class="ada-chat__brand">
                    <span class="ada-chat__avatar">
                        <img src="assets/img/ada-avatar.jpg" alt="ADA">
                    </span>
                    <div>
                        <div class="ada-chat__title-row">
                            <strong>ADA</strong>
                            <span class="ada-chat__status"><i></i> En línea</span>
                        </div>
                        <span>Especialista SEMBRIEXPORT</span>
                        <small>Inventario • Facturación • Insumos</small>
                    </div>
                </div>
                <button class="ada-chat__close" type="button" data-ada-close aria-label="Cerrar ADA">
                    <i class="fas fa-xmark"></i>
                </button>
            </header>

            <div class="ada-chat__messages" data-ada-messages>
                <div class="ada-welcome-card">
                    <h3>👋 Bienvenido <?= e($userName); ?></h3>
                    <p>Puedo ayudarte con:</p>
                    <div class="ada-capability-grid">
                        <?php foreach ($capabilities as $capability): ?>
                            <span><?= e($capability['icon']); ?> <?= e($capability['label']); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="ada-quick-actions" aria-label="Accesos rápidos de ADA">
                    <?php foreach ($quickActions as $action): ?>
                        <button type="button" data-ada-quick="<?= e($action['prompt']); ?>"><?= e($action['label']); ?></button>
                    <?php endforeach; ?>
                </div>
            </div>

            <form class="ada-chat__form" data-ada-form>
                <input class="ada-chat__input" type="text" data-ada-input placeholder="Ej: ¿Qué insumos tienen stock bajo?" autocomplete="off">
                <button class="ada-chat__send" type="submit" data-ada-send data-skip-loading="1" aria-label="Enviar pregunta">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </form>
        </section>
    </div>
    <script src="asistente/asistente_virtual.js?v=<?= filemtime(__DIR__ . '/../asistente/asistente_virtual.js'); ?>"></script>
    <?php
}

function render_scripts(array $scripts = []): void
{
    foreach ($scripts as $src) {
        echo '<script src="' . e($src) . '"></script>' . PHP_EOL;
    }
    echo '<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>' . PHP_EOL;
}
