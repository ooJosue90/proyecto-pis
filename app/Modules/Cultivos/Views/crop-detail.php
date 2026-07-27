<?php
declare(strict_types=1);
use App\Shared\Helpers\Html;
if ($row === null) { echo "<div class='alert alert-danger'>No se encontraron detalles del cultivo.</div>"; return; }
$total = (int) ($row['total_lotes'] ?? 0);
$status = (int) ($row['lotes_en_cosecha'] ?? 0) > 0 ? ['harvest', 'En cosecha']
    : ($total > 0 && (int) ($row['lotes_finalizados'] ?? 0) === $total ? ['finished', 'Finalizado']
    : ($total > 0 && (int) ($row['lotes_cancelados'] ?? 0) === $total ? ['cancelled', 'Cancelado'] : ['active', 'Activo']));
$days = max(0, (int) floor((time() - strtotime((string) $row['fecha_siembra'])) / 86400));
?>
<div class="admin-crop-detail">
    <div class="admin-crop-detail__hero">
        <span class="admin-crop-detail__hero-icon"><i class="fas fa-seedling"></i></span>
        <div><small><?= Html::escape($row['tipo']) ?></small><h3><?= Html::escape($row['nombre']) ?></h3><p>Sembrado el <?= date('d/m/Y', strtotime((string) $row['fecha_siembra'])) ?></p></div>
        <span class="admin-crop-detail__status"><span class="admin-crop-status admin-crop-status--<?= $status[0] ?>"><i></i><?= $status[1] ?></span></span>
    </div>
    <div class="admin-crop-detail__grid">
        <article><span><i class="fas fa-hashtag"></i> Identificador</span><strong>#<?= (int) $row['id_cultivo'] ?></strong></article>
        <article><span><i class="fas fa-user"></i> Agricultor</span><strong><?= Html::escape($row['agricultor'] ?: 'No asignado') ?></strong></article>
        <article><span><i class="fas fa-calendar-days"></i> Fecha de siembra</span><strong><?= date('d/m/Y', strtotime((string) $row['fecha_siembra'])) ?></strong></article>
        <article><span><i class="fas fa-clock"></i> Ciclo transcurrido</span><strong><?= $days ?> días</strong></article>
        <article><span><i class="fas fa-map"></i> Lotes</span><strong><?= $total ?></strong></article>
        <?php if (!empty($row['ultima_cosecha_real'])): ?><article><span><i class="fas fa-calendar-check"></i> Última cosecha real</span><strong><?= date('d/m/Y', strtotime((string) $row['ultima_cosecha_real'])) ?></strong></article><?php endif; ?>
    </div>
    <div class="admin-crop-detail__description">
        <span>Cultivos asociados</span>
        <p><?= Html::escape($row['cultivos_asociados'] !== '' ? $row['cultivos_asociados'] : 'Sin cultivos asociados') ?></p>
    </div>
    <?php if (!empty($row['descripcion'])): ?><div class="admin-crop-detail__description"><span>Descripción</span><p><?= Html::escape($row['descripcion']) ?></p></div><?php endif; ?>
</div>
