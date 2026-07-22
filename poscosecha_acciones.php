<?php
require_once 'conexion.php';
require_once __DIR__ . '/includes/poscosecha_helpers.php';

poscosecha_require_role(['Administrador', 'Bodeguero', 'Agricultor']);

$role = (string) $_SESSION['rol'];
$userId = (string) $_SESSION['id_usuario'];
$accion = (string) ($_POST['accion'] ?? '');

function poscosecha_redirect_for_role(string $role): void
{
    redirect($role === 'Administrador' ? 'admin.php#poscosecha' : 'poscosecha.php');
}

function poscosecha_read_payload(): array
{
    $destino = trim((string) ($_POST['destino_previsto'] ?? 'Exportación'));

    return [
        'id_cosecha' => (int) ($_POST['id_cosecha'] ?? 0),
        'fecha_ingreso' => trim((string) ($_POST['fecha_ingreso'] ?? '')),
        'kg_recibidos' => poscosecha_float('kg_recibidos'),
        'kg_lavados' => poscosecha_float('kg_lavados'),
        'kg_clasificados' => poscosecha_float('kg_clasificados'),
        'kg_primera' => poscosecha_float('kg_primera'),
        'kg_segunda' => poscosecha_float('kg_segunda'),
        'kg_descarte' => poscosecha_float('kg_descarte'),
        'kg_merma' => poscosecha_float('kg_merma'),
        'motivo_merma' => trim((string) ($_POST['motivo_merma'] ?? '')),
        'kg_exportacion' => poscosecha_float('kg_exportacion'),
        'kg_mercado_nacional' => poscosecha_float('kg_mercado_nacional'),
        'kg_procesamiento' => poscosecha_float('kg_procesamiento'),
        'destino_previsto' => in_array($destino, poscosecha_destinos(), true) ? $destino : '',
        'estado' => 'Recepción',
        'listo_para_despacho' => isset($_POST['listo_para_despacho']) ? 1 : 0,
        'observaciones' => trim((string) ($_POST['observaciones'] ?? '')),
    ];
}

function poscosecha_validate_payload(array $data): ?string
{
    if (!poscosecha_valid_date($data['fecha_ingreso'])) {
        return 'Ingrese una fecha de ingreso válida.';
    }

    if ($data['destino_previsto'] === '') {
        return 'Seleccione un destino previsto válido.';
    }

    return poscosecha_validate_amounts($data);
}

if ($role === 'Agricultor') {
    flash('error', 'El Agricultor solo puede consultar poscosecha.');
    poscosecha_redirect_for_role($role);
}

if ($accion === 'crear_poscosecha') {
    $data = poscosecha_read_payload();
    $data['estado'] = 'Recepción';
    $data['listo_para_despacho'] = 0;

    if ($data['id_cosecha'] <= 0) {
        flash('error', 'Seleccione una cosecha recibida.');
        poscosecha_redirect_for_role($role);
    }

    $error = poscosecha_validate_payload($data);
    if ($error !== null) {
        flash('error', $error);
        poscosecha_redirect_for_role($role);
    }

    $conn->begin_transaction();

    try {
        $cosecha = db_fetch_one(
            $conn,
            "SELECT co.id_cosecha, co.id_lote, co.estado, co.cantidad_total_kg,
                    p.id_poscosecha
             FROM cosechas co
             LEFT JOIN poscosecha p ON p.id_cosecha = co.id_cosecha
             WHERE co.id_cosecha = ?
             FOR UPDATE",
            "i",
            [$data['id_cosecha']]
        );

        if (!$cosecha || $cosecha['estado'] !== 'Recibida') {
            throw new RuntimeException('Solo se puede registrar poscosecha para cosechas recibidas.');
        }

        if (!empty($cosecha['id_poscosecha'])) {
            throw new RuntimeException('Esta cosecha ya tiene un proceso de poscosecha registrado.');
        }

        db_execute(
            $conn,
            "INSERT INTO poscosecha (
                id_cosecha, id_lote, id_responsable, fecha_ingreso,
                kg_recibidos, kg_lavados, kg_clasificados,
                kg_primera, kg_segunda, kg_descarte, kg_merma, motivo_merma,
                kg_exportacion, kg_mercado_nacional, kg_procesamiento,
                destino_previsto, estado, listo_para_despacho, observaciones,
                fecha_finalizacion
             ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            "iissdddddddsdddssiss",
            [
                $data['id_cosecha'],
                (int) $cosecha['id_lote'],
                $userId,
                $data['fecha_ingreso'],
                $data['kg_recibidos'],
                $data['kg_lavados'],
                $data['kg_clasificados'],
                $data['kg_primera'],
                $data['kg_segunda'],
                $data['kg_descarte'],
                $data['kg_merma'],
                $data['motivo_merma'] === '' ? null : $data['motivo_merma'],
                $data['kg_exportacion'],
                $data['kg_mercado_nacional'],
                $data['kg_procesamiento'],
                $data['destino_previsto'],
                $data['estado'],
                $data['listo_para_despacho'],
                $data['observaciones'] === '' ? null : $data['observaciones'],
                $data['estado'] === 'Finalizada' ? date('Y-m-d H:i:s') : null,
            ]
        );

        $idPoscosecha = (int) $conn->insert_id;

        db_execute(
            $conn,
            "INSERT INTO poscosecha_etapas (
                id_poscosecha, etapa_anterior, etapa_nueva, id_usuario, observacion
             ) VALUES (?, NULL, ?, ?, ?)",
            "isss",
            [
                $idPoscosecha,
                $data['estado'],
                $userId,
                'Proceso de poscosecha registrado.',
            ]
        );

        $conn->commit();
        flash('mensaje', 'Proceso de poscosecha registrado correctamente.');
    } catch (Throwable $exception) {
        $conn->rollback();
        error_log('Error al crear poscosecha: ' . $exception->getMessage());
        flash('error', $exception instanceof RuntimeException ? $exception->getMessage() : 'No se pudo registrar la poscosecha.');
    }

    poscosecha_redirect_for_role($role);
}

