<?php

declare(strict_types=1);

use App\Core\Url;
use App\Shared\Helpers\Html;

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

    <?php if ($user['rol'] === 'Agricultor'): ?>
        <section class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <h2 class="h4">Registrar cultivo</h2>
                <form method="post" action="<?= Html::escape(Url::route('/cultivos')) ?>" class="row g-3">
                    <input type="hidden" name="_token" value="<?= Html::escape($csrfToken) ?>">
                    <div class="col-md-6">
                        <label class="form-label" for="tipo">Tipo de cultivo</label>
                        <input class="form-control" id="tipo" name="tipo" maxlength="150" value="<?= Html::escape($old['tipo'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="fecha_siembra">Fecha de siembra</label>
                        <input class="form-control" type="date" id="fecha_siembra" name="fecha_siembra" value="<?= Html::escape($old['fecha_siembra'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button class="btn btn-success w-100" type="submit">Registrar</button>
                    </div>
                </form>
            </div>
        </section>
    <?php endif; ?>

    <section class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead><tr><th>ID</th><th>Tipo</th><th>Fecha de siembra</th><?php if ($user['rol'] === 'Administrador'): ?><th>Agricultor</th><?php endif; ?><th></th></tr></thead>
                    <tbody>
                    <?php if ($cultivos === []): ?>
                        <tr><td colspan="5" class="text-center py-5 text-secondary">No hay cultivos registrados.</td></tr>
                    <?php else: ?>
                        <?php foreach ($cultivos as $cultivo): ?>
                            <tr>
                                <td><?= $cultivo->id ?></td>
                                <td><?= Html::escape($cultivo->tipo) ?></td>
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
</body>
</html>
