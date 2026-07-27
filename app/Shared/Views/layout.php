<?php

declare(strict_types=1);

function render_head(string $title, array $extraStyles = [], array $extraScripts = []): void
{
    $appName = app_config('app.name', 'SembriExport');
    $projectRoot = dirname(__DIR__, 3);
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title); ?> | <?= e($appName); ?></title>
    <base href="<?= e(\App\Core\Url::root() . '/'); ?>">
    <link rel="icon" type="image/x-icon" href="assets/mango.ico">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <?php foreach ($extraStyles as $href): ?>
        <link href="<?= e($href); ?>" rel="stylesheet">
    <?php endforeach; ?>
    <link href="css/material-icons.css?v=<?= filemtime($projectRoot . '/css/material-icons.css'); ?>" rel="stylesheet">
    <?php foreach ($extraScripts as $src): ?>
        <script src="<?= e($src); ?>" defer></script>
    <?php endforeach; ?>
    <script src="js/app-table.js?v=<?= filemtime($projectRoot . '/js/app-table.js'); ?>" defer></script>
    <script src="js/app-ui.js?v=<?= filemtime($projectRoot . '/js/app-ui.js'); ?>" defer></script>
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

function render_logout_control(string $variant = 'sidebar'): void
{
    $isDropdown = $variant === 'dropdown';
    $buttonClass = match ($variant) {
        'app-sidebar' => 'app-sidebar-logout app-logout-button',
        'dropdown' => 'app-logout-button app-logout-button--dropdown',
        default => 'nav-item app-logout-button',
    };
    ?>
    <form class="app-logout-form<?= $isDropdown ? ' app-logout-form--dropdown' : ''; ?>"
          method="post"
          action="<?= e(\App\Core\Url::route('/logout')); ?>">
        <?= csrf_field(); ?>
        <button class="<?= e($buttonClass); ?>" type="submit"<?= $isDropdown ? ' role="menuitem"' : ''; ?> aria-label="Cerrar sesión" title="Cerrar sesión">
            <span class="material-symbols-outlined" aria-hidden="true">logout</span>
            <span class="<?= $isDropdown ? '' : 'nav-label'; ?>">Cerrar sesión</span>
        </button>
    </form>
    <?php
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
            ['section' => 'Operación', 'label' => 'Inventario', 'icon' => 'fas fa-boxes-stacked', 'href' => \App\Core\Url::route('/inventario')],
            ['section' => 'Operación', 'label' => 'Facturas', 'icon' => 'fas fa-receipt', 'tab' => '#facturas'],
            ['section' => 'Producción', 'label' => 'Cultivos', 'icon' => 'fas fa-seedling', 'tab' => '#cultivos'],
            ['section' => 'Producción', 'label' => 'Proveedores', 'icon' => 'fas fa-truck-fast', 'tab' => '#pedidos-proveedores'],
            ['section' => 'Análisis', 'label' => 'Reportes', 'icon' => 'fas fa-chart-simple', 'tab' => '#reportes'],
        ];
    }

    if ($role === 'Agricultor') {
        return [
            ['section' => 'Inicio', 'label' => 'Dashboard', 'icon' => 'fas fa-gauge-high', 'href' => \App\Core\Url::route('/dashboard/agricultor')],
            ['section' => 'Cultivo', 'label' => 'Calculadora', 'icon' => 'fas fa-calculator', 'href' => \App\Core\Url::route('/insumos/calculadora')],
            ['section' => 'Seguimiento', 'label' => 'Historial', 'icon' => 'fas fa-route', 'href' => \App\Core\Url::route('/solicitudes/historial')],
            ['section' => 'Seguimiento', 'label' => 'Fitosanitario', 'icon' => 'fas fa-shield-virus', 'href' => \App\Core\Url::route('/plagas')],
        ];
    }

    if ($role === 'Bodeguero') {
        return [
            ['section' => 'Inventario', 'label' => 'Bodega', 'icon' => 'fas fa-warehouse', 'href' => \App\Core\Url::route('/dashboard/bodega')],
            ['section' => 'Inventario', 'label' => 'Productos', 'icon' => 'fas fa-boxes-stacked', 'href' => \App\Core\Url::route('/inventario')],
            ['section' => 'Documentos', 'label' => 'Facturas', 'icon' => 'fas fa-receipt', 'href' => \App\Core\Url::route('/facturas/recepcion')],
            ['section' => 'Documentos', 'label' => 'Solicitudes', 'icon' => 'fas fa-clipboard-check', 'href' => \App\Core\Url::route('/reportes/solicitudes')],
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
            <?php render_logout_control('app-sidebar'); ?>
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
        'success' => ['success', 'fa-check', 'Operación completada', 4200],
        'mensaje' => ['success', 'fa-check', 'Operación completada', 4200],
        'error' => ['danger', 'fa-exclamation', 'No se pudo completar', 6000],
        'error_entrega' => ['warning', 'fa-exclamation', 'Requiere atención', 5200],
    ];

    $notifications = [];
    foreach ($messages as $key => [$type, $icon, $title, $duration]) {
        $message = flash($key);
        if ($message === null) {
            continue;
        }

        $notifications[] = compact('type', 'icon', 'title', 'message') + [
            'duration' => $duration,
            'action_label' => null,
            'action_url' => null,
        ];
    }

    $guidance = \App\Shared\Support\ActionGuidance::decode(flash('next_step'));
    if ($guidance !== null) {
        $notifications[] = $guidance + ['duration' => 5600];
    }

    if (!$notifications) {
        return;
    }
    ?>
    <div class="app-notification-stack" data-app-notification-stack aria-live="polite" aria-atomic="true">
        <?php foreach ($notifications as $notification): ?>
            <div class="app-notification app-notification--<?= e($notification['type']); ?>"
                 data-app-notification
                 data-duration="<?= (int) $notification['duration']; ?>"
                 role="<?= $notification['type'] === 'danger' ? 'alert' : 'status'; ?>">
                <span class="app-notification__icon" aria-hidden="true">
                    <i class="fas <?= e($notification['icon']); ?>"></i>
                </span>
                <span class="app-notification__content">
                    <strong class="app-notification__title"><?= e($notification['title']); ?></strong>
                    <span class="app-notification__message"><?= e($notification['message']); ?></span>
                    <?php if (!empty($notification['action_url']) && !empty($notification['action_label'])): ?>
                        <a class="app-notification__action"
                           href="<?= e((string) $notification['action_url']); ?>"
                           data-app-notification-action>
                            <?= e((string) $notification['action_label']); ?> <span aria-hidden="true">→</span>
                        </a>
                    <?php endif; ?>
                </span>
                <button class="app-notification__close" type="button" data-app-notification-close aria-label="Cerrar notificación">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
}

