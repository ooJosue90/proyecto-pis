<?php

declare(strict_types=1);

use App\Core\Url;
use App\Shared\Helpers\Html;
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lotes | SembriExport</title>
    <link rel="stylesheet" href="<?= Html::escape(Url::root('assets/vendor/bootstrap/bootstrap.min.css')) ?>">
    <link rel="stylesheet" href="<?= Html::escape(Url::root('css/admin.css')) ?>">
</head>
<body class="admin-dashboard-page">
<main class="container py-4">
    <header class="d-flex justify-content-between align-items-center mb-4"><div><h1>Lotes</h1><p class="text-secondary mb-0">Lotes visibles para <?= Html::escape($user['nombre']) ?>.</p></div><a class="btn btn-outline-dark" href="<?= Html::escape(Url::route($user['rol'] === 'Administrador' ? '/dashboard/admin' : '/dashboard/agricultor')) ?>">Volver al panel</a></header>
    <?php if ($success): ?><div class="alert alert-success"><?= Html::escape($success) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= Html::escape($error) ?></div><?php endif; ?>
    <section class="card border-0 shadow-sm"><div class="card-body p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
        <thead><tr><th>ID</th><th>Ubicación</th><th>Área</th><th>Cultivo</th><th>Etapa</th><th>Estado</th><?php if ($user['rol'] === 'Administrador'): ?><th>Agricultor</th><?php endif; ?><th></th></tr></thead>
        <tbody>
        <?php if ($lotes === []): ?><tr><td colspan="8" class="text-center py-5 text-secondary">No hay lotes registrados.</td></tr><?php endif; ?>
        <?php foreach ($lotes as $lote): ?><tr>
            <td><?= $lote->id ?></td><td><?= Html::escape($lote->ubicacion) ?></td><td><?= Html::escape($lote->area) ?> ha</td><td><?= Html::escape($lote->cultivo ?? '') ?></td><td><?= Html::escape($lote->etapaLabel()) ?></td><td><?= Html::escape($lote->estadoLabel()) ?></td>
            <?php if ($user['rol'] === 'Administrador'): ?><td><?= Html::escape($lote->agricultor ?? '') ?></td><?php endif; ?>
            <td><a href="<?= Html::escape(Url::route('/lotes/' . $lote->id)) ?>">Ver</a></td>
        </tr><?php endforeach; ?>
        </tbody>
    </table></div></div></section>
</main>
</body></html>
