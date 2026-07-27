<?php

declare(strict_types=1);

use App\Core\Url;
use App\Shared\Domain\CultivationStage;
use App\Shared\Helpers\Html;

$projectRoot = dirname(__DIR__, 4);
require_once $projectRoot . '/app/Shared/Views/layout.php';

$dashboardUrl = Url::route($user['rol'] === 'Administrador' ? '/dashboard/admin' : '/dashboard/agricultor');
$dateFields = [
    CultivationStage::PLANTING => ['fecha_inicio_siembra', 'fecha_fin_siembra'],
    CultivationStage::IRRIGATION => ['fecha_inicio_riego', 'fecha_fin_riego'],
    CultivationStage::HARVEST => ['fecha_inicio_cosecha', 'fecha_fin_cosecha'],
];
$icons = [
    CultivationStage::PLANTING => 'agriculture',
    CultivationStage::IRRIGATION => 'water_drop',
    CultivationStage::HARVEST => 'harvest',
];
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Fases del lote #<?= $lote->id ?> | SembriExport</title>
    <base href="<?= Html::escape(Url::root() . '/') ?>">
    <link rel="stylesheet" href="assets/vendor/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined">
    <link rel="stylesheet" href="css/admin.css">
</head>
<body class="admin-dashboard-page farmer-dashboard-page">
<main class="container py-4 lot-phase-page">
    <header class="lot-phase-header">
        <div>
            <span class="farmer-kicker">Seguimiento productivo</span>
            <h1><?= Html::escape($lote->ubicacion) ?></h1>
            <p>Lote #<?= $lote->id ?> · <?= Html::escape($lote->cultivo ?? '') ?> · <?= Html::escape($lote->area) ?> ha</p>
        </div>
        <div class="lot-phase-header__actions">
            <a class="btn btn-outline-secondary" href="<?= Html::escape(Url::route('/lotes')) ?>">Ver lotes</a>
            <a class="btn btn-outline-dark" href="<?= Html::escape($dashboardUrl) ?>">Volver al panel</a>
        </div>
    </header>

    <?php if ($success): ?><div class="alert alert-success" role="status"><?= Html::escape($success) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger" role="alert"><?= Html::escape($error) ?></div><?php endif; ?>
    <?php render_action_guidance($nextStep ?? null); ?>
    <div class="alert alert-warning d-none" role="alert" data-stage-message></div>

    <section class="lot-phase-shell">
        <header class="lot-phase-shell__heading">
            <div>
                <span class="farmer-kicker">Secuencia obligatoria</span>
                <h2>Fases del cultivo</h2>
                <p>Complete cada fase para desbloquear la siguiente. Las fases anteriores permanecen disponibles para revisión.</p>
            </div>
            <span class="crop-status crop-status--<?= Html::escape(str_replace('_', '-', $lote->estado)) ?>">
                <?= Html::escape($lote->estadoLabel()) ?>
            </span>
        </header>

        <nav class="lot-phase-stepper" aria-label="Fases del cultivo">
            <?php foreach (CultivationStage::labels(false) as $stage => $label): ?>
                <?php
                $status = $lote->phaseStatus($stage);
                $accessible = CultivationStage::canAccess($stage, $lote->etapaActual, $lote->phaseStates);
                $active = $selectedStage === $stage;
                $previousLabel = CultivationStage::label(max(CultivationStage::PLANTING, $stage - 1));
                ?>
                <div class="lot-phase-step lot-phase-step--<?= Html::escape($status) ?> <?= $active ? 'is-active' : '' ?>">
                    <?php if ($accessible): ?>
                        <a href="<?= Html::escape(Url::route('/lotes/' . $lote->id, ['fase' => $stage])) ?>"
                           aria-current="<?= $active ? 'step' : 'false' ?>">
                    <?php else: ?>
                        <button type="button"
                                aria-disabled="true"
                                data-stage-locked
                                data-message="Debe completar <?= Html::escape($previousLabel) ?> antes de acceder a <?= Html::escape($label) ?>.">
                    <?php endif; ?>
                        <span class="lot-phase-step__number"><?= str_pad((string) $stage, 2, '0', STR_PAD_LEFT) ?></span>
                        <span class="material-symbols-outlined" aria-hidden="true"><?= Html::escape($icons[$stage]) ?></span>
                        <span class="lot-phase-step__copy">
                            <strong><?= Html::escape($label) ?></strong>
                            <small><?= Html::escape(CultivationStage::statusLabel($status)) ?></small>
                        </span>
                        <?php if (!$accessible): ?><span class="material-symbols-outlined lot-phase-step__lock">lock</span><?php endif; ?>
                    <?= $accessible ? '</a>' : '</button>' ?>
                </div>
            <?php endforeach; ?>
        </nav>

        <article class="lot-phase-detail">
            <?php
            $selectedLabel = CultivationStage::label($selectedStage);
            $selectedStatus = $lote->phaseStatus($selectedStage);
            [$startField, $endField] = $dateFields[$selectedStage];
            ?>
            <div class="lot-phase-detail__main">
                <span class="material-symbols-outlined lot-phase-detail__icon" aria-hidden="true"><?= Html::escape($icons[$selectedStage]) ?></span>
                <div>
                    <span class="farmer-kicker">Fase seleccionada</span>
                    <h2><?= Html::escape($selectedLabel) ?></h2>
                    <p>Estado: <strong><?= Html::escape(CultivationStage::statusLabel($selectedStatus)) ?></strong></p>
                </div>
            </div>
            <div class="lot-phase-detail__dates">
                <span><small>Inicio</small><strong><?= Html::escape($lote->dates[$startField] ?? 'Sin registrar') ?></strong></span>
                <span><small>Finalización prevista</small><strong><?= Html::escape($lote->dates[$endField] ?? 'Sin registrar') ?></strong></span>
            </div>

            <footer class="lot-phase-detail__actions">
                <?php if ($lote->etapaActual === CultivationStage::NONE && $selectedStage === CultivationStage::PLANTING): ?>
                    <form method="post" action="<?= Html::escape(Url::route('/lotes/' . $lote->id . '/fases/avanzar')) ?>">
                        <input type="hidden" name="_token" value="<?= Html::escape($csrfToken) ?>">
                        <input type="hidden" name="fase" value="<?= CultivationStage::PLANTING ?>">
                        <button class="btn farmer-action-button farmer-action-button--primary" type="submit">Iniciar Siembra</button>
                    </form>
                <?php elseif ($selectedStage < $lote->etapaActual): ?>
                    <span><span class="material-symbols-outlined">visibility</span> Modo revisión: esta fase ya está completada.</span>
                <?php elseif ($selectedStage === $lote->etapaActual && $selectedStage < CultivationStage::HARVEST): ?>
                    <form method="post" action="<?= Html::escape(Url::route('/lotes/' . $lote->id . '/fases/avanzar')) ?>">
                        <input type="hidden" name="_token" value="<?= Html::escape($csrfToken) ?>">
                        <input type="hidden" name="fase" value="<?= $selectedStage ?>">
                        <button class="btn farmer-action-button farmer-action-button--primary" type="submit">
                            Completar <?= Html::escape($selectedLabel) ?> y avanzar
                        </button>
                    </form>
                <?php elseif ($selectedStage === CultivationStage::HARVEST && $selectedStatus === CultivationStage::STATUS_IN_PROGRESS): ?>
                    <a class="btn farmer-action-button farmer-action-button--primary" href="<?= Html::escape(Url::route('/dashboard/agricultor')) ?>">
                        Registrar producción para completar Cosecha
                    </a>
                <?php else: ?>
                    <span><span class="material-symbols-outlined">task_alt</span> Todas las fases están completadas.</span>
                <?php endif; ?>
            </footer>
        </article>
    </section>
</main>
<script src="js/lot-stages.js"></script>
</body>
</html>
