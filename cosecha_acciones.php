<?php
require_once 'conexion.php';
require_once __DIR__ . '/includes/cosecha_helpers.php';

cosecha_require_role(['Agricultor', 'Administrador', 'Bodeguero']);

$role = (string) $_SESSION['rol'];
$userId = (string) $_SESSION['id_usuario'];
$accion = (string) ($_POST['accion'] ?? '');

function cosecha_redirect_for_role(string $role): void
{
    redirect($role === 'Administrador' ? 'admin.php#cosechas' : 'cosechas.php');
}

if ($accion === 'guardar_cosecha') {
    if ($role !== 'Agricultor') {
        flash('error', 'Solo el agricultor puede registrar o editar cosechas.');
        cosecha_redirect_for_role($role);
    }

    $idCosecha = (int) ($_POST['id_cosecha'] ?? 0);
    $idLote = (int) ($_POST['id_lote'] ?? 0);
    $fechaCosecha = trim((string) ($_POST['fecha_cosecha'] ?? ''));
    $total = cosecha_float('cantidad_total_kg');
    $primera = cosecha_float('calidad_primera_kg');
    $segunda = cosecha_float('calidad_segunda_kg');
    $descarte = cosecha_float('descarte_kg');
    $observaciones = trim((string) ($_POST['observaciones'] ?? ''));

    if ($idLote <= 0 || !cosecha_user_owns_lote($conn, $userId, $idLote)) {
        flash('error', 'Seleccione un lote válido de su cuenta.');
        redirect('cosechas.php');
    }

    if (!cosecha_valid_date($fechaCosecha)) {
        flash('error', 'Ingrese una fecha de cosecha válida.');
        redirect('cosechas.php');
    }

    $amountError = cosecha_validate_amounts($total, $primera, $segunda, $descarte);
    if ($amountError !== null) {
        flash('error', $amountError);
        redirect('cosechas.php');
    }

    $conn->begin_transaction();

    try {
        if ($idCosecha > 0) {
            $current = db_fetch_one(
                $conn,
                "SELECT id_cosecha, id_lote
                 FROM cosechas
                 WHERE id_cosecha = ? AND id_usuario = ? AND estado = 'Registrada'
                 FOR UPDATE",
                "is",
                [$idCosecha, $userId]
            );

            if (!$current) {
                throw new RuntimeException('Solo puede editar cosechas propias en estado Registrada.');
            }

            if ((int) $current['id_lote'] !== $idLote) {
                throw new RuntimeException('No se puede cambiar el lote de una cosecha ya registrada.');
            }

            db_execute(
                $conn,
                "UPDATE cosechas
                 SET fecha_cosecha = ?, cantidad_total_kg = ?,
                     calidad_primera_kg = ?, calidad_segunda_kg = ?, descarte_kg = ?,
                     observaciones = ?
                 WHERE id_cosecha = ? AND id_usuario = ? AND estado = 'Registrada'",
                "sddddsis",
                [$fechaCosecha, $total, $primera, $segunda, $descarte, $observaciones === '' ? null : $observaciones, $idCosecha, $userId]
            );
            $conn->commit();
            flash('mensaje', 'Cosecha actualizada correctamente.');
        } else {
            $lote = db_fetch_one(
                $conn,
                "SELECT l.id_lote, l.estado_cultivo
                 FROM lotes l
                 INNER JOIN cultivos c ON l.id_cultivo = c.id_cultivo
                 WHERE l.id_lote = ? AND c.id_usuario = ?
                 FOR UPDATE",
                "is",
                [$idLote, $userId]
            );

            if (!$lote || $lote['estado_cultivo'] !== 'en_cosecha') {
                throw new RuntimeException('Solo puede registrar cosecha para lotes que estén en etapa En cosecha.');
            }

            $alreadyRegistered = (int) db_value(
                $conn,
                "SELECT COUNT(*)
                 FROM cosechas
                 WHERE id_lote = ?
                   AND estado IN ('Registrada', 'Validada', 'Recibida')",
                "i",
                [$idLote],
                0
            );

            if ($alreadyRegistered > 0) {
                throw new RuntimeException('Este lote ya tiene una cosecha activa registrada.');
            }

            db_execute(
                $conn,
                "INSERT INTO cosechas (
                    id_lote, id_usuario, fecha_cosecha, cantidad_total_kg,
                    calidad_primera_kg, calidad_segunda_kg, descarte_kg, estado, observaciones
                 ) VALUES (?, ?, ?, ?, ?, ?, ?, 'Registrada', ?)",
                "issdddds",
                [$idLote, $userId, $fechaCosecha, $total, $primera, $segunda, $descarte, $observaciones === '' ? null : $observaciones]
            );

            db_execute(
                $conn,
                "UPDATE lotes
                 SET estado_cultivo = 'finalizado'
                 WHERE id_lote = ? AND estado_cultivo = 'en_cosecha'",
                "i",
                [$idLote]
            );

            cosecha_notify_role(
                $conn,
                'Administrador',
                'Nueva cosecha registrada por ' . current_user_name() . ' para validación.'
            );
            $conn->commit();
            flash('mensaje', 'Cosecha registrada correctamente. Queda pendiente de validación.');
        }
    } catch (Throwable $exception) {
        $conn->rollback();
        error_log('Error al guardar cosecha: ' . $exception->getMessage());
        flash('error', $exception instanceof RuntimeException ? $exception->getMessage() : 'No se pudo guardar la cosecha.');
    }

    redirect('cosechas.php');
}

