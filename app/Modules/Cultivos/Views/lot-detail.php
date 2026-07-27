<?php
declare(strict_types=1);
use App\Shared\Domain\CultivationStage;
use App\Shared\Helpers\Html;
if ($row === null) { echo "<div class='alert alert-danger'>No se encontraron detalles del lote.</div>"; return; }
$stage = CultivationStage::label((int) ($row['etapa_actual'] ?? CultivationStage::NONE));
$status = match ((string) ($row['estado_cultivo'] ?? '')) { 'en_cosecha' => 'En cosecha', 'finalizado' => 'Finalizado', 'cancelado' => 'Cancelado', default => 'Activo' };
?>
<div class="admin-crop-detail">
    <div class="admin-crop-detail__hero admin-crop-detail__hero--lot">
        <span class="admin-crop-detail__hero-icon"><i class="fas fa-map-location-dot"></i></span>
        <div><small>Lote registrado</small><h3><?= Html::escape($row['ubicacion']) ?></h3><p><?= Html::escape($row['area']) ?></p></div>
        <span class="admin-crop-detail__lot-badge">Lote #<?= (int) $row['id_lote'] ?></span>
    </div>
    <div class="admin-crop-detail__grid">
        <article><span><i class="fas fa-location-dot"></i> Ubicación</span><strong><?= Html::escape($row['ubicacion']) ?></strong></article>
        <article><span><i class="fas fa-ruler-combined"></i> Área / zona</span><strong><?= Html::escape($row['area']) ?></strong></article>
        <article><span><i class="fas fa-seedling"></i> Cultivo</span><strong><?= Html::escape($row['cultivo'] ?: 'Sin cultivo') ?></strong></article>
        <article><span><i class="fas fa-user"></i> Agricultor</span><strong><?= Html::escape($row['agricultor'] ?: 'No asignado') ?></strong></article>
        <article><span><i class="fas fa-list-check"></i> Etapa actual</span><strong><?= Html::escape($stage) ?></strong></article>
        <article><span><i class="fas fa-circle-info"></i> Estado</span><strong><?= Html::escape($status) ?></strong></article>
        <?php if (!empty($row['fecha_siembra'])): ?><article><span><i class="fas fa-calendar-days"></i> Fecha de siembra</span><strong><?= date('d/m/Y', strtotime((string) $row['fecha_siembra'])) ?></strong></article><?php endif; ?>
        <?php if (!empty($row['fecha_fin_cosecha_real'])): ?><article><span><i class="fas fa-calendar-check"></i> Cosecha finalizada</span><strong><?= date('d/m/Y', strtotime((string) $row['fecha_fin_cosecha_real'])) ?></strong></article><?php endif; ?>
    </div>
</div>
