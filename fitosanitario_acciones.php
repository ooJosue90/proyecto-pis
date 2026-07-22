<?php
require_once 'conexion.php';
require_once __DIR__ . '/includes/fitosanitario_helpers.php';

fitosanitario_require_access();

function fito_wants_json(): bool
{
    return str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
        || strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch'
        || isset($_POST['ajax']);
}

function fito_finish(bool $success, string $message, string $redirect = 'fitosanitario.php'): void
{
    if (fito_wants_json()) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => $success, 'message' => $message], JSON_UNESCAPED_UNICODE);
        exit;
    }

    flash($success ? 'mensaje' : 'error', $message);
    redirect($redirect);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    fito_finish(false, 'Método no permitido.');
}

$role = (string) ($_SESSION['rol'] ?? '');
$id_usuario = (string) ($_SESSION['id_usuario'] ?? '');
$accion = (string) ($_POST['accion'] ?? '');

if ($role === 'Bodeguero' && $accion !== 'entregar_tratamiento') {
    fito_finish(false, 'El rol Bodeguero solo puede visualizar registros fitosanitarios.');
}

function fito_read_record_payload(): array
{
    return [
        'id_lote' => (int) ($_POST['id_lote'] ?? 0),
        'id_insumo' => (int) ($_POST['id_insumo'] ?? 0),
        'tipo' => trim((string) ($_POST['tipo'] ?? '')),
        'nombre_problema' => trim((string) ($_POST['nombre_problema'] ?? '')),
        'severidad' => trim((string) ($_POST['severidad'] ?? '')),
        'descripcion' => trim((string) ($_POST['descripcion'] ?? '')),
        'producto_aplicado' => '',
        'dosis_aplicada' => (float) ($_POST['dosis_aplicada'] ?? 0),
        'cantidad_aplicada' => (float) ($_POST['cantidad_aplicada'] ?? 0),
        'motivo_ajuste' => trim((string) ($_POST['motivo_ajuste'] ?? '')),
        'fecha_deteccion' => post_date_or_null('fecha_deteccion'),
        'fecha_aplicacion' => post_date_or_null('fecha_aplicacion'),
        'observaciones' => trim((string) ($_POST['observaciones'] ?? '')),
    ];
}

function fito_validate_record_payload(array $data, bool $validateInitialTreatment = true): ?string
{
    if ($data['id_lote'] <= 0) {
        return 'Seleccione un lote válido.';
    }

    if (!fitosanitario_is_valid_tipo($data['tipo'])) {
        return 'Seleccione un tipo fitosanitario válido.';
    }

    if ($data['nombre_problema'] === '') {
        return 'Ingrese el nombre del problema.';
    }

    if (!fitosanitario_is_valid_severidad($data['severidad'])) {
        return 'Seleccione un nivel de severidad válido.';
    }

    if ($data['descripcion'] === '') {
        return 'Ingrese una descripción del problema.';
    }

    if ($data['fecha_deteccion'] === null || !valid_date_or_null($data['fecha_deteccion'])) {
        return 'Ingrese una fecha de detección válida.';
    }

    if (!valid_date_or_null($data['fecha_aplicacion'])) {
        return 'Ingrese una fecha de aplicación válida.';
    }

    if ($validateInitialTreatment) {
        $treatmentFields = [$data['id_insumo'] > 0, $data['dosis_aplicada'] > 0, $data['cantidad_aplicada'] > 0, $data['fecha_aplicacion'] !== null];
        if (in_array(true, $treatmentFields, true) && in_array(false, $treatmentFields, true)) {
            return 'Para registrar un tratamiento inicial complete producto, dosis aplicada, cantidad y fecha de aplicación.';
        }
    }

    return null;
}

function fito_lote_area(mysqli $conn, int $idLote): float
{
    return (float) db_value($conn, "SELECT area FROM lotes WHERE id_lote = ?", "i", [$idLote], 0);
}

function fito_control_lote_area(mysqli $conn, int $idControl): float
{
    return (float) db_value(
        $conn,
        "SELECT l.area
         FROM control_fitosanitario cf
         INNER JOIN lotes l ON cf.id_lote = l.id_lote
         WHERE cf.id_control = ?",
        "i",
        [$idControl],
        0
    );
}

