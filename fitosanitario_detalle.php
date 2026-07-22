<?php
require_once 'conexion.php';
require_once __DIR__ . '/includes/fitosanitario_helpers.php';
require_once __DIR__ . '/includes/fitosanitario_data.php';

fitosanitario_require_access();

$role = (string) ($_SESSION['rol'] ?? '');
$id_usuario = (string) ($_SESSION['id_usuario'] ?? '');
$idControl = (int) ($_GET['id'] ?? 0);

if ($idControl <= 0 || !fitosanitario_can_view($conn, $role, $id_usuario, $idControl)) {
    echo "<div class='alert alert-danger'>No tienes permisos para consultar este registro.</div>";
    exit;
}

$registro = fitosanitario_record($conn, $idControl);
if (!$registro) {
    echo "<div class='alert alert-danger'>Registro no encontrado.</div>";
    exit;
}

$tratamientos = fitosanitario_treatments($conn, $idControl);
$isWarehouse = $role === 'Bodeguero';

function fito_detail_number($value): string
{
    if ($value === null || $value === '') {
        return '-';
    }

    return rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
}

function fito_detail_unit(array $row): string
{
    $unidadDosis = $row['unidad_dosis'] ?: ($row['stock_unidad'] ?: 'unidades');
    $unidadAplicacion = $row['unidad_aplicacion'] ?: 'ha';

    return "{$unidadDosis}/{$unidadAplicacion}";
}

function fito_detail_quantity_unit(array $row): string
{
    return $row['unidad_dosis'] ?: ($row['stock_unidad'] ?: 'unidades');
}
?>

