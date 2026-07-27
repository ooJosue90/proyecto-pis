<?php

declare(strict_types=1);

use App\Core\Url;
use App\Shared\Helpers\Html;
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= Html::escape($cultivo->nombre) ?> | SembriExport</title>
    <link rel="stylesheet" href="<?= Html::escape(Url::root('assets/vendor/bootstrap/bootstrap.min.css')) ?>">
</head>
<body class="bg-light">
<main class="container py-5">
    <a href="<?= Html::escape(Url::route('/cultivos')) ?>">← Volver a cultivos</a>
    <article class="card border-0 shadow-sm mt-3"><div class="card-body p-4">
        <small class="text-secondary">Cultivo #<?= $cultivo->id ?></small>
        <h1><?= Html::escape($cultivo->nombre) ?></h1>
        <dl class="row mb-0">
            <dt class="col-sm-4">Variedad principal</dt><dd class="col-sm-8"><?= Html::escape($cultivo->tipo) ?></dd>
            <dt class="col-sm-4">Fecha de siembra</dt><dd class="col-sm-8"><?= Html::escape($cultivo->fechaSiembra) ?></dd>
            <dt class="col-sm-4">Cultivos asociados</dt>
            <dd class="col-sm-8">
                <?php $associatedLabels = $cultivo->associatedCropLabels(); ?>
                <?= Html::escape($associatedLabels === [] ? 'Sin cultivos asociados' : implode(', ', $associatedLabels)) ?>
            </dd>
            <?php if ($user['rol'] === 'Administrador'): ?><dt class="col-sm-4">Agricultor</dt><dd class="col-sm-8"><?= Html::escape($cultivo->agricultor ?? '') ?></dd><?php endif; ?>
        </dl>
    </div></article>
</main>
</body>
</html>