function fito_prepare_dose_data(array $insumo, float $area, float $dosisAplicada, float $cantidadAplicada, string $motivoAjuste): array
{
    $dosisRecomendada = (float) ($insumo['dosis_recomendada'] ?? 0);
    if ($area <= 0) {
        throw new RuntimeException('No es posible calcular la cantidad porque el lote no tiene superficie registrada.');
    }
    if ($dosisRecomendada <= 0) {
        throw new RuntimeException('Este producto no tiene una dosis recomendada configurada.');
    }
    if ($dosisAplicada <= 0) {
        throw new RuntimeException('La dosis aplicada debe ser mayor que cero.');
    }
    if ($cantidadAplicada <= 0) {
        throw new RuntimeException('La cantidad aplicada debe ser mayor que cero.');
    }

    $cantidadSugerida = round($area * $dosisRecomendada, 2);
    if ($cantidadSugerida <= 0) {
        throw new RuntimeException('La cantidad sugerida debe ser mayor que cero.');
    }

    if (abs($dosisAplicada - $dosisRecomendada) > 0.0001 && $motivoAjuste === '') {
        throw new RuntimeException('Ingrese el motivo del ajuste cuando la dosis aplicada sea diferente a la recomendada.');
    }

    $unidadDosis = $insumo['unidad_dosis'] ?: ($insumo['unidad_medida'] ?: 'unidades');
    $unidadAplicacion = $insumo['unidad_aplicacion'] ?: 'ha';

    return [
        'dosis_recomendada' => $dosisRecomendada,
        'dosis_aplicada' => $dosisAplicada,
        'unidad_dosis' => $unidadDosis,
        'unidad_aplicacion' => $unidadAplicacion,
        'cantidad_sugerida' => $cantidadSugerida,
        'cantidad_aplicada' => round($cantidadAplicada, 2),
        'motivo_ajuste' => $motivoAjuste === '' ? null : $motivoAjuste,
        'dosis_texto' => rtrim(rtrim(number_format($dosisAplicada, 2, '.', ''), '0'), '.') . " {$unidadDosis}/{$unidadAplicacion}",
    ];
}

function fito_treatment_for_delivery(mysqli $conn, int $idTratamiento, bool $lock = false): ?array
{
    $sql = "SELECT cft.*, cf.id_lote, cf.nombre_problema, l.ubicacion AS lote_ubicacion,
                   ia.nombre AS insumo_nombre, ia.cantidad AS stock_disponible, ia.unidad_medida AS stock_unidad
            FROM control_fitosanitario_tratamientos cft
            INNER JOIN control_fitosanitario cf ON cft.id_control = cf.id_control
            INNER JOIN lotes l ON cf.id_lote = l.id_lote
            INNER JOIN insumos_agricolas ia ON cft.id_insumo = ia.id_insumos
            WHERE cft.id_tratamiento = ?";

    if ($lock) {
        $sql .= " FOR UPDATE";
    }

    return db_fetch_one($conn, $sql, "i", [$idTratamiento]);
}

function fito_delivery_quantity(array $tratamiento): float
{
    $cantidad = (float) ($tratamiento['cantidad_aplicada'] ?? 0);
    if ($cantidad <= 0) {
        $cantidad = (float) ($tratamiento['cantidad_solicitada'] ?? 0);
    }

    return $cantidad;
}

