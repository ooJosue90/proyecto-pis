<?php
declare(strict_types=1);
use App\Shared\Helpers\Html;
$tone = static function (string $status): string {
    $value = function_exists('mb_strtolower') ? mb_strtolower(trim($status), 'UTF-8') : strtolower(trim($status));
    return preg_match('/pendiente|espera|revisi[oó]n|cosecha/', $value) ? 'warning'
        : (preg_match('/aprobado|procesando|informaci[oó]n/', $value) ? 'info'
        : (preg_match('/entregado|activo|finalizado|completado/', $value) ? 'success'
        : (preg_match('/rechazado|error|cr[ií]tico/', $value) ? 'danger'
        : (preg_match('/cancelado|inactivo/', $value) ? 'neutral' : 'default'))));
};
if ($rows === []) { echo "<div class='alert alert-info'>No hay historial para este lote.</div>"; return; }
?>
<div class="table-responsive admin-lot-history-table-wrap"><table class="table admin-lot-history-table" data-app-table-owner="historialLoteContent">
<thead><tr><th>ID</th><th>Agricultor</th><th>Producto</th><th>Cantidad</th><th>Estado</th><th>Fecha</th></tr></thead><tbody>
<?php foreach ($rows as $row): $status = trim((string) ($row['estado'] ?? 'Sin estado')); ?>
<tr><td><?= (int) $row['id_producto_solicitud'] ?></td><td><?= Html::escape($row['agricultor_nombre']) ?></td><td><?= Html::escape($row['nombre']) ?></td><td><?= Html::escape($row['cantidad_solicitada']) ?></td><td><span class="app-table-status-capsule app-table-status-capsule--<?= $tone($status) ?>"><?= Html::escape($status) ?></span></td><td><?= Html::escape($row['fecha']) ?></td></tr>
<?php endforeach; ?>
</tbody></table></div>