<div class="phytosanitary-detail">
    <section class="phytosanitary-detail__hero">
        <span class="phytosanitary-detail__icon"><i class="fas fa-shield-virus"></i></span>
        <div>
            <small>Lote #<?php echo (int) $registro['id_lote']; ?> - <?php echo e($registro['lote_ubicacion']); ?></small>
            <h3><?php echo $isWarehouse ? 'Producto fitosanitario utilizado' : e($registro['nombre_problema']); ?></h3>
            <?php if (!$isWarehouse): ?>
                <p><?php echo e($registro['descripcion']); ?></p>
            <?php endif; ?>
        </div>
        <span class="app-table-status-capsule app-table-status-capsule--<?php echo e(fitosanitario_status_tone($registro['estado'])); ?>">
            <?php echo e($registro['estado']); ?>
        </span>
    </section>

    <section class="phytosanitary-detail__grid">
        <?php if (!$isWarehouse): ?>
            <article>
                <span><i class="fas fa-bug"></i> Tipo</span>
                <strong><?php echo e($registro['tipo']); ?></strong>
            </article>
            <article>
                <span><i class="fas fa-triangle-exclamation"></i> Severidad</span>
                <strong><?php echo e($registro['severidad']); ?></strong>
            </article>
            <article>
                <span><i class="fas fa-user"></i> Responsable</span>
                <strong><?php echo e($registro['usuario_responsable'] ?: 'Sin usuario'); ?></strong>
            </article>
            <article>
                <span><i class="fas fa-calendar-days"></i> Detección</span>
                <strong><?php echo date('d/m/Y', strtotime($registro['fecha_deteccion'])); ?></strong>
            </article>
        <?php endif; ?>
        <article>
            <span><i class="fas fa-flask-vial"></i> Producto aplicado</span>
            <strong><?php echo e($registro['producto_aplicado'] ?: 'Sin producto registrado'); ?></strong>
            <?php if ($registro['stock_disponible'] !== null): ?>
                <small>Stock disponible: <?php echo e($registro['stock_disponible']); ?> <?php echo e($registro['stock_unidad'] ?: 'unidades'); ?></small>
            <?php endif; ?>
        </article>
        <article>
            <span><i class="fas fa-prescription-bottle"></i> Dosis</span>
            <strong><?php echo e($registro['dosis'] ?: '-'); ?></strong>
        </article>
        <article>
            <span><i class="fas fa-calendar-check"></i> Fecha aplicación</span>
            <strong><?php echo $registro['fecha_aplicacion'] ? date('d/m/Y', strtotime($registro['fecha_aplicacion'])) : '-'; ?></strong>
        </article>
        <article>
            <span><i class="fas fa-seedling"></i> Cultivo</span>
            <strong><?php echo e($registro['cultivo_tipo'] ?: 'Sin cultivo'); ?></strong>
        </article>
    </section>

    <?php if (!$isWarehouse && !empty($registro['observaciones'])): ?>
        <section class="phytosanitary-detail__notes">
            <span>Observaciones</span>
            <p><?php echo e($registro['observaciones']); ?></p>
        </section>
    <?php endif; ?>

    <section class="phytosanitary-timeline">
        <div class="phytosanitary-timeline__heading">
            <h4>Historial de tratamientos</h4>
            <span><?php echo count($tratamientos); ?> registros</span>
        </div>

        <?php if (!$tratamientos): ?>
            <div class="app-empty-state">Aún no hay tratamientos registrados.</div>
        <?php endif; ?>

        <?php foreach ($tratamientos as $tratamiento): ?>
            <?php
                $unidadDosis = fito_detail_unit($tratamiento);
                $unidadCantidad = fito_detail_quantity_unit($tratamiento);
                $dosisRecomendada = $tratamiento['dosis_recomendada'] ?? null;
                $dosisAplicada = $tratamiento['dosis_aplicada'] ?? null;
                $cantidadSugerida = $tratamiento['cantidad_sugerida'] ?? null;
                $cantidadAplicada = $tratamiento['cantidad_aplicada'] ?? ($tratamiento['cantidad_solicitada'] ?? null);
            ?>
            <article class="phytosanitary-timeline__item">
                <span class="phytosanitary-timeline__marker"><i class="fas fa-flask"></i></span>
                <div>
                    <div class="phytosanitary-timeline__top">
                        <strong><?php echo e($tratamiento['producto_aplicado']); ?></strong>
                        <span class="app-table-status-capsule app-table-status-capsule--<?php echo e(fitosanitario_status_tone($tratamiento['estado_resultante'])); ?>">
                            <?php echo e($tratamiento['estado_resultante']); ?>
                        </span>
                    </div>
                    <div class="phytosanitary-treatment-summary">
                        <span><strong>Dosis recomendada:</strong> <?php echo e(fito_detail_number($dosisRecomendada)); ?> <?php echo e($unidadDosis); ?></span>
                        <span><strong>Dosis aplicada:</strong> <?php echo e(fito_detail_number($dosisAplicada)); ?> <?php echo e($unidadDosis); ?></span>
                        <span><strong>Cantidad sugerida:</strong> <?php echo e(fito_detail_number($cantidadSugerida)); ?> <?php echo e($unidadCantidad); ?></span>
                        <span><strong>Cantidad para entrega:</strong> <?php echo e(fito_detail_number($cantidadAplicada)); ?> <?php echo e($unidadCantidad); ?></span>
                    </div>
                    <p>Aplicación: <?php echo date('d/m/Y', strtotime($tratamiento['fecha_aplicacion'])); ?></p>
                    <?php if (!empty($tratamiento['motivo_ajuste'])): ?>
                        <p class="phytosanitary-timeline__notes">
                            <strong>Motivo del ajuste:</strong> <?php echo e($tratamiento['motivo_ajuste']); ?>
                        </p>
                    <?php endif; ?>
                    <p>
                        Entrega:
                        <span class="app-table-status-capsule app-table-status-capsule--<?php
                            echo $tratamiento['estado_entrega'] === 'Entregado'
                                ? 'success'
                                : ($tratamiento['estado_entrega'] === 'Aprobado' ? 'info' : 'warning');
                        ?>">
                            <?php echo e($tratamiento['estado_entrega']); ?>
                        </span>
                        <?php if ($tratamiento['cantidad_entregada'] !== null): ?>
                            · Entregado: <?php echo e($tratamiento['cantidad_entregada']); ?> <?php echo e($tratamiento['stock_unidad'] ?: 'unidades'); ?>
                        <?php endif; ?>
                    </p>
                    <?php if ($tratamiento['stock_disponible'] !== null): ?>
                        <small>Stock disponible actual: <?php echo e($tratamiento['stock_disponible']); ?> <?php echo e($tratamiento['stock_unidad'] ?: 'unidades'); ?></small>
                    <?php endif; ?>
                    <?php if ($role === 'Administrador' && $tratamiento['estado_entrega'] === 'Pendiente' && !empty($tratamiento['id_insumo']) && (float) $tratamiento['cantidad_solicitada'] > 0): ?>
                        <form class="phytosanitary-inline-action" data-fito-ajax-form>
                            <input type="hidden" name="accion" value="aprobar_tratamiento">
                            <input type="hidden" name="id_tratamiento" value="<?php echo (int) $tratamiento['id_tratamiento']; ?>">
                            <button type="submit" class="btn btn-sm btn-outline-primary" data-skip-loading="1">
                                <i class="fas fa-check"></i>
                                Aprobar para bodega
                            </button>
                        </form>
                    <?php endif; ?>
                    <?php if ($role === 'Bodeguero' && $tratamiento['estado_entrega'] === 'Aprobado'): ?>
                        <form class="phytosanitary-inline-action" data-fito-ajax-form>
                            <input type="hidden" name="accion" value="entregar_tratamiento">
                            <input type="hidden" name="id_tratamiento" value="<?php echo (int) $tratamiento['id_tratamiento']; ?>">
                            <button type="submit" class="btn btn-sm btn-outline-success" data-skip-loading="1">
                                <i class="fas fa-box-open"></i>
                                Entregar y descontar stock
                            </button>
                        </form>
                    <?php endif; ?>
                    <?php if (!$isWarehouse): ?>
                        <small>
                            Registrado por <?php echo e($tratamiento['usuario_responsable'] ?: 'Usuario'); ?>
                            el <?php echo date('d/m/Y H:i', strtotime($tratamiento['fecha_registro'])); ?>
                        </small>
                        <?php if (!empty($tratamiento['usuario_aprobacion'])): ?>
                            <small>Aprobado por <?php echo e($tratamiento['usuario_aprobacion']); ?> el <?php echo date('d/m/Y H:i', strtotime($tratamiento['fecha_aprobacion'])); ?></small>
                        <?php endif; ?>
                        <?php if (!empty($tratamiento['usuario_entrega'])): ?>
                            <small>Entregado por <?php echo e($tratamiento['usuario_entrega']); ?> el <?php echo date('d/m/Y H:i', strtotime($tratamiento['fecha_entrega'])); ?></small>
                        <?php endif; ?>
                        <?php if (!empty($tratamiento['observaciones'])): ?>
                            <p class="phytosanitary-timeline__notes"><?php echo e($tratamiento['observaciones']); ?></p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </section>
</div>