if ($accion === 'crear_registro') {
    if ($role !== 'Agricultor') {
        fito_finish(false, 'Solo el Agricultor puede crear registros fitosanitarios.');
    }

    $data = fito_read_record_payload();
    $error = fito_validate_record_payload($data);
    if ($error !== null) {
        fito_finish(false, $error);
    }

    if (!user_owns_lote($conn, $id_usuario, $data['id_lote'])) {
        fito_finish(false, 'El lote seleccionado no pertenece a su cuenta.');
    }

    $insumo = fitosanitario_inventory_product($conn, $data['id_insumo']);
    if ($data['id_insumo'] > 0 && !$insumo) {
        fito_finish(false, 'Seleccione un producto de inventario válido.');
    }
    $productoAplicado = $insumo['nombre'] ?? null;
    $doseData = null;

    $hasInitialTreatment = $data['id_insumo'] > 0
        && $data['dosis_aplicada'] > 0
        && $data['cantidad_aplicada'] > 0
        && $data['fecha_aplicacion'] !== null;
    if ($hasInitialTreatment && $insumo) {
        try {
            $doseData = fito_prepare_dose_data(
                $insumo,
                fito_lote_area($conn, $data['id_lote']),
                $data['dosis_aplicada'],
                $data['cantidad_aplicada'],
                $data['motivo_ajuste']
            );
        } catch (RuntimeException $exception) {
            fito_finish(false, $exception->getMessage());
        }
    }
    $estado = $hasInitialTreatment ? 'En tratamiento' : 'Pendiente';

    $conn->begin_transaction();

    try {
        db_execute(
            $conn,
            "INSERT INTO control_fitosanitario (
                id_lote, id_usuario, id_insumo, tipo, nombre_problema, severidad, descripcion,
                producto_aplicado, dosis, fecha_deteccion, fecha_aplicacion, estado, observaciones
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            "isissssssssss",
            [
                $data['id_lote'],
                $id_usuario,
                $data['id_insumo'] > 0 ? $data['id_insumo'] : null,
                $data['tipo'],
                $data['nombre_problema'],
                $data['severidad'],
                $data['descripcion'],
                $productoAplicado,
                $doseData['dosis_texto'] ?? null,
                $data['fecha_deteccion'],
                $data['fecha_aplicacion'],
                $estado,
                $data['observaciones'] === '' ? null : $data['observaciones'],
            ]
        );

        $idControl = $conn->insert_id;

        if ($hasInitialTreatment) {
            db_execute(
                $conn,
                "INSERT INTO control_fitosanitario_tratamientos (
                    id_control, id_usuario, id_insumo, producto_aplicado,
                    dosis_recomendada, dosis_aplicada, unidad_dosis, unidad_aplicacion,
                    dosis, cantidad_sugerida, cantidad_aplicada, cantidad_solicitada,
                    fecha_aplicacion, estado_resultante, observaciones, motivo_ajuste
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                "isisddsssdddssss",
                [
                    $idControl,
                    $id_usuario,
                    $data['id_insumo'],
                    $productoAplicado,
                    $doseData['dosis_recomendada'],
                    $doseData['dosis_aplicada'],
                    $doseData['unidad_dosis'],
                    $doseData['unidad_aplicacion'],
                    $doseData['dosis_texto'],
                    $doseData['cantidad_sugerida'],
                    $doseData['cantidad_aplicada'],
                    $doseData['cantidad_aplicada'],
                    $data['fecha_aplicacion'],
                    $estado,
                    null,
                    $doseData['motivo_ajuste'],
                ]
            );
        }

        if ($data['severidad'] === 'Alta') {
            fitosanitario_notify_high_severity(
                $conn,
                $data['id_lote'],
                $data['nombre_problema'],
                $data['fecha_deteccion']
            );
        }

        $conn->commit();
        fito_finish(true, 'Registro fitosanitario creado correctamente.');
    } catch (Throwable $exception) {
        $conn->rollback();
        error_log('Error al crear registro fitosanitario: ' . $exception->getMessage());
        fito_finish(false, 'No se pudo crear el registro fitosanitario.');
    }
}

