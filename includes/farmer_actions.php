<?php

// Finalizar cosecha y registrar la producción real con el esquema de main.
if (($_POST['accion'] ?? '') === 'finalizar_cosecha') {
    $id_lote = (int) ($_POST['id_lote'] ?? 0);
    $cantidad_cosechada = (float) ($_POST['cantidad_cosechada'] ?? 0);
    $unidad_cosecha = trim($_POST['unidad_cosecha'] ?? '');
    $fecha_cosecha = post_date_or_null('fecha_cosecha');
    $observacion = trim($_POST['observacion'] ?? '');

    if ($id_lote <= 0 || !user_owns_lote($conn, $id_usuario, $id_lote)) {
        flash('error', 'Seleccione un lote válido.');
        redirect('agricultor.php');
    }

    if ($cantidad_cosechada <= 0) {
        flash('error', 'La cantidad cosechada debe ser mayor que cero.');
        redirect('agricultor.php');
    }

    if ($unidad_cosecha === '' || $fecha_cosecha === null || !valid_date_or_null($fecha_cosecha)) {
        flash('error', 'Complete la unidad y la fecha real de cosecha.');
        redirect('agricultor.php');
    }

    $conn->begin_transaction();

    try {
        $lote = db_fetch_one(
            $conn,
            "SELECT l.id_lote, l.estado_cultivo, c.tipo
             FROM lotes l
             INNER JOIN cultivos c ON l.id_cultivo = c.id_cultivo
             WHERE l.id_lote = ? AND c.id_usuario = ?
             FOR UPDATE",
            "is",
            [$id_lote, $id_usuario]
        );

        if (!$lote || $lote['estado_cultivo'] !== 'en_cosecha') {
            throw new RuntimeException('El lote ya no está disponible para finalizar su cosecha.');
        }

        db_execute(
            $conn,
            "INSERT INTO productos_finales (
                id_usuario, id_lote, nombre_producto, cantidad,
                unidad_medida, observaciones, fecha
            ) VALUES (?, ?, ?, ?, ?, ?, ?)",
            "sisdsss",
            [
                $id_usuario,
                $id_lote,
                $lote['tipo'],
                $cantidad_cosechada,
                $unidad_cosecha,
                $observacion === '' ? null : $observacion,
                $fecha_cosecha . ' 00:00:00',
            ]
        );

        db_execute(
            $conn,
            "UPDATE lotes
             SET estado_cultivo = 'finalizado',
                 fecha_fin_cosecha_real = ?
             WHERE id_lote = ? AND estado_cultivo = 'en_cosecha'",
            "si",
            [$fecha_cosecha, $id_lote]
        );

        $conn->commit();
        flash('mensaje', 'Cosecha finalizada y producción registrada correctamente.');
    } catch (Throwable $exception) {
        $conn->rollback();
        error_log('Error al finalizar cosecha: ' . $exception->getMessage());
        flash('error', $exception instanceof RuntimeException
            ? $exception->getMessage()
            : 'No se pudo finalizar la cosecha.');
    }

    redirect('agricultor.php');
}

// Registrar cultivo
if (($_POST['accion'] ?? '') === 'registrar_cultivo' || isset($_POST['registrar_cultivo'])) {
    $tipo = trim($_POST['tipo'] ?? '');
    $fecha_siembra = post_date_or_null('fecha_siembra');

    if ($tipo === '' || $fecha_siembra === null || !valid_date_or_null($fecha_siembra)) {
        flash('error', 'Complete los datos del cultivo.');
        redirect('agricultor.php');
    }

    try {
        db_execute(
            $conn,
            "INSERT INTO cultivos (id_usuario, tipo, fecha_siembra) VALUES (?, ?, ?)",
            "sss",
            [$id_usuario, $tipo, $fecha_siembra]
        );
        flash('mensaje', 'Cultivo registrado correctamente. Ya puede asociarlo a un lote.');
    } catch (Throwable $exception) {
        error_log('Error al registrar cultivo: ' . $exception->getMessage());
        flash('error', 'No se pudo registrar el cultivo.');
    }

    redirect('agricultor.php?tab=lote');
}

