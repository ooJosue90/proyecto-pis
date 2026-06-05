<?php

require_once 'conexion.php';
require_auth('Bodeguero');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('bodeguero.php');
}

$idSolicitud = (int) ($_POST['id_producto_solicitud'] ?? 0);
$accion = $_POST['accion'] ?? '';

if (!in_array($accion, ['entregar', 'cancelar'], true)) {
    flash('error_entrega', 'No tienes permisos para realizar esta acción.');
    redirect('bodeguero.php');
}

if ($idSolicitud <= 0) {
    flash('error_entrega', 'Solicitud inválida.');
    redirect('bodeguero.php');
}

$transactionStarted = false;

try {
    if ($accion === 'cancelar') {
        $actualizadas = db_execute(
            $conn,
            "UPDATE productos_solicitud
             SET estado = 'Cancelado'
             WHERE id_producto_solicitud = ? AND estado = 'Aprobado'",
            "i",
            [$idSolicitud]
        );

        if ($actualizadas === 0) {
            throw new RuntimeException('Acción no permitida para el estado actual de la solicitud.');
        }

        flash('mensaje', 'Solicitud cancelada correctamente.');
        redirect('bodeguero.php');
    }

    $transactionStarted = true;
    $conn->begin_transaction();

    $solicitud = db_fetch_one(
        $conn,
        "SELECT id_producto_solicitud, id_insumos, nombre, cantidad_solicitada
         FROM productos_solicitud
         WHERE id_producto_solicitud = ? AND estado = 'Aprobado'
         FOR UPDATE",
        "i",
        [$idSolicitud]
    );

    if (!$solicitud) {
        throw new RuntimeException('Acción no permitida para el estado actual de la solicitud.');
    }

    if (!empty($solicitud['id_insumos'])) {
        $insumo = db_fetch_one(
            $conn,
            "SELECT id_insumos, cantidad FROM insumos_agricolas WHERE id_insumos = ? FOR UPDATE",
            "i",
            [(int) $solicitud['id_insumos']]
        );
    } else {
        $insumo = db_fetch_one(
            $conn,
            "SELECT id_insumos, cantidad FROM insumos_agricolas WHERE nombre = ? LIMIT 1 FOR UPDATE",
            "s",
            [$solicitud['nombre']]
        );
    }

    if (!$insumo) {
        throw new RuntimeException('No se encontró el insumo correspondiente.');
    }

    $cantidadSolicitada = (float) $solicitud['cantidad_solicitada'];
    $stockDisponible = (float) $insumo['cantidad'];
    $idInsumo = (int) $insumo['id_insumos'];

    if ($cantidadSolicitada <= 0) {
        throw new RuntimeException('La cantidad solicitada no es válida.');
    }

    if ($cantidadSolicitada > $stockDisponible) {
        throw new RuntimeException("Cantidad solicitada ($cantidadSolicitada) mayor al stock disponible ($stockDisponible).");
    }

    db_execute(
        $conn,
        "UPDATE insumos_agricolas SET cantidad = cantidad - ? WHERE id_insumos = ?",
        "di",
        [$cantidadSolicitada, $idInsumo]
    );

    $solicitudesActualizadas = db_execute(
        $conn,
        "UPDATE productos_solicitud
         SET id_insumos = ?, estado = 'Entregado'
         WHERE id_producto_solicitud = ? AND estado = 'Aprobado'",
        "ii",
        [$idInsumo, $idSolicitud]
    );

    if ($solicitudesActualizadas !== 1) {
        throw new RuntimeException('Acción no permitida para el estado actual de la solicitud.');
    }

    db_execute(
        $conn,
        "INSERT INTO movimientos_insumos (
            id_insumo, id_usuario, id_producto_solicitud, tipo, estado,
            cantidad, cantidad_solicitada, cantidad_entregada, observaciones, fecha_movimiento
        ) VALUES (?, ?, ?, 'Salida', 'Salida', ?, ?, ?, 'Entrega a agricultor', NOW())",
        "isiddd",
        [$idInsumo, $_SESSION['id_usuario'], $idSolicitud, $cantidadSolicitada, $cantidadSolicitada, $cantidadSolicitada]
    );

    $conn->commit();
    flash('mensaje', 'Solicitud entregada correctamente.');
} catch (Throwable $exception) {
    if (!empty($transactionStarted)) {
        try {
            $conn->rollback();
        } catch (Throwable $rollbackException) {
            error_log('Rollback failed: ' . $rollbackException->getMessage());
        }
    }

    error_log('Error al procesar solicitud: ' . $exception->getMessage());
    flash('error_entrega', $exception->getMessage());
}

redirect('bodeguero.php');