if ($accion === 'editar_registro') {
    $idControl = (int) ($_POST['id_control'] ?? 0);
    $data = fito_read_record_payload();
    $error = fito_validate_record_payload($data, false);

    if ($idControl <= 0 || $error !== null) {
        fito_finish(false, $error ?? 'Registro inválido.');
    }

    if (!fitosanitario_can_edit($conn, $role, $id_usuario, $idControl)) {
        fito_finish(false, 'No tienes permisos para editar este registro.');
    }

    $registroActual = db_fetch_one(
        $conn,
        "SELECT id_insumo, producto_aplicado, dosis FROM control_fitosanitario WHERE id_control = ?",
        "i",
        [$idControl]
    );
    if (!$registroActual) {
        fito_finish(false, 'Registro no encontrado.');
    }

    if ($role === 'Agricultor' && !user_owns_lote($conn, $id_usuario, $data['id_lote'])) {
        fito_finish(false, 'El lote seleccionado no pertenece a su cuenta.');
    }

    $insumo = fitosanitario_inventory_product($conn, $data['id_insumo']);
    if ($data['id_insumo'] > 0 && !$insumo) {
        fito_finish(false, 'Seleccione un producto de inventario válido.');
    }
    $productoAplicado = $registroActual['producto_aplicado'];
    $doseText = $registroActual['dosis'];
    $idInsumoResumen = $registroActual['id_insumo'] !== null ? (int) $registroActual['id_insumo'] : null;
    if ($insumo && $data['dosis_aplicada'] > 0 && $data['cantidad_aplicada'] > 0) {
        try {
            $editDoseData = fito_prepare_dose_data(
                $insumo,
                fito_lote_area($conn, $data['id_lote']),
                $data['dosis_aplicada'],
                $data['cantidad_aplicada'],
                $data['motivo_ajuste']
            );
            $productoAplicado = $insumo['nombre'];
            $doseText = $editDoseData['dosis_texto'];
            $idInsumoResumen = $data['id_insumo'];
        } catch (RuntimeException $exception) {
            fito_finish(false, $exception->getMessage());
        }
    }

    try {
        db_execute(
            $conn,
            "UPDATE control_fitosanitario
             SET id_lote = ?, id_insumo = ?, tipo = ?, nombre_problema = ?, severidad = ?, descripcion = ?,
                 producto_aplicado = ?, dosis = ?, fecha_deteccion = ?, fecha_aplicacion = ?,
                 observaciones = ?, fecha_actualizacion = NOW()
             WHERE id_control = ?",
            "iisssssssssi",
            [
                $data['id_lote'],
                $idInsumoResumen,
                $data['tipo'],
                $data['nombre_problema'],
                $data['severidad'],
                $data['descripcion'],
                $productoAplicado,
                $doseText,
                $data['fecha_deteccion'],
                $data['fecha_aplicacion'],
                $data['observaciones'] === '' ? null : $data['observaciones'],
                $idControl,
            ]
        );

        fito_finish(true, 'Registro fitosanitario actualizado correctamente.');
    } catch (Throwable $exception) {
        error_log('Error al editar registro fitosanitario: ' . $exception->getMessage());
        fito_finish(false, 'No se pudo actualizar el registro fitosanitario.');
    }
}