/** @param array<string, string|null>|null $guidance */
function render_action_guidance(?array $guidance): void
{
    if ($guidance === null) {
        return;
    }
    ?>
    <aside class="app-action-guidance app-action-guidance--<?= e((string) $guidance['type']); ?>"
           role="status"
           aria-label="Siguiente paso">
        <span class="app-action-guidance__icon" aria-hidden="true">
            <i class="fas <?= e((string) $guidance['icon']); ?>"></i>
        </span>
        <div class="app-action-guidance__content">
            <strong><?= e((string) $guidance['title']); ?></strong>
            <p><?= e((string) $guidance['message']); ?></p>
            <?php if (!empty($guidance['action_url']) && !empty($guidance['action_label'])): ?>
                <a href="<?= e((string) $guidance['action_url']); ?>">
                    <?= e((string) $guidance['action_label']); ?> <span aria-hidden="true">→</span>
                </a>
            <?php endif; ?>
        </div>
    </aside>
    <?php
}

/** @param array<string, string> $options @param list<string> $selected */
function render_associated_crop_picker(array $options, array $selected = []): void
{
    static $pickerSequence = 0;
    $pickerSequence++;
    $labelId = 'associated-crop-label-' . $pickerSequence;
    $triggerId = 'associated-crop-trigger-' . $pickerSequence;
    $listboxId = 'associated-crop-listbox-' . $pickerSequence;
    $searchId = 'associated-crop-search-' . $pickerSequence;
    $selected = array_values(array_filter(
        array_keys($options),
        static fn (string $code): bool => in_array($code, $selected, true)
    ));
    $catalog = \App\Shared\Domain\AssociatedCropCatalog::catalog();
    $groups = [];
    foreach ($options as $code => $label) {
        $details = $catalog[$code] ?? [
            'label' => $label,
            'category' => 'Otros',
            'description' => 'Cultivo asociado',
            'icon' => 'eco',
        ];
        $groups[$details['category']][$code] = $details;
    }
    $expanded = $selected !== [];
    ?>
    <div class="associated-crop-picker" data-associated-crop-picker>
        <button class="associated-crop-picker__toggle"
                type="button"
                data-associated-crop-toggle
                aria-expanded="<?= $expanded ? 'true' : 'false'; ?>">
            <span class="associated-crop-picker__toggle-icon material-symbols-outlined" aria-hidden="true">add_circle</span>
            <span>
                <strong>Agregar cultivos asociados</strong>
                <small>
                    Opcional ·
                    <b data-associated-crop-count>
                        <?= count($selected); ?> <?= count($selected) === 1 ? 'seleccionado' : 'seleccionados'; ?>
                    </b>
                </small>
            </span>
            <span class="associated-crop-picker__arrow material-symbols-outlined" aria-hidden="true">expand_more</span>
        </button>

        <div class="associated-crop-picker__panel"
             data-associated-crop-panel
             <?= $expanded ? '' : 'hidden'; ?>>
            <span class="associated-crop-picker__label" id="<?= e($labelId); ?>">
                Seleccione un cultivo para agregar
            </span>
            <div class="associated-crop-picker__controls">
                <div class="associated-custom-select" data-associated-select>
                    <button class="associated-custom-select__trigger"
                            id="<?= e($triggerId); ?>"
                            type="button"
                            data-associated-select-trigger
                            aria-haspopup="listbox"
                            aria-expanded="false"
                            aria-labelledby="<?= e($labelId . ' ' . $triggerId); ?>"
                            aria-controls="<?= e($listboxId); ?>">
                        <span class="associated-custom-select__leading material-symbols-outlined" aria-hidden="true">eco</span>
                        <span class="associated-custom-select__value" data-associated-select-value>Seleccione una opción</span>
                        <span class="associated-custom-select__arrow material-symbols-outlined" aria-hidden="true">expand_more</span>
                    </button>
                    <div class="associated-custom-select__menu"
                         id="<?= e($listboxId); ?>"
                         data-associated-select-menu
                         role="listbox"
                         aria-labelledby="<?= e($labelId); ?>"
                         hidden>
                        <div class="associated-custom-select__search">
                            <span class="material-symbols-outlined" aria-hidden="true">search</span>
                            <label class="associated-custom-select__sr-only" for="<?= e($searchId); ?>">Buscar cultivo asociado</label>
                            <input id="<?= e($searchId); ?>"
                                   type="search"
                                   data-associated-select-search
                                   placeholder="Buscar cultivo..."
                                   autocomplete="off">
                            <button type="button"
                                    data-associated-select-search-clear
                                    aria-label="Limpiar búsqueda"
                                    hidden>
                                <span class="material-symbols-outlined" aria-hidden="true">close</span>
                            </button>
                        </div>
                        <div class="associated-custom-select__options" data-associated-select-options>
                            <?php foreach ($groups as $category => $crops): ?>
                                <div class="associated-custom-select__group"
                                     data-associated-select-group
                                     data-associated-category="<?= e($category); ?>">
                                    <span class="associated-custom-select__group-title"><?= e($category); ?></span>
                                    <?php foreach ($crops as $code => $details): ?>
                                        <?php $isSelected = in_array($code, $selected, true); ?>
                                        <button class="associated-custom-select__option<?= $isSelected ? ' is-added' : ''; ?>"
                                                type="button"
                                                role="option"
                                                data-associated-select-option
                                                data-associated-code="<?= e($code); ?>"
                                                data-associated-label="<?= e($details['label']); ?>"
                                                data-associated-description="<?= e($details['description']); ?>"
                                                data-associated-category="<?= e($details['category']); ?>"
                                                aria-selected="false"
                                                aria-disabled="<?= $isSelected ? 'true' : 'false'; ?>"
                                                <?= $isSelected ? 'disabled' : ''; ?>>
                                            <span class="associated-custom-select__option-icon material-symbols-outlined" aria-hidden="true"><?= e($details['icon']); ?></span>
                                            <span class="associated-custom-select__option-copy">
                                                <strong><?= e($details['label']); ?></strong>
                                                <small><?= e($details['description']); ?></small>
                                            </span>
                                            <span class="associated-custom-select__option-state" aria-hidden="true">
                                                <span class="material-symbols-outlined" aria-hidden="true">check_circle</span>
                                                <small>Agregado</small>
                                            </span>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                            <div class="associated-custom-select__empty" data-associated-select-empty hidden>
                                <span class="material-symbols-outlined" aria-hidden="true">search_off</span>
                                <strong>No encontramos coincidencias</strong>
                                <small>Pruebe con otro nombre o categoría.</small>
                            </div>
                        </div>
                        <div class="associated-custom-select__footer">
                            <span data-associated-available-count><?= count($options) ?> disponibles</span>
                            <span><kbd>↑</kbd><kbd>↓</kbd> navegar · <kbd>Enter</kbd> seleccionar</span>
                        </div>
                    </div>
                </div>
                <button class="farmer-action-button farmer-action-button--secondary"
                        type="button"
                        data-associated-crop-add
                        disabled>
                    <span class="material-symbols-outlined" aria-hidden="true">add</span>
                    <span data-associated-add-label>Agregar cultivo</span>
                </button>
            </div>
            <p class="associated-crop-picker__hint">Puede agregar más de uno y retirarlos antes de guardar.</p>
            <div class="associated-crop-picker__feedback"
                 data-associated-feedback
                 role="status"
                 aria-live="polite"
                 hidden></div>
            <div class="associated-crop-picker__summary"
                 data-associated-summary
                 <?= $selected === [] ? 'hidden' : ''; ?>>
                <span class="associated-crop-picker__summary-icon material-symbols-outlined" aria-hidden="true">checklist</span>
                <span>
                    <strong data-associated-summary-title>
                        <?= count($selected); ?> <?= count($selected) === 1 ? 'cultivo asociado' : 'cultivos asociados'; ?>
                    </strong>
                    <small data-associated-summary-copy>
                        <?= e(implode(' · ', array_map(static fn (string $code): string => $options[$code], $selected))); ?>
                    </small>
                </span>
                <button type="button"
                        data-associated-clear-all
                        <?= count($selected) < 2 ? 'hidden' : ''; ?>>
                    Quitar todos
                </button>
            </div>
            <div class="associated-crop-picker__selected"
                data-associated-crop-selected
                 aria-live="polite">
                <?php foreach ($selected as $code): ?>
                    <span class="associated-crop-chip"
                          data-associated-crop-chip="<?= e($code); ?>"
                          data-associated-label="<?= e($options[$code]); ?>">
                        <span class="associated-crop-chip__icon material-symbols-outlined" aria-hidden="true">
                            <?= e($catalog[$code]['icon'] ?? 'eco'); ?>
                        </span>
                        <span><?= e($options[$code]); ?></span>
                        <button type="button"
                                data-associated-crop-remove
                                data-associated-code="<?= e($code); ?>"
                                aria-label="Quitar <?= e($options[$code]); ?>">
                            <span class="material-symbols-outlined" aria-hidden="true">close</span>
                        </button>
                        <input type="hidden" name="cultivos_asociados[]" value="<?= e($code); ?>">
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php
}

