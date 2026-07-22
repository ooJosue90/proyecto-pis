<?php

declare(strict_types=1);

use App\Core\Url;
use App\Shared\Helpers\Html;

$dashboardUrl = Url::route($user['rol'] === 'Administrador' ? '/dashboard/admin' : '/dashboard/agricultor');
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Producción | SembriExport</title>
    <link rel="stylesheet" href="<?= Html::escape(Url::root('assets/vendor/bootstrap/bootstrap.min.css')) ?>">
    <link rel="stylesheet" href="<?= Html::escape(Url::root('css/admin.css')) ?>">
</head>
<body class="admin-dashboard-page">
<main class="container py-4">
    <header class="d-flex justify-content-between align-items-center mb-4">
        <div><h1>Producción registrada</h1><p class="text-secondary mb-0">Cosechas visibles para <?= Html::escape($user['nombre']) ?>.</p></div>
        <a class="btn btn-outline-dark" href="<?= Html::escape($dashboardUrl) ?>">Volver</a>
    </header>
    <?php if ($success): ?><div class="alert alert-success"><?= Html::escape($success) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= Html::escape($error) ?></div><?php endif; ?>
    <section class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Fecha</th><th>Producto</th><th>Lote</th><th>Ubicación</th><th>Cantidad</th><?php if ($user['rol'] === 'Administrador'): ?><th>Agricultor</th><?php endif; ?><th>Observaciones</th></tr></thead>
                <tbody>
                <?php if ($producciones === []): ?><tr><td colspan="7" class="text-center py-5">No hay producción registrada.</td></tr><?php endif; ?>
                <?php foreach ($producciones as $item): ?>
                    <tr>
                        <td><?= Html::escape($item->date) ?></td>
                        <td><?= Html::escape($item->productName) ?></td>
                        <td>#<?= $item->loteId ?></td>
                        <td><?= Html::escape($item->ubicacion ?? '') ?></td>
                        <td><?= Html::escape($item->quantity) ?> <?= Html::escape($item->unit) ?></td>
                        <?php if ($user['rol'] === 'Administrador'): ?><td><?= Html::escape($item->agricultor ?? '') ?></td><?php endif; ?>
                        <td><?= Html::escape($item->observations ?? '') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>
</body>
</html>