if ($accion === 'avanzar_etapa') {
    $idPoscosecha = (int) ($_POST['id_poscosecha'] ?? 0);
    $targetEstado = trim((string) ($_POST['etapa_nueva'] ?? ''));
    $observacion = trim((string) ($_POST['observacion_etapa'] ?? ''));

    if ($idPoscosecha <= 0 || !in_array($targetEstado, poscosecha_estados(), true)) {
        flash('error', 'Seleccione una etapa válida.');
        poscosecha_redirect_for_role($role);
    }

    $conn->begin_transaction();

    try {
        $actual = db_fetch_one(
            $conn,
            "SELECT id_poscosecha, estado, kg_recibidos, kg_lavados, kg_clasificados,
                    kg_primera, kg_segunda, kg_descarte, kg_merma,
                    kg_exportacion, kg_mercado_nacional, kg_procesamiento
             FROM poscosecha
             WHERE id_poscosecha = ?
             FOR UPDATE",
            "i",
            [$idPoscosecha]
        );

        if (!$actual) {
            throw new RuntimeException('El proceso de poscosecha no existe.');
        }

        $estadoActual = (string) $actual['estado'];
        $nextEstado = poscosecha_next_estado($estadoActual);
        $previousEstado = poscosecha_previous_estado($estadoActual);
        $isNext = $targetEstado === $nextEstado;
        $isPrevious = $targetEstado === $previousEstado;

        if (!$isNext && !($role === 'Administrador' && $isPrevious)) {
            throw new RuntimeException('Solo se puede avanzar a la siguiente etapa. El retroceso queda reservado al Administrador.');
        }

        $updates = [
            'kg_lavados' => (float) $actual['kg_lavados'],
            'kg_clasificados' => (float) $actual['kg_clasificados'],
            'kg_primera' => (float) $actual['kg_primera'],
            'kg_segunda' => (float) $actual['kg_segunda'],
            'kg_descarte' => (float) $actual['kg_descarte'],
            'kg_merma' => (float) $actual['kg_merma'],
            'motivo_merma' => null,
            'kg_exportacion' => (float) $actual['kg_exportacion'],
            'kg_mercado_nacional' => (float) $actual['kg_mercado_nacional'],
            'kg_procesamiento' => (float) $actual['kg_procesamiento'],
        ];

        if ($isNext) {
            if ($targetEstado === 'Lavado') {
                $updates['kg_lavados'] = poscosecha_float('kg_lavados');
                if ($updates['kg_lavados'] <= 0) {
                    throw new RuntimeException('Registre los kg lavados antes de avanzar.');
                }
                if ($updates['kg_lavados'] > (float) $actual['kg_recibidos']) {
                    throw new RuntimeException('Los kg lavados no pueden superar los kg recibidos.');
                }
            } elseif ($targetEstado === 'Clasificación') {
                if ((float) $actual['kg_lavados'] <= 0) {
                    throw new RuntimeException('No se puede pasar a Clasificación sin registrar lavado.');
                }

                $updates['kg_primera'] = poscosecha_float('kg_primera');
                $updates['kg_segunda'] = poscosecha_float('kg_segunda');
                $updates['kg_descarte'] = poscosecha_float('kg_descarte');
                $updates['kg_merma'] = poscosecha_float('kg_merma');
                $updates['motivo_merma'] = trim((string) ($_POST['motivo_merma'] ?? ''));
                $updates['kg_clasificados'] = round(
                    $updates['kg_primera'] + $updates['kg_segunda'] + $updates['kg_descarte'] + $updates['kg_merma'],
                    2
                );

                if ($updates['kg_clasificados'] <= 0) {
                    throw new RuntimeException('Registre la clasificación antes de avanzar.');
                }
                if ($updates['kg_clasificados'] > (float) $actual['kg_recibidos']) {
                    throw new RuntimeException('La clasificación no puede superar los kg recibidos.');
                }
                if ($updates['kg_merma'] > 0 && $updates['motivo_merma'] === '') {
                    throw new RuntimeException('Ingrese el motivo de la merma.');
                }
            } elseif ($targetEstado === 'Empaque') {
                if ((float) $actual['kg_clasificados'] <= 0) {
                    throw new RuntimeException('No se puede definir destino sin clasificación.');
                }

                $updates['kg_exportacion'] = poscosecha_float('kg_exportacion');
                $updates['kg_mercado_nacional'] = poscosecha_float('kg_mercado_nacional');
                $updates['kg_procesamiento'] = poscosecha_float('kg_procesamiento');
                $destinoTotal = round($updates['kg_exportacion'] + $updates['kg_mercado_nacional'] + $updates['kg_procesamiento'], 2);

                if ($destinoTotal <= 0) {
                    throw new RuntimeException('Defina al menos un destino antes de avanzar.');
                }
                if ($destinoTotal > (float) $actual['kg_recibidos']) {
                    throw new RuntimeException('La distribución por destino no puede superar los kg recibidos.');
                }
            } elseif ($targetEstado === 'Almacenamiento') {
                $destinoTotal = (float) $actual['kg_exportacion'] + (float) $actual['kg_mercado_nacional'] + (float) $actual['kg_procesamiento'];
                if ($destinoTotal <= 0) {
                    throw new RuntimeException('No se puede almacenar sin destino definido.');
                }
            } elseif ($targetEstado === 'Finalizada') {
                $destinoTotal = (float) $actual['kg_exportacion'] + (float) $actual['kg_mercado_nacional'] + (float) $actual['kg_procesamiento'];
                if ($destinoTotal <= 0) {
                    throw new RuntimeException('No se puede finalizar sin destino definido.');
                }
            }
        }

        $fechaFinalizacion = $targetEstado === 'Finalizada' ? date('Y-m-d H:i:s') : null;
        $listoParaDespacho = $targetEstado === 'Finalizada' ? 1 : 0;

        db_execute(
            $conn,
            "UPDATE poscosecha
             SET estado = ?,
                 id_responsable = ?,
                 kg_lavados = ?,
                 kg_clasificados = ?,
                 kg_primera = ?,
                 kg_segunda = ?,
                 kg_descarte = ?,
                 kg_merma = ?,
                 motivo_merma = COALESCE(?, motivo_merma),
                 kg_exportacion = ?,
                 kg_mercado_nacional = ?,
                 kg_procesamiento = ?,
                 listo_para_despacho = ?,
                 fecha_finalizacion = ?
             WHERE id_poscosecha = ?",
            "ssddddddsdddisi",
            [
                $targetEstado,
                $userId,
                $updates['kg_lavados'],
                $updates['kg_clasificados'],
                $updates['kg_primera'],
                $updates['kg_segunda'],
                $updates['kg_descarte'],
                $updates['kg_merma'],
                $updates['motivo_merma'] === '' ? null : $updates['motivo_merma'],
                $updates['kg_exportacion'],
                $updates['kg_mercado_nacional'],
                $updates['kg_procesamiento'],
                $listoParaDespacho,
                $fechaFinalizacion,
                $idPoscosecha,
            ]
        );

        db_execute(
            $conn,
            "INSERT INTO poscosecha_etapas (
                id_poscosecha, etapa_anterior, etapa_nueva, id_usuario, observacion
             ) VALUES (?, ?, ?, ?, ?)",
            "issss",
            [
                $idPoscosecha,
                $estadoActual,
                $targetEstado,
                $userId,
                $observacion === '' ? null : $observacion,
            ]
        );

        $conn->commit();
        flash('mensaje', $targetEstado === 'Finalizada'
            ? 'Poscosecha finalizada correctamente.'
            : 'Poscosecha avanzada a ' . $targetEstado . ' correctamente.');
    } catch (Throwable $exception) {
        $conn->rollback();
        error_log('Error al avanzar etapa de poscosecha: ' . $exception->getMessage());
        flash('error', $exception instanceof RuntimeException ? $exception->getMessage() : 'No se pudo cambiar la etapa de poscosecha.');
    }

    poscosecha_redirect_for_role($role);
}