/** @param array<int, array<string, mixed>> $messages */
function render_contextual_messages(array $messages): void
{
    if ($messages === []) {
        return;
    }
    $userKey = (string) ($_SESSION['id_usuario'] ?? $_SESSION['usuario'] ?? 'guest');
    ?>
    <section class="app-context-guidance"
             data-context-guidance
             data-context-user="<?= e($userKey); ?>"
             aria-label="Siguientes pasos recomendados">
        <?php foreach ($messages as $message): ?>
            <article class="app-context-message app-context-message--<?= e((string) $message['type']); ?>"
                     data-context-message
                     data-context-message-id="<?= e((string) $message['id']); ?>">
                <span class="app-context-message__icon material-symbols-outlined" aria-hidden="true"><?= e((string) $message['icon']); ?></span>
                <div class="app-context-message__content">
                    <strong><?= e((string) $message['title']); ?></strong>
                    <p><?= e((string) $message['message']); ?></p>
                    <div class="app-context-message__actions">
                        <?php if (!empty($message['action_url']) && !empty($message['action_label'])): ?>
                            <a href="<?= e((string) $message['action_url']); ?>" data-context-action><?= e((string) $message['action_label']); ?> <span aria-hidden="true">→</span></a>
                        <?php endif; ?>
                        <button type="button" data-context-dismiss>Descartar permanentemente</button>
                    </div>
                </div>
                <button class="app-context-message__close" type="button" data-context-close aria-label="Cerrar mensaje">
                    <span class="material-symbols-outlined" aria-hidden="true">close</span>
                </button>
            </article>
        <?php endforeach; ?>
    </section>
    <?php
}

