<?php

declare(strict_types=1);

use App\Core\Url;
use App\Shared\Helpers\Html;
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Inventario | SembriExport</title>
    <link rel="stylesheet" href="<?= Html::escape(Url::root('assets/vendor/bootstrap/bootstrap.min.css')) ?>">
    <link rel="stylesheet" href="<?= Html::escape(Url::root('css/admin.css')) ?>">
</head>
<body class="admin-dashboard-page">
<main class="container py-4">
    <header class="d-flex justify-content-between mb-4">
        <div><h1>Inventario de insumos</h1><p>Control de existencias y ajustes.</p></div>
        <a class="btn btn-outline-dark" href="<?= Html::escape(Url::route('/dashboard/bodega')) ?>">Volver</a>
    </header>
    <?php if ($success): ?><div class="alert alert-success"><?= Html::escape($success) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= Html::escape($error) ?></div><?php endif; ?>
    <section class="card mb-4">
        <div class="card-body">
            <h2 class="h4">Nuevo insumo</h2>
            <form method="post" action="<?= Html::escape(Url::route('/inventario')) ?>" class="row g-2">
                <input type="hidden" name="_token" value="<?= Html::escape($csrfToken) ?>">
                <div class="col-md-3"><input class="form-control" name="nombre" placeholder="Nombre" maxlength="200" required></div>
                <div class="col-md-2"><input class="form-control" name="tipo" placeholder="Tipo" maxlength="100" required></div>
                <div class="col-md-2"><input class="form-control" name="unidad_medida" placeholder="Unidad" maxlength="50" required></div>
                <div class="col-md-2"><input class="form-control" type="number" min="0" step="0.01" name="cantidad" placeholder="Cantidad" required></div>
                <div class="col-md-3"><button class="btn btn-success w-100">Registrar</button></div>
                <div class="col-12"><textarea class="form-control" name="descripcion" placeholder="Descripción"></textarea></div>
            </form>
        </div>
    </section>
    <section class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Insumo</th><th>Tipo</th><th>Unidad</th><th>Stock</th><th>Ajustar</th></tr></thead>
                <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?= Html::escape($item['nombre']) ?></td>
                        <td><?= Html::escape($item['tipo']) ?></td>
                        <td><?= Html::escape($item['unidad_medida']) ?></td>
                        <td><?= Html::escape($item['cantidad']) ?></td>
                        <td>
                            <form class="d-flex gap-2" method="post" action="<?= Html::escape(Url::route('/inventario/ajustar')) ?>">
                                <input type="hidden" name="_token" value="<?= Html::escape($csrfToken) ?>">
                                <input type="hidden" name="id_insumo" value="<?= (int) $item['id_insumos'] ?>">
                                <input class="form-control form-control-sm" type="number" min="0" step="0.01" name="cantidad" value="<?= Html::escape($item['cantidad']) ?>" required>
                                <button class="btn btn-sm btn-primary">Guardar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>
</body>
</html>
