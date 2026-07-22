<?php

declare(strict_types=1);

use App\Core\Url;
use App\Shared\Helpers\Html;
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Lote #<?= $lote->id ?> | SembriExport</title><link rel="stylesheet" href="<?= Html::escape(Url::root('assets/vendor/bootstrap/bootstrap.min.css')) ?>"></head>
<body class="bg-light"><main class="container py-5"><a href="<?= Html::escape(Url::route('/lotes')) ?>">← Volver a lotes</a><article class="card border-0 shadow-sm mt-3"><div class="card-body p-4"><small class="text-secondary">Lote #<?= $lote->id ?></small><h1><?= Html::escape($lote->ubicacion) ?></h1><dl class="row mb-0"><dt class="col-sm-4">Área</dt><dd class="col-sm-8"><?= Html::escape($lote->area) ?> ha</dd><dt class="col-sm-4">Cultivo</dt><dd class="col-sm-8"><?= Html::escape($lote->cultivo ?? '') ?></dd><dt class="col-sm-4">Etapa</dt><dd class="col-sm-8"><?= Html::escape($lote->etapaLabel()) ?></dd><dt class="col-sm-4">Estado</dt><dd class="col-sm-8"><?= Html::escape($lote->estadoLabel()) ?></dd><?php if ($user['rol'] === 'Administrador'): ?><dt class="col-sm-4">Agricultor</dt><dd class="col-sm-8"><?= Html::escape($lote->agricultor ?? '') ?></dd><?php endif; ?></dl></div></article></main></body></html>