function render_ada_chat(): void
{
    if (empty($_SESSION['rol'])) {
        return;
    }

    if (!isset($_SESSION['_csrf_token']) || !is_string($_SESSION['_csrf_token']) || strlen($_SESSION['_csrf_token']) < 32) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
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
    <div class="ada-chat" data-ada-chat data-endpoint="<?= e(\App\Core\Url::route('/api/asistente/chat')); ?>" data-csrf="<?= e($_SESSION['_csrf_token']); ?>">
        <button class="ada-chat__toggle" type="button" data-ada-toggle aria-label="Abrir ADA" aria-expanded="false">
                <img src="assets/img/ada-avatar.webp" alt="ADA" loading="lazy" decoding="async">
        </button>

        <section class="ada-chat__window" role="dialog" aria-labelledby="adaChatTitle" aria-hidden="true">
            <header class="ada-chat__header">
                <div class="ada-chat__brand">
                    <span class="ada-chat__avatar">
                <img src="assets/img/ada-avatar.webp" alt="ADA" loading="lazy" decoding="async">
                    </span>
                    <div>
                        <div class="ada-chat__title-row">
                            <strong id="adaChatTitle">ADA</strong>
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
    <script src="asistente/asistente_virtual.js?v=<?= filemtime(dirname(__DIR__, 3) . '/asistente/asistente_virtual.js'); ?>"></script>
    <?php
}

function render_scripts(array $scripts = []): void
{
    echo '<script src="assets/vendor/bootstrap/bootstrap.bundle.min.js"></script>' . PHP_EOL;
    foreach ($scripts as $src) {
        echo '<script src="' . e($src) . '"></script>' . PHP_EOL;
    }
}
