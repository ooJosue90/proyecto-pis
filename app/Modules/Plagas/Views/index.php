<?php

declare(strict_types=1);

$projectRoot = dirname(__DIR__, 4);
require_once $projectRoot . '/app/Shared/Views/layout.php';

$isFarmer = $user['rol'] === 'Agricultor';
$affectedLots = count(array_unique(array_map(static fn ($plaga): int => $plaga->loteId, $plagas)));
$days = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
$months = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
$today = $days[(int) date('w')] . ', ' . date('j') . ' de ' . $months[(int) date('n') - 1] . ' de ' . date('Y');
?>
<?php render_head('Control Fitosanitario', [
    'https://fonts.googleapis.com/css2?family=Raleway:wght@500;600;700;800;900&family=Roboto+Condensed:wght@400;500;600;700;800;900&display=swap',
    'assets/vendor/bootstrap/bootstrap.min.css',
    'assets/vendor/fontawesome/css/all.min.css',
    'css/admin.css?v=' . filemtime($projectRoot . '/css/admin.css'),
]); ?>
<body class="farmer-dashboard-page admin-dashboard-page phytosanitary-page">
<div class="admin-tablet-shell">
    <aside class="sidebar" id="mainSidebar" aria-label="Navegación principal">
        <div class="logo-container">
            <div class="admin-sidebar-logo"><i class="fas fa-seedling" aria-hidden="true"></i></div>
            <span class="nav-label admin-sidebar-brand">SembriExport</span>
        </div>

        <nav class="app-sidebar-nav admin-reference-nav">
            <a class="nav-item app-sidebar-link" href="<?= e(\App\Core\Url::route($isFarmer ? '/dashboard/agricultor' : '/dashboard/admin')); ?>" title="Dashboard">
                <span class="material-symbols-outlined" aria-hidden="true">dashboard</span>
                <span class="nav-label">Dashboard</span>
            </a>
            <?php if ($isFarmer): ?>
                <a class="nav-item app-sidebar-link" href="<?= e(\App\Core\Url::route('/insumos/calculadora')); ?>" title="Calculadora">
                    <span class="material-symbols-outlined" aria-hidden="true">calculate</span>
                    <span class="nav-label">Calculadora</span>
                </a>
                <a class="nav-item app-sidebar-link" href="<?= e(\App\Core\Url::route('/solicitudes/historial')); ?>" title="Historial">
                    <span class="material-symbols-outlined" aria-hidden="true">route</span>
                    <span class="nav-label">Historial</span>
                </a>
            <?php endif; ?>
            <a class="nav-item app-sidebar-link active" href="<?= e(\App\Core\Url::route('/plagas')); ?>" title="Fitosanitario">
                <span class="material-symbols-outlined" aria-hidden="true">health_and_safety</span>
                <span class="nav-label">Fitosanitario</span>
            </a>
        </nav>

        <div class="admin-sidebar-actions"><?php render_logout_control(); ?></div>
    </aside>

    <main class="admin-inner-container">
        <header class="admin-reference-topbar">
            <div class="admin-topbar-user">
                <span class="admin-topbar-avatar"><?= e(app_user_initials()); ?></span>
                <div><h2>Saludos, <?= e(current_user_name()); ?></h2><p>Monitorea la salud de tus lotes y registra novedades.</p></div>
            </div>
            <div class="admin-topbar-actions">
                <div class="admin-account-menu" data-admin-account-menu>
                    <button class="admin-account-button" type="button" aria-haspopup="menu" aria-expanded="false" data-admin-account-trigger>
                        <span class="admin-account-initials" aria-hidden="true"><?= e(app_user_initials()); ?></span>
                        <span>Cuenta</span>
                        <span class="material-symbols-outlined" aria-hidden="true">expand_more</span>
                    </button>
                    <div class="admin-account-dropdown" role="menu" aria-label="Opciones de cuenta">
                        <div class="admin-account-dropdown__profile" aria-hidden="true"><strong><?= e($user['rol']); ?></strong><small><?= e(current_user_name()); ?></small></div>
                        <?php render_logout_control('dropdown'); ?>
                    </div>
                </div>
            </div>
        </header>

        <div class="container farmer-dashboard admin-dashboard mt-4">
            <?php if ($success): ?><div class="app-notification alert alert-success" role="status"><i class="fas fa-circle-check"></i> <?= e($success); ?></div><?php endif; ?>
            <?php if ($error): ?><div class="app-notification alert alert-danger" role="alert"><i class="fas fa-triangle-exclamation"></i> <?= e($error); ?></div><?php endif; ?>

            <section class="farmer-page-heading admin-page-heading">
                <div class="admin-greeting">
                    <div class="admin-heading-copy">
                        <h1>Control <span>fitosanitario</span></h1>
                        <p><?= e($today); ?></p>
                    </div>
                </div>
            </section>

            <header class="admin-users__header phytosanitary-admin-header">
                <div class="admin-users__title">
                    <span class="admin-users__header-icon"><i class="fas fa-shield-virus" aria-hidden="true"></i></span>
                    <div>
                        <span class="admin-section-eyebrow">Sanidad del cultivo</span>
                        <h4>Registro de afectaciones</h4>
                        <p>Reporta hallazgos y conserva la trazabilidad de cada lote.</p>
                    </div>
                </div>
            </header>

            <section class="row admin-users__metrics phytosanitary-summary" aria-label="Resumen fitosanitario">
                <div class="col-md-4">
                    <article class="admin-users__metric">
                        <span class="admin-users__metric-icon admin-users__metric-icon--total"><i class="fas fa-bug" aria-hidden="true"></i></span>
                        <div><span>Afectaciones registradas</span><strong><?= count($plagas); ?></strong></div>
                    </article>
                </div>
                <div class="col-md-4">
                    <article class="admin-users__metric">
                        <span class="admin-users__metric-icon admin-users__metric-icon--farmer"><i class="fas fa-seedling" aria-hidden="true"></i></span>
                        <div><span>Lotes con seguimiento</span><strong><?= $affectedLots; ?></strong></div>
                    </article>
                </div>
                <div class="col-md-4">
                    <article class="admin-users__metric">
                        <span class="admin-users__metric-icon admin-users__metric-icon--warehouse"><i class="fas fa-location-dot" aria-hidden="true"></i></span>
                        <div><span>Lotes disponibles</span><strong><?= count($lotes); ?></strong></div>
                    </article>
                </div>
            </section>

            <div class="phytosanitary-grid<?= $isFarmer ? '' : ' phytosanitary-grid--single'; ?>">
                <?php if ($isFarmer): ?>
                    <section class="phytosanitary-card phytosanitary-form-card" aria-labelledby="phytosanitary-form-title">
                        <header>
                            <span class="phytosanitary-card__icon"><span class="material-symbols-outlined" aria-hidden="true">add_circle</span></span>
                            <div><span class="farmer-kicker">Nuevo hallazgo</span><h2 id="phytosanitary-form-title">Registrar afectación</h2></div>
                        </header>
                        <?php if ($lotes === []): ?>
                            <div class="phytosanitary-empty phytosanitary-empty--compact">
                                <span class="material-symbols-outlined" aria-hidden="true">info</span>
                                <p>Primero debes registrar un lote para asociar el hallazgo.</p>
                                <a href="<?= e(\App\Core\Url::route('/dashboard/agricultor', ['tab' => 'lote'])); ?>">Registrar lote</a>
                            </div>
                        <?php else: ?>
                            <form method="post" action="<?= e(\App\Core\Url::route('/plagas')); ?>" class="phytosanitary-form">
                                <input type="hidden" name="_token" value="<?= e($csrfToken); ?>">
                                <div class="phytosanitary-field">
                                    <label for="id_lote">Lote afectado <b>*</b></label>
                                    <select id="id_lote" name="id_lote" class="form-select" data-admin-select required aria-label="Seleccionar lote afectado">
                                        <option value="" selected disabled>Selecciona un lote</option>
                                        <?php foreach ($lotes as $lote): ?>
                                            <option value="<?= $lote->id; ?>">#<?= $lote->id; ?> · <?= e($lote->ubicacion); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="phytosanitary-field">
                                    <label for="nombre">Plaga o afectación <b>*</b></label>
                                    <div class="phytosanitary-control">
                                        <span class="material-symbols-outlined" aria-hidden="true">pest_control</span>
                                        <input id="nombre" name="nombre" type="text" maxlength="200" placeholder="Ej. Mosca de la fruta" required>
                                    </div>
                                    <small>Describe brevemente lo observado en el lote.</small>
                                </div>
                                <button class="phytosanitary-submit" type="submit"><span class="material-symbols-outlined" aria-hidden="true">save</span> Guardar registro</button>
                            </form>
                        <?php endif; ?>
                    </section>
                <?php endif; ?>

                <section class="phytosanitary-card phytosanitary-history" aria-labelledby="phytosanitary-history-title">
                    <header>
                        <span class="phytosanitary-card__icon"><span class="material-symbols-outlined" aria-hidden="true">history</span></span>
                        <div><span class="farmer-kicker">Trazabilidad</span><h2 id="phytosanitary-history-title">Registros recientes</h2></div>
                    </header>
                    <?php if ($plagas === []): ?>
                        <div class="phytosanitary-empty"><span class="material-symbols-outlined" aria-hidden="true">shield</span><h3>Sin afectaciones registradas</h3><p>Cuando reportes un hallazgo aparecerá en este historial.</p></div>
                    <?php else: ?>
                        <div class="phytosanitary-list">
                            <?php foreach ($plagas as $plaga): ?>
                                <article>
                                    <span class="phytosanitary-list__marker"><span class="material-symbols-outlined" aria-hidden="true">pest_control</span></span>
                                    <div><strong><?= e($plaga->nombre); ?></strong><p>Lote #<?= $plaga->loteId; ?> · <?= e($plaga->ubicacion ?? 'Sin ubicación'); ?></p></div>
                                    <time datetime="<?= e($plaga->fecha); ?>"><?= e(date('d/m/Y', strtotime($plaga->fecha))); ?></time>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
            </div>
        </div>
    </main>
</div>

<?php render_ada_chat(); ?>
<?php render_scripts(['js/admin-forms.js?v=' . filemtime($projectRoot . '/js/admin-forms.js')]); ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    window.AdminFormMethods?.setupGenericAdminSelects(document);
});
</script>
</body>
</html>