if ($accion === 'agregar_tratamiento') {
    $idControl = (int) ($_POST['id_control'] ?? 0);
    $idInsumo = (int) ($_POST['id_insumo'] ?? 0);
    $dosisAplicada = (float) ($_POST['dosis_aplicada'] ?? 0);
    $cantidadAplicada = (float) ($_POST['cantidad_aplicada'] ?? 0);
    $motivoAjuste = trim((string) ($_POST['motivo_ajuste'] ?? ''));
    $fechaAplicacion = post_date_or_null('fecha_aplicacion');
    $estadoResultante = trim((string) ($_POST['estado_resultante'] ?? ''));
    $observaciones = trim((string) ($_POST['observaciones'] ?? ''));

    if ($idControl <= 0 || !fitosanitario_can_add_treatment($conn, $role, $id_usuario, $idControl)) {
        fito_finish(false, 'No tienes permisos para registrar tratamientos en este caso.');
    }

    $insumo = fitosanitario_inventory_product($conn, $idInsumo);
    if (!$insumo) {
        fito_finish(false, 'Seleccione un producto de inventario válido.');
    }
    $producto = $insumo['nombre'];

    if ($dosisAplicada <= 0 || $cantidadAplicada <= 0 || $fechaAplicacion === null || !valid_date_or_null($fechaAplicacion)) {
        fito_finish(false, 'Complete producto, dosis aplicada, cantidad y fecha de aplicación.');
    }

    if (!fitosanitario_is_valid_estado($estadoResultante)) {
        fito_finish(false, 'Seleccione un estado resultante válido.');
    }

    $conn->begin_transaction();

    try {
        $doseData = fito_prepare_dose_data(
            $insumo,
            fito_control_lote_area($conn, $idControl),
            $dosisAplicada,
            $cantidadAplicada,
            $motivoAjuste
        );

        db_execute(
            $conn,
            "INSERT INTO control_fitosanitario_tratamientos (
                id_control, id_usuario, id_insumo, producto_aplicado,
                dosis_recomendada, dosis_aplicada, unidad_dosis, unidad_aplicacion,
                dosis, cantidad_sugerida, cantidad_aplicada, cantidad_solicitada,
                fecha_aplicacion, estado_resultante, observaciones, motivo_ajuste
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            "isisddsssdddssss",
            [
                $idControl,
                $id_usuario,
                $idInsumo,
                $producto,
                $doseData['dosis_recomendada'],
                $doseData['dosis_aplicada'],
                $doseData['unidad_dosis'],
                $doseData['unidad_aplicacion'],
                $doseData['dosis_texto'],
                $doseData['cantidad_sugerida'],
                $doseData['cantidad_aplicada'],
                $doseData['cantidad_aplicada'],
                $fechaAplicacion,
                $estadoResultante,
                $observaciones === '' ? null : $observaciones,
                $doseData['motivo_ajuste'],
            ]
        );

        db_execute(
            $conn,
            "UPDATE control_fitosanitario
             SET producto_aplicado = ?, dosis = ?, fecha_aplicacion = ?,
                 id_insumo = ?, estado = ?, fecha_actualizacion = NOW()
             WHERE id_control = ?",
            "sssisi",
            [$producto, $doseData['dosis_texto'], $fechaAplicacion, $idInsumo, $estadoResultante, $idControl]
        );

        $conn->commit();
        fito_finish(true, 'Tratamiento registrado correctamente.');
    } catch (Throwable $exception) {
        $conn->rollback();
        error_log('Error al registrar tratamiento fitosanitario: ' . $exception->getMessage());
        fito_finish(false, 'No se pudo registrar el tratamiento.');
    }
}

if ($accion === 'cambiar_estado') {
    if ($role !== 'Administrador') {
        fito_finish(false, 'Solo el Administrador puede cambiar el estado del tratamiento.');
    }

    $idControl = (int) ($_POST['id_control'] ?? 0);
    $estado = trim((string) ($_POST['estado'] ?? ''));

    if ($idControl <= 0 || !fitosanitario_is_valid_estado($estado)) {
        fito_finish(false, 'Seleccione un estado válido.');
    }

    try {
        db_execute(
            $conn,
            "UPDATE control_fitosanitario
             SET estado = ?, fecha_actualizacion = NOW()
             WHERE id_control = ?",
            "si",
            [$estado, $idControl]
        );

        fito_finish(true, 'Estado actualizado correctamente.');
    } catch (Throwable $exception) {
        error_log('Error al cambiar estado fitosanitario: ' . $exception->getMessage());
        fito_finish(false, 'No se pudo actualizar el estado.');
    }
}

if ($accion === 'aprobar_tratamiento') {
    if ($role !== 'Administrador') {
        fito_finish(false, 'Solo el Administrador puede aprobar tratamientos.');
    }

    $idTratamiento = (int) ($_POST['id_tratamiento'] ?? 0);
    if ($idTratamiento <= 0) {
        fito_finish(false, 'Tratamiento inválido.');
    }

    $conn->begin_transaction();

    try {
        $tratamiento = fito_treatment_for_delivery($conn, $idTratamiento, true);
        if (!$tratamiento || $tratamiento['estado_entrega'] !== 'Pendiente') {
            throw new RuntimeException('El tratamiento no está disponible para aprobación.');
        }

        $cantidad = fito_delivery_quantity($tratamiento);
        $stockDisponible = (float) $tratamiento['stock_disponible'];
        if ($cantidad <= 0) {
            throw new RuntimeException('La cantidad para entrega no es válida.');
        }
        if ($cantidad > $stockDisponible) {
            throw new RuntimeException("No hay stock suficiente para aprobar. Disponible: {$stockDisponible} {$tratamiento['stock_unidad']}.");
        }

        $actualizadas = db_execute(
            $conn,
            "UPDATE control_fitosanitario_tratamientos
             SET estado_entrega = 'Aprobado',
                 id_usuario_aprobacion = ?,
                 fecha_aprobacion = NOW()
             WHERE id_tratamiento = ?
               AND estado_entrega = 'Pendiente'
               AND id_insumo IS NOT NULL",
            "si",
            [$id_usuario, $idTratamiento]
        );

        if ($actualizadas !== 1) {
            throw new RuntimeException('El tratamiento no está disponible para aprobación.');
        }

        $conn->commit();
        fito_finish(true, 'Tratamiento aprobado para entrega por bodega.');
    } catch (Throwable $exception) {
        $conn->rollback();
        error_log('Error al aprobar tratamiento fitosanitario: ' . $exception->getMessage());
        fito_finish(false, $exception instanceof RuntimeException ? $exception->getMessage() : 'No se pudo aprobar el tratamiento.');
    }
}

