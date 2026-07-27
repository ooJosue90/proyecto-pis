<?php

declare(strict_types=1);

use App\Core\Url;
use App\Shared\Domain\AssociatedCropCatalog;
use App\Shared\Helpers\Html;

$projectRoot = dirname(__DIR__, 4);
require_once $projectRoot . '/app/Shared/Views/layout.php';
$associatedCropOptions = AssociatedCropCatalog::options();
$selectedAssociatedCrops = is_array($old['cultivos_asociados'] ?? null)
    ? $old['cultivos_asociados']
    : [];

$dashboard = match ($user['rol']) {
    'Administrador' => Url::route('/dashboard/admin'),
    'Agricultor' => Url::route('/dashboard/agricultor'),
    default => Url::route('/login'),
};
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cultivos | SembriExport</title>
    <link rel="stylesheet" href="<?= Html::escape(Url::root('assets/vendor/bootstrap/bootstrap.min.css')) ?>">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined">
    <link rel="stylesheet" href="<?= Html::escape(Url::root('css/admin.css')) ?>">
</head>
<body class="admin-dashboard-page farmer-dashboard-page">
<main class="container py-4">
    <header class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <span class="farmer-kicker">Módulo piloto MVC</span>
            <h1 class="mb-1">Cultivos</h1>
            <p class="text-secondary mb-0">Registros disponibles para <?= Html::escape($user['nombre']) ?>.</p>
        </div>
        <a class="btn btn-outline-dark" href="<?= Html::escape($dashboard) ?>">Volver al panel</a>
    </header>

    <?php if ($success): ?>
        <div class="alert alert-success" role="status"><?= Html::escape($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger" role="alert"><?= Html::escape($error) ?></div>
    <?php endif; ?>
    <?php render_action_guidance($nextStep ?? null); ?>

    <?php if ($user['rol'] === 'Agricultor'): ?>
        <section class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <h2 class="h4">Registrar Mango Tommy Atkins</h2>
                <p class="text-secondary">La variedad principal es fija. Puede seleccionar cultivos complementarios para la siembra conjunta.</p>
                <form method="post" action="<?= Html::escape(Url::route('/cultivos')) ?>" class="row g-3">
                    <input type="hidden" name="_token" value="<?= Html::escape($csrfToken) ?>">
                    <input type="hidden" name="tipo" value="<?= Html::escape(AssociatedCropCatalog::MAIN_CROP) ?>">
                    <div class="col-lg-4">
                        <span class="form-label d-block">Cultivo principal</span>
                        <div class="crop-fixed-value crop-fixed-value--standalone">
                            <span class="material-symbols-outlined" aria-hidden="true">verified</span>
                            <strong><?= Html::escape(AssociatedCropCatalog::MAIN_CROP) ?></strong>
                            <small>Variedad oficial del programa</small>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <label class="form-label" for="nombre">Nombre del cultivo</label>
                        <input class="form-control" id="nombre" name="nombre" maxlength="120" placeholder="Ej. Mango norte" value="<?= Html::escape($old['nombre'] ?? '') ?>" required>
                    </div>
                    <div class="col-lg-4">
                        <label class="form-label" for="fecha_siembra">Fecha de siembra</label>
                        <input class="form-control" type="date" id="fecha_siembra" name="fecha_siembra" value="<?= Html::escape($old['fecha_siembra'] ?? '') ?>" min="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="col-12">
                        <?php render_associated_crop_picker($associatedCropOptions, $selectedAssociatedCrops); ?>
                    </div>
                    <div class="col-12 d-flex justify-content-end">
                        <button class="btn btn-success px-4" type="submit">Registrar cultivo</button>
                    </div>
                </form>
            </div>
        </section>
    <?php endif; ?>

    <section class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead><tr><th>ID</th><th>Nombre</th><th>Variedad</th><th>Asociados</th><th>Fecha de siembra</th><?php if ($user['rol'] === 'Administrador'): ?><th>Agricultor</th><?php endif; ?><th></th></tr></thead>
                    <tbody>
                    <?php if ($cultivos === []): ?>
                        <tr><td colspan="<?= $user['rol'] === 'Administrador' ? 7 : 6 ?>" class="text-center py-5 text-secondary">No hay cultivos registrados.</td></tr>
                    <?php else: ?>
                        <?php foreach ($cultivos as $cultivo): ?>
                            <tr>
                                <td><?= $cultivo->id ?></td>
                                <td><strong><?= Html::escape($cultivo->nombre) ?></strong></td>
                                <td><?= Html::escape($cultivo->tipo) ?></td>
                                <td>
                                    <?php $associatedLabels = $cultivo->associatedCropLabels(); ?>
                                    <?= $associatedLabels === [] ? '<span class="text-secondary">Sin asociados</span>' : Html::escape(implode(', ', $associatedLabels)) ?>
                                </td>
                                <td><?= Html::escape($cultivo->fechaSiembra) ?></td>
                                <?php if ($user['rol'] === 'Administrador'): ?><td><?= Html::escape($cultivo->agricultor ?? '') ?></td><?php endif; ?>
                                <td class="text-end">
                                    <a href="<?= Html::escape(Url::route('/cultivos/' . $cultivo->id)) ?>">Ver</a>
                                    <?php if ($user['rol'] === 'Administrador'): ?>
                                        <form method="post" action="<?= Html::escape(Url::route('/cultivos/' . $cultivo->id)) ?>" class="d-inline ms-2" onsubmit="return confirm('¿Eliminar este cultivo?');">
                                            <input type="hidden" name="_method" value="DELETE">
                                            <input type="hidden" name="_token" value="<?= Html::escape($csrfToken) ?>">
                                            <button class="btn btn-link link-danger p-0" type="submit">Eliminar</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</main>
<script src="<?= Html::escape(Url::root('js/associated-crops.js')) ?>"></script>
</body>
</html>