// Registrar lote
if (($_POST['accion'] ?? '') === 'registrar_lote' || isset($_POST['registrar_lote'])) {
    $id_cultivo = (int) ($_POST['id_cultivo'] ?? 0);
    $ubicacion = trim($_POST['ubicacion'] ?? '');
    $area = (float) ($_POST['area'] ?? 0);
    $etapa_siembra = isset($_POST['etapa_siembra']) ? 1 : 0;
    $etapa_riego = isset($_POST['etapa_riego']) ? 1 : 0;
    $etapa_cosecha = isset($_POST['etapa_cosecha']) ? 1 : 0;
    $etapa_actual = $etapa_cosecha === 1
        ? 3
        : ($etapa_riego === 1 ? 2 : ($etapa_siembra === 1 ? 1 : 0));
    $estado_cultivo = $etapa_actual === 3 ? 'en_cosecha' : 'activo';
    $fecha_inicio_riego = post_date_or_null('fecha_inicio_riego');
    $fecha_fin_riego = post_date_or_null('fecha_fin_riego');
    $fecha_inicio_siembra = post_date_or_null('fecha_inicio_siembra');
    $fecha_fin_siembra = post_date_or_null('fecha_fin_siembra');
    $fecha_inicio_cosecha = post_date_or_null('fecha_inicio_cosecha');
    $fecha_fin_cosecha = post_date_or_null('fecha_fin_cosecha');
    if ($id_cultivo <= 0) {
        flash('error', 'Seleccione el cultivo que desea asociar al lote.');
        redirect('agricultor.php?tab=lote');
    }

    if (!user_owns_cultivo($conn, $id_usuario, $id_cultivo)) {
        flash('error', 'El cultivo seleccionado no pertenece a su cuenta.');
        redirect('agricultor.php?tab=lote');
    }

    if ($ubicacion === '') {
        flash('error', 'Ingrese la ubicación del lote.');
        redirect('agricultor.php?tab=lote');
    }

    if ($area <= 0) {
        flash('error', 'Ingrese un área válida mayor que cero.');
        redirect('agricultor.php?tab=lote');
    }

    if (!valid_date_range($fecha_inicio_riego, $fecha_fin_riego)) {
        flash('error', 'Revise las fechas de riego: la fecha final no puede ser anterior a la inicial.');
        redirect('agricultor.php?tab=lote');
    }

    if (!valid_date_range($fecha_inicio_siembra, $fecha_fin_siembra)) {
        flash('error', 'Revise las fechas de siembra: la fecha final no puede ser anterior a la inicial.');
        redirect('agricultor.php?tab=lote');
    }

    if (!valid_date_range($fecha_inicio_cosecha, $fecha_fin_cosecha)) {
        flash('error', 'Revise las fechas de cosecha: la fecha final no puede ser anterior a la inicial.');
        redirect('agricultor.php?tab=lote');
    }

    try {
        db_execute(
            $conn,
            "INSERT INTO lotes (
                id_cultivo, ubicacion, area, etapa_actual, estado_cultivo,
                etapa_riego, etapa_siembra, etapa_cosecha,
                fecha_inicio_riego, fecha_fin_riego, fecha_inicio_siembra, fecha_fin_siembra,
                fecha_inicio_cosecha, fecha_fin_cosecha, fecha_registro
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())",
            "isdisiiissssss",
            [
                $id_cultivo,
                $ubicacion,
                $area,
                $etapa_actual,
                $estado_cultivo,
                $etapa_riego,
                $etapa_siembra,
                $etapa_cosecha,
                $fecha_inicio_riego,
                $fecha_fin_riego,
                $fecha_inicio_siembra,
                $fecha_fin_siembra,
                $fecha_inicio_cosecha,
                $fecha_fin_cosecha,
            ]
        );
        flash('mensaje', 'Lote registrado correctamente.');
    } catch (Throwable $exception) {
        error_log('Error al registrar lote: ' . $exception->getMessage());
        flash('error', 'No se pudo registrar el lote.');
    }

    redirect('agricultor.php?tab=lote');
}