if ($accion === 'entregar_tratamiento') {
    if ($role !== 'Bodeguero') {
        fito_finish(false, 'Solo el Bodeguero puede entregar productos fitosanitarios.');
    }

    $idTratamiento = (int) ($_POST['id_tratamiento'] ?? 0);
    if ($idTratamiento <= 0) {
        fito_finish(false, 'Tratamiento inválido.');
    }

    $conn->begin_transaction();

    try {
        $tratamiento = fito_treatment_for_delivery($conn, $idTratamiento, true);
        if (!$tratamiento || $tratamiento['estado_entrega'] !== 'Aprobado') {
            throw new RuntimeException('El tratamiento no está aprobado para entrega.');
        }

        $cantidad = fito_delivery_quantity($tratamiento);
        $stockDisponible = (float) $tratamiento['stock_disponible'];
        $idInsumo = (int) $tratamiento['id_insumo'];

        if ($cantidad <= 0) {
            throw new RuntimeException('La cantidad para entrega no es válida.');
        }
        if ($cantidad > $stockDisponible) {
            throw new RuntimeException("Stock insuficiente. Disponible: {$stockDisponible} {$tratamiento['stock_unidad']}.");
        }

        $stockActualizado = db_execute(
            $conn,
            "UPDATE insumos_agricolas
             SET cantidad = cantidad - ?
             WHERE id_insumos = ? AND cantidad >= ?",
            "did",
            [$cantidad, $idInsumo, $cantidad]
        );

        if ($stockActualizado !== 1) {
            throw new RuntimeException('No se pudo descontar el stock. Verifique la disponibilidad actual.');
        }

        $entregaActualizada = db_execute(
            $conn,
            "UPDATE control_fitosanitario_tratamientos
             SET estado_entrega = 'Entregado',
                 cantidad_entregada = ?,
                 id_usuario_entrega = ?,
                 fecha_entrega = NOW()
             WHERE id_tratamiento = ?
               AND estado_entrega = 'Aprobado'",
            "dsi",
            [$cantidad, $id_usuario, $idTratamiento]
        );

        if ($entregaActualizada !== 1) {
            throw new RuntimeException('El tratamiento ya no está disponible para entrega.');
        }

        $observacion = sprintf(
            'Salida por Control Fitosanitario. Lote #%d - %s.',
            (int) $tratamiento['id_lote'],
            $tratamiento['nombre_problema']
        );

        db_execute(
            $conn,
            "INSERT INTO movimientos_insumos (
                id_insumo, id_usuario, id_tratamiento_fitosanitario, tipo, estado,
                cantidad, cantidad_solicitada, cantidad_entregada, observaciones, fecha_movimiento
            ) VALUES (?, ?, ?, 'Salida', 'Salida', ?, ?, ?, ?, NOW())",
            "isiddds",
            [$idInsumo, $id_usuario, $idTratamiento, $cantidad, $cantidad, $cantidad, $observacion]
        );

        $conn->commit();
        fito_finish(true, 'Producto fitosanitario entregado y stock descontado correctamente.');
    } catch (Throwable $exception) {
        $conn->rollback();
        error_log('Error al entregar tratamiento fitosanitario: ' . $exception->getMessage());
        fito_finish(false, $exception instanceof RuntimeException ? $exception->getMessage() : 'No se pudo entregar el producto fitosanitario.');
    }
}

fito_finish(false, 'Acción no reconocida.');