if ($accion === 'validar_cosecha' || $accion === 'rechazar_cosecha') {
    if ($role !== 'Administrador') {
        flash('error', 'Solo el administrador puede validar o rechazar cosechas.');
        cosecha_redirect_for_role($role);
    }

    $idCosecha = (int) ($_POST['id_cosecha'] ?? 0);
    $newState = $accion === 'validar_cosecha' ? 'Validada' : 'Rechazada';
    $adminNotes = trim((string) ($_POST['observaciones_admin'] ?? ''));

    if ($idCosecha <= 0 || ($newState === 'Rechazada' && $adminNotes === '')) {
        flash('error', 'Complete los datos requeridos para cambiar el estado.');
        redirect('admin.php#cosechas');
    }

    $conn->begin_transaction();

    try {
        $cosecha = db_fetch_one(
            $conn,
            "SELECT co.id_cosecha, co.estado, co.cantidad_total_kg, u.nombre AS agricultor_nombre, l.ubicacion AS lote_ubicacion
             FROM cosechas co
             INNER JOIN usuarios u ON co.id_usuario = u.id_usuario
             INNER JOIN lotes l ON co.id_lote = l.id_lote
             WHERE co.id_cosecha = ?
             FOR UPDATE",
            "i",
            [$idCosecha]
        );

        if (!$cosecha || $cosecha['estado'] !== 'Registrada') {
            throw new RuntimeException('Solo se pueden procesar cosechas en estado Registrada.');
        }

        db_execute(
            $conn,
            "UPDATE cosechas
             SET estado = ?, observaciones_admin = ?, id_usuario_valida = ?, fecha_validacion = NOW()
             WHERE id_cosecha = ? AND estado = 'Registrada'",
            "sssi",
            [$newState, $adminNotes === '' ? null : $adminNotes, $userId, $idCosecha]
        );

        if ($newState === 'Validada') {
            cosecha_notify_role(
                $conn,
                'Bodeguero',
                'Cosecha validada para recepción: lote ' . $cosecha['lote_ubicacion'] . ', ' . number_format((float) $cosecha['cantidad_total_kg'], 2) . ' kg.'
            );
        }

        $conn->commit();
        flash('mensaje', 'Cosecha ' . strtolower($newState) . ' correctamente.');
    } catch (Throwable $exception) {
        $conn->rollback();
        error_log('Error al cambiar estado de cosecha: ' . $exception->getMessage());
        flash('error', $exception instanceof RuntimeException ? $exception->getMessage() : 'No se pudo cambiar el estado de la cosecha.');
    }

    redirect('admin.php#cosechas');
}