// Registrar solicitud manual desde el dashboard
if (($_POST['accion'] ?? '') === 'solicitar_insumos_manual' || isset($_POST['solicitar_insumos_manual'])) {
    $id_lote = (int) ($_POST['id_lote'] ?? 0);
    $hectareas = (float) ($_POST['hectareas'] ?? 0);
    $observaciones = trim($_POST['observaciones'] ?? '');
    $productos = is_array($_POST['productos'] ?? null) ? $_POST['productos'] : [];

    if ($id_lote <= 0 || !user_owns_lote($conn, $id_usuario, $id_lote)) {
        flash('error', 'Seleccione un lote válido.');
        redirect('agricultor.php?tab=insumos');
    }

    $area_lote = (float) db_value(
        $conn,
        "SELECT area FROM lotes WHERE id_lote = ?",
        "i",
        [$id_lote],
        0
    );

    if ($hectareas <= 0 || $area_lote <= 0 || $hectareas > $area_lote) {
        flash('error', 'Las hectáreas deben ser mayores que cero y no superar el área del lote.');
        redirect('agricultor.php?tab=insumos');
    }

    if (empty($productos)) {
        flash('error', 'Agregue al menos un insumo a la solicitud.');
        redirect('agricultor.php?tab=insumos');
    }

    $conn->begin_transaction();

    try {
        $registrados = 0;

        foreach ($productos as $producto) {
            $id_insumo = (int) ($producto['id_insumo'] ?? 0);
            $cantidad_por_hectarea = (float) ($producto['cantidad'] ?? 0);

            if ($id_insumo <= 0 || $cantidad_por_hectarea <= 0) {
                continue;
            }

            $insumo = db_fetch_one(
                $conn,
                "SELECT id_insumos, nombre, tipo FROM insumos_agricolas WHERE id_insumos = ?",
                "i",
                [$id_insumo]
            );

            if (!$insumo) {
                continue;
            }

            $cantidad_total = $cantidad_por_hectarea * $hectareas;
            db_execute(
                $conn,
                "INSERT INTO productos_solicitud (
                    id_agricultor, id_lote, id_insumos, etapa, nombre, cantidad_solicitada, observaciones, fecha, estado
                ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), 'Pendiente')",
                "siissds",
                [
                    $id_usuario,
                    $id_lote,
                    $id_insumo,
                    $insumo['tipo'],
                    $insumo['nombre'],
                    $cantidad_total,
                    $observaciones,
                ]
            );
            $registrados++;
        }

        if ($registrados === 0) {
            throw new RuntimeException('No se registró ningún insumo válido.');
        }

        $conn->commit();
        flash('mensaje', "Solicitud enviada correctamente con $registrados insumos.");
    } catch (Throwable $exception) {
        $conn->rollback();
        error_log('Error al registrar solicitud desde dashboard: ' . $exception->getMessage());
        flash('error', 'No se pudo registrar la solicitud de insumos.');
    }

    redirect('agricultor.php?tab=insumos');
}

// Registrar solicitud automática basada en insumos calculados
if (isset($_POST['solicitar_insumos_automatico'])) {
    $id_lote = (int) ($_POST['id_lote'] ?? 0);
    $observaciones = trim($_POST['observaciones'] ?? '');

    // Obtener insumos calculados para el lote
    $insumos_json = $_POST['insumos_json'] ?? '[]';
    $insumos = json_decode($insumos_json, true);

    if ($id_lote <= 0 || !is_array($insumos) || empty($insumos) || !user_owns_lote($conn, $id_usuario, $id_lote)) {
        flash('error', 'No se pudo registrar la solicitud automática.');
        redirect('agricultor.php');
    }

    $conn->begin_transaction();

    try {
        $registrados = 0;
        foreach ($insumos as $insumo) {
            $nombre = trim($insumo['nombre'] ?? '');
            $cantidad = (float) ($insumo['cantidad_total'] ?? 0);

            if ($nombre === '' || $cantidad <= 0) {
                continue;
            }

            $insumoDb = db_fetch_one(
                $conn,
                "SELECT id_insumos, tipo FROM insumos_agricolas WHERE nombre = ? LIMIT 1",
                "s",
                [$nombre]
            );

            if (!$insumoDb) {
                continue;
            }

            db_execute(
                $conn,
                "INSERT INTO productos_solicitud (
                    id_agricultor, id_lote, id_insumos, etapa, nombre, cantidad_solicitada, observaciones, fecha, estado
                ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), 'Pendiente')",
                "siissds",
                [$id_usuario, $id_lote, (int) $insumoDb['id_insumos'], $insumoDb['tipo'], $nombre, $cantidad, $observaciones]
            );
            $registrados++;
        }

        if ($registrados === 0) {
            throw new RuntimeException('No se encontró ningún insumo válido.');
        }

        $conn->commit();
        flash('mensaje', "Solicitud automática registrada correctamente con $registrados insumos.");
    } catch (Throwable $exception) {
        $conn->rollback();
        error_log('Error al registrar solicitud automática: ' . $exception->getMessage());
        flash('error', 'No se pudo registrar la solicitud automática.');
    }

    redirect('agricultor.php');
}
