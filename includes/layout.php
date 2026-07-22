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
    <link rel="icon" type="image/x-icon" href="assets/mango.ico">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link href="css/material-icons.css?v=<?= filemtime(__DIR__ . '/../css/material-icons.css'); ?>" rel="stylesheet">
    <?php foreach ($extraStyles as $href): ?>
        <link href="<?= e($href); ?>" rel="stylesheet">
    <?php endforeach; ?>
    <?php foreach ($extraScripts as $src): ?>
        <script src="<?= e($src); ?>" defer></script>
    <?php endforeach; ?>
    <script src="js/app-table.js?v=<?= filemtime(__DIR__ . '/../js/app-table.js'); ?>" defer></script>
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
            ['section' => 'Inicio', 'label' => 'Dashboard', 'icon' => 'fas fa-gauge-high', 'tab' => '#dashboard', 'active' => true],
            ['section' => 'Gestión', 'label' => 'Usuarios', 'icon' => 'fas fa-users-gear', 'tab' => '#usuarios'],
            ['section' => 'Gestión', 'label' => 'Solicitudes', 'icon' => 'fas fa-clipboard-check', 'tab' => '#solicitudes'],
            ['section' => 'Operación', 'label' => 'Movimientos', 'icon' => 'fas fa-arrow-right-arrow-left', 'tab' => '#movimientos'],
            ['section' => 'Operación', 'label' => 'Facturas', 'icon' => 'fas fa-receipt', 'tab' => '#facturas'],
            ['section' => 'Producción', 'label' => 'Cultivos', 'icon' => 'fas fa-seedling', 'tab' => '#cultivos'],
            ['section' => 'Producción', 'label' => 'Fitosanitario', 'icon' => 'fas fa-shield-virus', 'tab' => '#fitosanitario'],
            ['section' => 'Producción', 'label' => 'Cosecha', 'icon' => 'fas fa-wheat-awn', 'tab' => '#cosechas'],
            ['section' => 'Producción', 'label' => 'Poscosecha', 'icon' => 'fas fa-boxes-packing', 'tab' => '#poscosecha'],
            ['section' => 'Producción', 'label' => 'Proveedores', 'icon' => 'fas fa-truck-fast', 'tab' => '#pedidos-proveedores'],
            ['section' => 'Análisis', 'label' => 'Reportes', 'icon' => 'fas fa-chart-simple', 'tab' => '#reportes'],
        ];
    }

    if ($role === 'Agricultor') {
        return [
            ['section' => 'Inicio', 'label' => 'Dashboard', 'icon' => 'fas fa-gauge-high', 'href' => 'agricultor.php'],
            ['section' => 'Cultivo', 'label' => 'Calculadora', 'icon' => 'fas fa-calculator', 'href' => 'calcular_insumos.php'],
            ['section' => 'Seguimiento', 'label' => 'Fitosanitario', 'icon' => 'fas fa-shield-virus', 'href' => 'fitosanitario.php'],
            ['section' => 'Seguimiento', 'label' => 'Cosecha', 'icon' => 'fas fa-wheat-awn', 'href' => 'cosechas.php'],
            ['section' => 'Seguimiento', 'label' => 'Poscosecha', 'icon' => 'fas fa-boxes-packing', 'href' => 'poscosecha.php'],
            ['section' => 'Seguimiento', 'label' => 'Historial', 'icon' => 'fas fa-route', 'href' => 'historial_solicitudes.php'],
        ];
    }

    if ($role === 'Bodeguero') {
        return [
            ['section' => 'Inventario', 'label' => 'Bodega', 'icon' => 'fas fa-warehouse', 'href' => 'bodeguero.php'],
            ['section' => 'Inventario', 'label' => 'Fitosanitario', 'icon' => 'fas fa-shield-virus', 'href' => 'fitosanitario.php'],
            ['section' => 'Inventario', 'label' => 'Cosecha', 'icon' => 'fas fa-wheat-awn', 'href' => 'cosechas.php'],
            ['section' => 'Inventario', 'label' => 'Poscosecha', 'icon' => 'fas fa-boxes-packing', 'href' => 'poscosecha.php'],
            ['section' => 'Documentos', 'label' => 'Facturas', 'icon' => 'fas fa-receipt', 'href' => 'bodeguero_facturas.php'],
            ['section' => 'Documentos', 'label' => 'Solicitudes', 'icon' => 'fas fa-clipboard-check', 'href' => 'imprimir_solicitudes.php'],
        ];
    }

    return [];
}

function app_nav_section_icon(string $section): string
{
    return [
        'Inicio' => 'fas fa-gauge-high',
        'Gestión' => 'fas fa-layer-group',
        'Operación' => 'fas fa-list-check',
        'Producción' => 'fas fa-seedling',
        'Análisis' => 'fas fa-chart-simple',
        'Cultivo' => 'fas fa-leaf',
        'Seguimiento' => 'fas fa-route',
        'Inventario' => 'fas fa-warehouse',
        'Documentos' => 'fas fa-folder-open',
    ][$section] ?? 'fas fa-folder';
}