if ($accion === 'editar_poscosecha') {
    $idPoscosecha = (int) ($_POST['id_poscosecha'] ?? 0);
    $data = poscosecha_read_payload();

    if ($idPoscosecha <= 0) {
        flash('error', 'Seleccione un proceso de poscosecha válido.');
        poscosecha_redirect_for_role($role);
    }

    $error = poscosecha_validate_payload($data);
    if ($error !== null) {
        flash('error', $error);
        poscosecha_redirect_for_role($role);
    }

    $conn->begin_transaction();

    try {
        $actual = db_fetch_one(
            $conn,
            "SELECT id_poscosecha, estado
             FROM poscosecha
             WHERE id_poscosecha = ?
             FOR UPDATE",
            "i",
            [$idPoscosecha]
        );

        if (!$actual) {
            throw new RuntimeException('El proceso de poscosecha no existe.');
        }

        $data['estado'] = (string) $actual['estado'];
        $data['listo_para_despacho'] = $data['estado'] === 'Finalizada' && (int) $data['listo_para_despacho'] === 1 ? 1 : 0;
        $fechaFinalizacion = $data['estado'] === 'Finalizada' ? date('Y-m-d H:i:s') : null;

        db_execute(
            $conn,
            "UPDATE poscosecha
             SET id_responsable = ?,
                 fecha_ingreso = ?,
                 kg_recibidos = ?,
                 kg_lavados = ?,
                 kg_clasificados = ?,
                 kg_primera = ?,
                 kg_segunda = ?,
                 kg_descarte = ?,
                 kg_merma = ?,
                 motivo_merma = ?,
                 kg_exportacion = ?,
                 kg_mercado_nacional = ?,
                 kg_procesamiento = ?,
                 destino_previsto = ?,
                 estado = ?,
                 listo_para_despacho = ?,
                 observaciones = ?,
                 fecha_finalizacion = ?
             WHERE id_poscosecha = ?",
            "ssdddddddsdddssissi",
            [
                $userId,
                $data['fecha_ingreso'],
                $data['kg_recibidos'],
                $data['kg_lavados'],
                $data['kg_clasificados'],
                $data['kg_primera'],
                $data['kg_segunda'],
                $data['kg_descarte'],
                $data['kg_merma'],
                $data['motivo_merma'] === '' ? null : $data['motivo_merma'],
                $data['kg_exportacion'],
                $data['kg_mercado_nacional'],
                $data['kg_procesamiento'],
                $data['destino_previsto'],
                $data['estado'],
                $data['listo_para_despacho'],
                $data['observaciones'] === '' ? null : $data['observaciones'],
                $fechaFinalizacion,
                $idPoscosecha,
            ]
        );

        $conn->commit();
        flash('mensaje', 'Proceso de poscosecha actualizado correctamente.');
    } catch (Throwable $exception) {
        $conn->rollback();
        error_log('Error al editar poscosecha: ' . $exception->getMessage());
        flash('error', $exception instanceof RuntimeException ? $exception->getMessage() : 'No se pudo actualizar la poscosecha.');
    }

    poscosecha_redirect_for_role($role);
}

flash('error', 'Acción no reconocida.');
poscosecha_redirect_for_role($role);