if ($accion === 'recibir_cosecha') {
    if ($role !== 'Bodeguero') {
        flash('error', 'Solo el bodeguero puede recibir cosechas validadas.');
        cosecha_redirect_for_role($role);
    }

    $idCosecha = (int) ($_POST['id_cosecha'] ?? 0);

    if ($idCosecha <= 0) {
        flash('error', 'Seleccione una cosecha válida.');
        redirect('cosechas.php');
    }

    $conn->begin_transaction();

    try {
        $cosecha = db_fetch_one(
            $conn,
            "SELECT co.id_cosecha, co.id_lote, co.id_usuario, co.estado,
                    co.cantidad_total_kg, co.calidad_primera_kg,
                    co.calidad_segunda_kg, co.descarte_kg, co.observaciones,
                    co.id_producto_final, c.tipo AS cultivo_tipo
             FROM cosechas co
             INNER JOIN lotes l ON co.id_lote = l.id_lote
             LEFT JOIN cultivos c ON l.id_cultivo = c.id_cultivo
             WHERE co.id_cosecha = ?
             FOR UPDATE",
            "i",
            [$idCosecha]
        );

        if (!$cosecha) {
            throw new RuntimeException('Seleccione una cosecha válida.');
        }

        if ($cosecha['estado'] === 'Recibida' && !empty($cosecha['id_producto_final'])) {
            $conn->commit();
            flash('mensaje', 'La cosecha ya estaba recibida y vinculada a producto final.');
            redirect('cosechas.php');
        }

        if ($cosecha['estado'] !== 'Validada') {
            throw new RuntimeException('Solo se pueden recibir cosechas validadas.');
        }

        $idProductoFinal = (int) ($cosecha['id_producto_final'] ?? 0);

        if ($idProductoFinal <= 0) {
            $nombreProducto = trim((string) ($cosecha['cultivo_tipo'] ?? ''));
            if ($nombreProducto === '') {
                $nombreProducto = 'Cosecha lote #' . (int) $cosecha['id_lote'];
            }

            $observacionProducto = 'Generado desde cosecha #' . (int) $cosecha['id_cosecha']
                . '. Primera: ' . number_format((float) $cosecha['calidad_primera_kg'], 2, '.', '') . ' kg'
                . ', Segunda: ' . number_format((float) $cosecha['calidad_segunda_kg'], 2, '.', '') . ' kg'
                . ', Descarte: ' . number_format((float) $cosecha['descarte_kg'], 2, '.', '') . ' kg.';

            if (!empty($cosecha['observaciones'])) {
                $observacionProducto .= ' Observaciones: ' . trim((string) $cosecha['observaciones']);
            }

            db_execute(
                $conn,
                "INSERT INTO productos_finales (
                    id_usuario, id_lote, nombre_producto, cantidad,
                    unidad_medida, observaciones, fecha
                 ) VALUES (?, ?, ?, ?, 'kg', ?, NOW())",
                "sisds",
                [
                    (string) $cosecha['id_usuario'],
                    (int) $cosecha['id_lote'],
                    $nombreProducto,
                    (float) $cosecha['cantidad_total_kg'],
                    $observacionProducto,
                ]
            );

            $idProductoFinal = (int) $conn->insert_id;
        }

        db_execute(
            $conn,
            "UPDATE cosechas
             SET estado = 'Recibida',
                 id_usuario_recibe = ?,
                 fecha_recepcion = NOW(),
                 id_producto_final = ?
             WHERE id_cosecha = ? AND estado = 'Validada'",
            "sii",
            [$userId, $idProductoFinal, $idCosecha]
        );

        $conn->commit();
        flash('mensaje', 'Cosecha marcada como recibida y vinculada a producto final correctamente.');
    } catch (Throwable $exception) {
        $conn->rollback();
        error_log('Error al recibir cosecha: ' . $exception->getMessage());
        flash('error', $exception instanceof RuntimeException ? $exception->getMessage() : 'No se pudo recibir la cosecha.');
    }

    redirect('cosechas.php');
}

flash('error', 'Acción no reconocida.');
cosecha_redirect_for_role($role);