function app_nav_section_label(string $section): string
{
    return [
        'Inicio' => 'Dashboard',
        'Gestión' => 'Gestión',
        'Operación' => 'Operación',
        'Producción' => 'Producción',
        'Análisis' => 'Análisis',
        'Cultivo' => 'Cultivo',
        'Seguimiento' => 'Seguimiento',
        'Inventario' => 'Inventario',
        'Documentos' => 'Documentos',
    ][$section] ?? $section;
}

function render_app_nav(string $icon, string $label, array $actions = []): void
{
    $role = $_SESSION['rol'] ?? 'Invitado';
    $items = app_nav_items();
    $initials = app_user_initials();
    $currentPage = basename($_SERVER['SCRIPT_NAME'] ?? '');
    $groups = [];

    foreach ($items as $item) {
        $section = $item['section'] ?? 'Principal';
        if (!isset($groups[$section])) {
            $groups[$section] = [
                'icon' => app_nav_section_icon($section),
                'items' => [],
                'open' => false,
            ];
        }

        if (!empty($item['active']) || (!empty($item['href']) && basename($item['href']) === $currentPage)) {
            $groups[$section]['open'] = true;
        }

        $groups[$section]['items'][] = $item;
    }
    ?>
<aside class="app-sidebar" aria-label="Navegación principal">
    <div class="app-sidebar-header">
        <span class="app-brand-mark" aria-hidden="true"></span>
        <div class="app-brand-copy">
            <p class="app-brand-title">
                <span><?= e(app_config('app.name', 'SembriExport')); ?></span>
                <span class="app-brand-badge">BETA</span>
            </p>
            <p class="app-brand-subtitle"><?= e($role); ?></p>
        </div>
        <button class="app-sidebar-collapse" type="button" data-admin-sidebar-toggle aria-label="Plegar menú" aria-expanded="true">
            <i class="fas fa-chevron-left" aria-hidden="true"></i>
        </button>
    </div>

    <nav class="app-sidebar-nav">
        <?php foreach ($groups as $section => $group): ?>
            <?php $groupId = 'app-nav-' . strtolower(preg_replace('/[^a-z0-9]+/i', '-', $section)); ?>
            <?php if ($section === 'Inicio' && count($group['items']) === 1): ?>
                <?php $item = $group['items'][0]; ?>
                <div class="app-sidebar-single" data-app-sidebar-single>
                    <?php if (!empty($item['tab'])): ?>
                        <button class="app-sidebar-link <?= !empty($item['active']) ? 'active' : ''; ?>" type="button" data-app-tab="<?= e($item['tab']); ?>" title="<?= e($item['label']); ?>">
                            <i class="<?= e($item['icon']); ?>"></i>
                            <span><?= e($item['label']); ?></span>
                        </button>
                    <?php else: ?>
                        <a class="app-sidebar-link" href="<?= e($item['href']); ?>" title="<?= e($item['label']); ?>">
                            <i class="<?= e($item['icon']); ?>"></i>
                            <span><?= e($item['label']); ?></span>
                        </a>
                    <?php endif; ?>
                </div>
                <?php continue; ?>
            <?php endif; ?>
            <div class="app-sidebar-group <?= $group['open'] ? 'is-open' : ''; ?>" data-app-sidebar-group>
                <button class="app-sidebar-section-toggle"
                        type="button"
                        data-app-sidebar-section
                        aria-expanded="<?= $group['open'] ? 'true' : 'false'; ?>"
                        aria-controls="<?= e($groupId); ?>">
                    <i class="<?= e($group['icon']); ?>" aria-hidden="true"></i>
                    <span><?= e(app_nav_section_label($section)); ?></span>
                    <i class="fas fa-chevron-down app-sidebar-section-chevron" aria-hidden="true"></i>
                </button>

                <div class="app-sidebar-section-panel" id="<?= e($groupId); ?>">
                    <?php foreach ($group['items'] as $item): ?>
                        <?php if (!empty($item['tab'])): ?>
                            <button class="app-sidebar-link <?= !empty($item['active']) ? 'active' : ''; ?>" type="button" data-app-tab="<?= e($item['tab']); ?>" title="<?= e($item['label']); ?>">
                                <i class="<?= e($item['icon']); ?>"></i>
                                <span><?= e($item['label']); ?></span>
                            </button>
                        <?php else: ?>
                            <a class="app-sidebar-link" href="<?= e($item['href']); ?>" title="<?= e($item['label']); ?>">
                                <i class="<?= e($item['icon']); ?>"></i>
                                <span><?= e($item['label']); ?></span>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="app-nav-section">Sistema</div>
        <div class="app-sidebar-tools">
        <div class="app-sidebar-session">
            <div class="app-sidebar-account">
                <span class="app-avatar"><?= e($initials); ?></span>
                <span class="app-sidebar-account-copy">
                    <strong><?= e(current_user_name()); ?></strong>
                    <small><?= e($role); ?></small>
                </span>
            </div>
            <a class="app-sidebar-logout" href="logout.php" aria-label="Cerrar sesión" title="Cerrar sesión">
                <i class="fas fa-arrow-right-from-bracket"></i>
                <span>Cerrar sesión</span>
            </a>
        </div>
    </div>
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
    $quickActions = [
        ['icon' => 'fas fa-chart-line', 'label' => 'Mi monitoreo', 'prompt' => 'Muéstrame todos los datos de monitoreo de mis lotes'],
        ['icon' => 'fas fa-clipboard-list', 'label' => 'Mis solicitudes', 'prompt' => 'Muéstrame mis solicitudes registradas'],
        ['icon' => 'fas fa-location-dot', 'label' => 'Mis lotes', 'prompt' => 'Muéstrame mis lotes registrados'],
        ['icon' => 'fas fa-list-check', 'label' => 'Tareas pendientes', 'prompt' => 'Muéstrame mis actividades agrícolas pendientes'],
    ];

    if ($role === 'Administrador') {
        $quickActions = [
            ['icon' => 'fas fa-chart-line', 'label' => 'Monitoreo general', 'prompt' => 'Muéstrame todos los datos de monitoreo de los lotes'],
            ['icon' => 'fas fa-users-gear', 'label' => 'Usuarios', 'prompt' => 'Lista los usuarios registrados en el sistema'],
            ['icon' => 'fas fa-satellite-dish', 'label' => 'Actividad reciente', 'prompt' => 'Muéstrame la actividad agrícola reciente y las tareas pendientes'],
            ['icon' => 'fas fa-chart-simple', 'label' => 'Resumen general', 'prompt' => 'Muéstrame el resumen general del sistema'],
        ];
    } elseif ($role === 'Bodeguero') {
        $quickActions = [
            ['icon' => 'fas fa-box-archive', 'label' => 'Ver inventario', 'prompt' => 'Muéstrame el inventario de insumos'],
            ['icon' => 'fas fa-triangle-exclamation', 'label' => 'Insumos bajos', 'prompt' => 'Muéstrame los insumos con stock bajo en inventario'],
            ['icon' => 'fas fa-receipt', 'label' => 'Facturas', 'prompt' => 'Muéstrame las facturas registradas'],
            ['icon' => 'fas fa-clipboard-check', 'label' => 'Solicitudes pendientes', 'prompt' => 'Muéstrame las solicitudes pendientes por atender'],
        ];
    }
    ?>
    <div class="ada-chat" data-ada-chat data-endpoint="asistente/asistente_virtual.php">
        <button class="ada-chat__toggle" type="button" data-ada-toggle aria-label="Abrir ADA" aria-expanded="false">
                <img src="assets/img/ada-avatar.webp" alt="ADA" loading="lazy" decoding="async">
        </button>

        <section class="ada-chat__window" aria-label="Chat de ADA" aria-hidden="true">
            <header class="ada-chat__header">
                <div class="ada-chat__brand">
                    <span class="ada-chat__avatar">
                <img src="assets/img/ada-avatar.webp" alt="ADA" loading="lazy" decoding="async">
                    </span>
                    <div>
                        <div class="ada-chat__title-row">
                            <strong>ADA</strong>
                            <span class="ada-chat__status"><i></i> Disponible</span>
                        </div>
                        <span>Asistente de decisiones agrícolas</span>
                    </div>
                </div>
                <button class="ada-chat__close" type="button" data-ada-close aria-label="Cerrar ADA">
                    <i class="fas fa-xmark"></i>
                </button>
            </header>

            <div class="ada-chat__messages" data-ada-messages>
                <div class="ada-welcome-card">
                    <span class="ada-welcome-card__eyebrow">Asistencia inteligente</span>
                    <h3>Hola, <?= e($userName); ?></h3>
                    <p>Consulta información del sistema o elige una sugerencia para empezar.</p>
                </div>
                <div class="ada-suggestions">
                    <span class="ada-suggestions__label">Sugerencias</span>
                    <div class="ada-quick-actions" aria-label="Sugerencias de consulta">
                        <?php foreach ($quickActions as $action): ?>
                            <button type="button" data-ada-quick="<?= e($action['prompt']); ?>">
                                <i class="<?= e($action['icon']); ?>" aria-hidden="true"></i>
                                <span><?= e($action['label']); ?></span>
                                <i class="fas fa-arrow-right" aria-hidden="true"></i>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <form class="ada-chat__form" data-ada-form>
                <div class="ada-chat__input-wrap">
                    <i class="fas fa-wand-magic-sparkles" aria-hidden="true"></i>
                    <input class="ada-chat__input" type="text" data-ada-input placeholder="Escribe tu consulta..." autocomplete="off">
                </div>
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
    echo '<script src="assets/vendor/bootstrap/bootstrap.bundle.min.js"></script>' . PHP_EOL;
    foreach ($scripts as $src) {
        echo '<script src="' . e($src) . '"></script>' . PHP_EOL;
    }
}
