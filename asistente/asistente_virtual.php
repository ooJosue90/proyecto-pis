<?php
/**
 * ADA - Asistente virtual conectado a Gemini.
 * Recibe preguntas por POST, clasifica la intencion, valida permisos por rol,
 * arma contexto permitido desde MySQL y llama a Gemini si corresponde.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/config_gemini.php';
require_once __DIR__ . '/permisos_asistente.php';

start_secure_session();

header('Content-Type: application/json; charset=utf-8');

const ADA_DENIED_MESSAGE = 'No tienes permisos para realizar esta acción. Puedo ayudarte con las funciones disponibles para tu perfil.';
const ADA_SYSTEM_PROMPT = 'Eres ADA (Asistente de Decisiones Agrícolas) del sistema SEMBRIEXPORT.

Tu función es ayudar a los usuarios con información agrícola, consultas del sistema y orientación operativa.

Debes distinguir entre:

* Conocimiento agrícola general.
* Consultas sobre datos internos del sistema.
* Acciones administrativas.

El conocimiento agrícola general puede ser respondido a cualquier usuario.

Las consultas sobre datos internos deben respetar los permisos del rol.

Las acciones administrativas requieren autorización según el perfil del usuario.

No bloquees preguntas educativas relacionadas con agricultura, cultivos, fertilización, plagas, enfermedades, producción agrícola o buenas prácticas.

Solo restringe el acceso cuando el usuario solicite información interna o acciones para las que no tenga permisos.

Si el usuario no tiene permisos, responde de forma amable indicando qué funciones sí están disponibles para su perfil.

No te presentes en cada respuesta. No empieces con frases como "Soy ADA" o "Hola, soy ADA". Responde directamente con una frase breve de cortesía, por ejemplo "Con gusto, te doy la información:", y luego entrega la información solicitada.

Responde de forma profesional, clara y útil.';

function ada_json(bool $success, string $respuesta, int $status = 200, ?string $rol = null): void
{
    http_response_code($status);
    echo json_encode([
        'success' => $success,
        'ok' => $success,
        'rol' => $rol ?? ($_SESSION['rol'] ?? 'Invitado'),
        'respuesta' => $respuesta,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function ada_pregunta_recibida(): string
{
    $entrada = json_decode(file_get_contents('php://input'), true);

    if (is_array($entrada) && isset($entrada['pregunta'])) {
        return trim((string) $entrada['pregunta']);
    }

    return trim((string) ($_POST['pregunta'] ?? ''));
}

function ada_contiene_tema(string $pregunta, array $temas): bool
{
    $texto = ada_normalizar_texto($pregunta);

    foreach ($temas as $tema) {
        if (str_contains($texto, ada_normalizar_texto($tema))) {
            return true;
        }
    }

    return false;
}

function ada_es_consulta_general(string $pregunta): bool
{
    return ada_contiene_tema($pregunta, [
        'hola',
        'buenos dias',
        'buenas tardes',
        'buenas noches',
        'ayuda',
        'que puedes hacer',
        'como funciona',
    ]);
}

function ada_api_key_configurada(): bool
{
    $key = trim((string) GEMINI_API_KEY);

    return $key !== '';
}

function ada_fetch_all(mysqli $conn, string $sql, string $types = '', array $params = []): array
{
    $stmt = $conn->prepare($sql);

    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $rows;
}

function ada_fetch_value(mysqli $conn, string $sql, string $types = '', array $params = [], $default = 0)
{
    $rows = ada_fetch_all($conn, $sql, $types, $params);

    if (!$rows) {
        return $default;
    }

    $value = reset($rows[0]);
    return $value ?? $default;
}

function ada_table_exists(mysqli $conn, string $table): bool
{
    $escaped = $conn->real_escape_string($table);
    return $conn->query("SHOW TABLES LIKE '{$escaped}'")->num_rows > 0;
}

function ada_numero($valor, int $decimales = 2): string
{
    return number_format((float) $valor, $decimales, '.', ',');
}

function ada_fecha(?string $fecha): string
{
    if (empty($fecha)) {
        return 'Sin fecha';
    }

    return date('d/m/Y', strtotime($fecha));
}

function ada_etapa_lote($etapa): string
{
    return match ((int) $etapa) {
        1 => 'Riego',
        2 => 'Siembra',
        3 => 'Cosecha',
        default => 'Sin etapa',
    };
}

function ada_lineas(array $rows, callable $formatter): string
{
    if (!$rows) {
        return 'No hay registros disponibles.';
    }

    return implode("\n", array_map($formatter, $rows));
}

function ada_contexto_usuarios(mysqli $conn): string
{
    $rows = ada_fetch_all(
        $conn,
        'SELECT id_usuario, nombre, email, rol, fecha_registro
         FROM usuarios
         ORDER BY nombre ASC
         LIMIT 20'
    );

    return "Usuarios:\n" . ada_lineas($rows, fn ($row) =>
        "{$row['id_usuario']} - {$row['nombre']} - {$row['email']} - {$row['rol']} - Registro: " . ada_fecha($row['fecha_registro'])
    );
}

function ada_contexto_inventario(mysqli $conn): string
{
    $rows = ada_fetch_all(
        $conn,
        'SELECT nombre, tipo, unidad_medida, cantidad, observaciones
         FROM insumos_agricolas
         ORDER BY cantidad ASC, nombre ASC
         LIMIT 20'
    );

    return "Inventario de insumos:\n" . ada_lineas($rows, fn ($row) =>
        "{$row['nombre']} - {$row['tipo']} - " . ada_numero($row['cantidad']) . " {$row['unidad_medida']} - " . ($row['observaciones'] ?: 'Sin observaciones')
    );
}

function ada_contexto_proveedores(mysqli $conn): string
{
    $rows = ada_fetch_all(
        $conn,
        'SELECT Nombre, ruc_cedula, telefono, email, direccion
         FROM proveedor
         ORDER BY Nombre ASC
         LIMIT 20'
    );

    return "Proveedores:\n" . ada_lineas($rows, fn ($row) =>
        "{$row['Nombre']} - RUC/Cedula: {$row['ruc_cedula']} - Tel: " . ($row['telefono'] ?: 'N/A') . " - Email: " . ($row['email'] ?: 'N/A') . " - Direccion: " . ($row['direccion'] ?: 'N/A')
    );
}

function ada_contexto_pedidos(mysqli $conn): string
{
    $rows = ada_fetch_all(
        $conn,
        'SELECT p.nombre_producto, p.cantidad, p.unidad_medida, p.fecha, pr.Nombre AS proveedor, u.nombre AS usuario
         FROM pedidos p
         JOIN proveedor pr ON p.id_proveedor = pr.id_proveedor
         JOIN usuarios u ON p.id_usuario = u.id_usuario
         ORDER BY p.fecha DESC
         LIMIT 20'
    );

    return "Pedidos:\n" . ada_lineas($rows, fn ($row) =>
        ada_fecha($row['fecha']) . " - {$row['nombre_producto']} - " . ada_numero($row['cantidad']) . " {$row['unidad_medida']} - Proveedor: {$row['proveedor']} - Usuario: {$row['usuario']}"
    );
}

function ada_contexto_facturas(mysqli $conn): string
{
    if (ada_table_exists($conn, 'facturas_compra')) {
        $rows = ada_fetch_all(
            $conn,
            "SELECT fc.numero_factura, fc.fecha, fc.total, fc.estado, fc.observaciones,
                    u.nombre AS usuario, p.Nombre AS proveedor
             FROM facturas_compra fc
             JOIN usuarios u ON fc.id_usuario = u.id_usuario
             JOIN proveedor p ON fc.id_proveedor = p.id_proveedor
             ORDER BY fc.fecha DESC, fc.id_factura_compra DESC
             LIMIT 20"
        );

        return "Facturas de compra:\n" . ada_lineas($rows, fn ($row) =>
            "{$row['numero_factura']} - " . ada_fecha($row['fecha']) . " - {$row['estado']} - Total: $" . ada_numero($row['total']) . " - Proveedor: {$row['proveedor']} - Registrada por: {$row['usuario']} - " . ($row['observaciones'] ?: 'Sin observaciones')
        );
    }

    $rows = ada_fetch_all(
        $conn,
        'SELECT f.id_factura, f.fecha, f.total, f.estado, f.observaciones, u.nombre AS usuario, pr.Nombre AS proveedor
         FROM factura f
         JOIN usuarios u ON f.id_usuario = u.id_usuario
         LEFT JOIN proveedor pr ON f.id_proveedor = pr.id_proveedor
         ORDER BY f.fecha DESC, f.id_factura DESC
         LIMIT 20'
    );

    return "Facturas:\n" . ada_lineas($rows, fn ($row) =>
        "#{$row['id_factura']} - " . ada_fecha($row['fecha']) . " - {$row['estado']} - Total: $" . ada_numero($row['total']) . " - Proveedor: " . ($row['proveedor'] ?: 'N/A') . " - Usuario: {$row['usuario']} - " . ($row['observaciones'] ?: 'Sin observaciones')
    );
}

function ada_contexto_cultivos_lotes(mysqli $conn, string $rol): string
{
    $userId = (string) ($_SESSION['id_usuario'] ?? '');
    $where = '';
    $types = '';
    $params = [];

    if (in_array($rol, ['Agricultor', 'Técnico de Campo'], true)) {
        $where = 'WHERE c.id_usuario = ?';
        $types = 's';
        $params = [$userId];
    }

    $rows = ada_fetch_all(
        $conn,
        "SELECT c.tipo, c.fecha_siembra, u.nombre AS agricultor, l.ubicacion, l.area, l.etapa_actual, l.fecha_registro
         FROM cultivos c
         JOIN usuarios u ON c.id_usuario = u.id_usuario
         LEFT JOIN lotes l ON c.id_cultivo = l.id_cultivo
         {$where}
         ORDER BY c.fecha_siembra DESC, l.fecha_registro DESC
         LIMIT 20",
        $types,
        $params
    );

    return "Cultivos y lotes:\n" . ada_lineas($rows, fn ($row) =>
        "{$row['tipo']} - Agricultor: {$row['agricultor']} - Siembra: " . ada_fecha($row['fecha_siembra']) . " - Lote: " . ($row['ubicacion'] ?: 'Sin lote') . " - Area: " . ada_numero($row['area']) . " ha - Etapa: {$row['etapa_actual']}"
    );
}

function ada_contexto_solicitudes(mysqli $conn, string $rol): string
{
    $userId = (string) ($_SESSION['id_usuario'] ?? '');
    $where = '';
    $types = '';
    $params = [];

    if (in_array($rol, ['Agricultor', 'Técnico de Campo'], true)) {
        $where = 'WHERE ps.id_agricultor = ?';
        $types = 's';
        $params = [$userId];
    }

    $rows = ada_fetch_all(
        $conn,
        "SELECT ps.nombre, ps.cantidad_solicitada, ps.estado, ps.fecha, ps.etapa, ps.observaciones, u.nombre AS agricultor
         FROM productos_solicitud ps
         JOIN usuarios u ON ps.id_agricultor = u.id_usuario
         {$where}
         ORDER BY ps.fecha DESC
         LIMIT 20",
        $types,
        $params
    );

    return "Solicitudes de insumos:\n" . ada_lineas($rows, fn ($row) =>
        ada_fecha($row['fecha']) . " - {$row['nombre']} - " . ada_numero($row['cantidad_solicitada']) . " - Estado: {$row['estado']} - Etapa: " . ($row['etapa'] ?: 'N/A') . " - Agricultor: {$row['agricultor']} - " . ($row['observaciones'] ?: 'Sin observaciones')
    );
}

function ada_contexto_produccion(mysqli $conn, string $rol): string
{
    $userId = (string) ($_SESSION['id_usuario'] ?? '');
    $where = '';
    $types = '';
    $params = [];

    if (in_array($rol, ['Agricultor', 'Técnico de Campo'], true)) {
        $where = 'WHERE pf.id_usuario = ?';
        $types = 's';
        $params = [$userId];
    }

    $rows = ada_fetch_all(
        $conn,
        "SELECT pf.nombre_producto, pf.cantidad, pf.unidad_medida, pf.fecha, pf.observaciones, u.nombre AS agricultor, l.ubicacion
         FROM productos_finales pf
         JOIN usuarios u ON pf.id_usuario = u.id_usuario
         JOIN lotes l ON pf.id_lote = l.id_lote
         {$where}
         ORDER BY pf.fecha DESC
         LIMIT 20",
        $types,
        $params
    );

    return "Produccion:\n" . ada_lineas($rows, fn ($row) =>
        ada_fecha($row['fecha']) . " - {$row['nombre_producto']} - " . ada_numero($row['cantidad']) . " {$row['unidad_medida']} - Lote: {$row['ubicacion']} - Agricultor: {$row['agricultor']} - " . ($row['observaciones'] ?: 'Sin observaciones')
    );
}

function ada_contexto_plagas(mysqli $conn, string $rol): string
{
    $userId = (string) ($_SESSION['id_usuario'] ?? '');
    $where = '';
    $types = '';
    $params = [];

    if (in_array($rol, ['Agricultor', 'Técnico de Campo'], true)) {
        $where = 'WHERE p.id_usuario = ?';
        $types = 's';
        $params = [$userId];
    }

    $rows = ada_fetch_all(
        $conn,
        "SELECT p.nombre, p.fecha, u.nombre AS usuario, l.ubicacion
         FROM plagas p
         JOIN usuarios u ON p.id_usuario = u.id_usuario
         JOIN lotes l ON p.id_lote = l.id_lote
         {$where}
         ORDER BY p.fecha DESC
         LIMIT 20",
        $types,
        $params
    );

    return "Plagas:\n" . ada_lineas($rows, fn ($row) =>
        ada_fecha($row['fecha']) . " - {$row['nombre']} - Lote: {$row['ubicacion']} - Usuario: {$row['usuario']}"
    );
}

function ada_contexto_monitoreo(mysqli $conn, string $rol): string
{
    $userId = (string) ($_SESSION['id_usuario'] ?? '');
    $where = '';
    $types = '';
    $params = [];

    if (in_array($rol, ['Agricultor', 'Técnico de Campo'], true)) {
        $where = 'WHERE c.id_usuario = ?';
        $types = 's';
        $params = [$userId];
    }

    $rows = ada_fetch_all(
        $conn,
        "SELECT l.id_lote, l.ubicacion, l.area, l.etapa_actual,
                l.etapa_riego, l.etapa_siembra, l.etapa_cosecha,
                l.fecha_inicio_riego, l.fecha_fin_riego,
                l.fecha_inicio_siembra, l.fecha_fin_siembra,
                l.fecha_inicio_cosecha, l.fecha_fin_cosecha,
                l.fecha_registro, c.tipo AS cultivo, c.fecha_siembra,
                u.nombre AS agricultor,
                (SELECT COUNT(*) FROM plagas p WHERE p.id_lote = l.id_lote) AS total_plagas,
                (SELECT MAX(p.fecha) FROM plagas p WHERE p.id_lote = l.id_lote) AS ultimo_monitoreo_plagas,
                (SELECT GROUP_CONCAT(DISTINCT p.nombre ORDER BY p.nombre SEPARATOR ', ')
                   FROM plagas p WHERE p.id_lote = l.id_lote) AS plagas_detectadas,
                (SELECT COALESCE(SUM(pf.cantidad), 0)
                   FROM productos_finales pf WHERE pf.id_lote = l.id_lote) AS produccion_total,
                (SELECT GROUP_CONCAT(DISTINCT COALESCE(NULLIF(pf.unidad_medida, ''), 'sin unidad')
                                     ORDER BY pf.unidad_medida SEPARATOR ', ')
                   FROM productos_finales pf WHERE pf.id_lote = l.id_lote) AS unidades_produccion,
                (SELECT MAX(pf.fecha)
                   FROM productos_finales pf WHERE pf.id_lote = l.id_lote) AS ultima_produccion,
                (SELECT COUNT(*)
                   FROM productos_solicitud ps
                  WHERE ps.id_lote = l.id_lote
                    AND ps.estado IN ('Pendiente', 'Aprobado')) AS solicitudes_pendientes
         FROM lotes l
         JOIN cultivos c ON l.id_cultivo = c.id_cultivo
         JOIN usuarios u ON c.id_usuario = u.id_usuario
         {$where}
         ORDER BY l.fecha_registro DESC, l.id_lote DESC
         LIMIT 100",
        $types,
        $params
    );

    $resumen = "Resumen de monitoreo:\n"
        . "Lotes monitoreados: " . count($rows) . "\n"
        . "Area total: " . ada_numero(array_sum(array_column($rows, 'area'))) . " ha\n"
        . "Lotes en riego: " . count(array_filter($rows, fn ($row) => (int) $row['etapa_actual'] === 1)) . "\n"
        . "Lotes en siembra: " . count(array_filter($rows, fn ($row) => (int) $row['etapa_actual'] === 2)) . "\n"
        . "Lotes en cosecha: " . count(array_filter($rows, fn ($row) => (int) $row['etapa_actual'] === 3)) . "\n"
        . "Lotes con plagas registradas: " . count(array_filter($rows, fn ($row) => (int) $row['total_plagas'] > 0)) . "\n"
        . "Solicitudes pendientes o aprobadas: " . array_sum(array_column($rows, 'solicitudes_pendientes'));

    $detalle = ada_lineas($rows, function ($row) {
        return "Lote #{$row['id_lote']} ({$row['ubicacion']})"
            . " - Cultivo: {$row['cultivo']}"
            . " - Agricultor: {$row['agricultor']}"
            . " - Area: " . ada_numero($row['area']) . " ha"
            . " - Etapa actual: " . ada_etapa_lote($row['etapa_actual'])
            . " - Riego: " . ((int) $row['etapa_riego'] === 1 ? 'completado' : 'pendiente')
            . " [" . ada_fecha($row['fecha_inicio_riego']) . " a " . ada_fecha($row['fecha_fin_riego']) . "]"
            . " - Siembra: " . ((int) $row['etapa_siembra'] === 1 ? 'completada' : 'pendiente')
            . " [" . ada_fecha($row['fecha_inicio_siembra']) . " a " . ada_fecha($row['fecha_fin_siembra']) . "]"
            . " - Cosecha: " . ((int) $row['etapa_cosecha'] === 1 ? 'completada' : 'pendiente')
            . " [" . ada_fecha($row['fecha_inicio_cosecha']) . " a " . ada_fecha($row['fecha_fin_cosecha']) . "]"
            . " - Produccion acumulada: " . ada_numero($row['produccion_total'])
            . " " . ($row['unidades_produccion'] ?: 'sin registros')
            . " - Ultima produccion: " . ada_fecha($row['ultima_produccion'])
            . " - Plagas: " . ($row['plagas_detectadas'] ?: 'Sin plagas registradas')
            . " - Ultimo monitoreo fitosanitario: " . ada_fecha($row['ultimo_monitoreo_plagas'])
            . " - Solicitudes pendientes: {$row['solicitudes_pendientes']}";
    });

    return $resumen . "\n\nDetalle por lote:\n" . $detalle;
}

function ada_contexto_actividades(mysqli $conn, string $rol): string
{
    $userId = (string) ($_SESSION['id_usuario'] ?? '');
    $whereCultivo = '';
    $whereUsuario = '';
    $types = '';
    $params = [];

    if (in_array($rol, ['Agricultor', 'Técnico de Campo'], true)) {
        $whereCultivo = 'AND c.id_usuario = ?';
        $whereUsuario = 'AND actividad.id_usuario = ?';
        $types = 'ss';
        $params = [$userId, $userId];
    }

    $pendientes = ada_fetch_all(
        $conn,
        "SELECT l.id_lote, l.ubicacion, c.tipo AS cultivo,
                CASE
                    WHEN l.etapa_riego = 0 THEN 'Completar etapa de riego'
                    WHEN l.etapa_siembra = 0 THEN 'Completar etapa de siembra'
                    WHEN l.etapa_cosecha = 0 THEN 'Completar etapa de cosecha'
                    ELSE 'Sin etapas pendientes'
                END AS actividad_pendiente
         FROM lotes l
         JOIN cultivos c ON l.id_cultivo = c.id_cultivo
         WHERE (l.etapa_riego = 0 OR l.etapa_siembra = 0 OR l.etapa_cosecha = 0)
         {$whereCultivo}
         ORDER BY l.fecha_registro DESC
         LIMIT 100",
        $types === '' ? '' : 's',
        $types === '' ? [] : [$params[0]]
    );

    $recientes = ada_fetch_all(
        $conn,
        "SELECT actividad.tipo, actividad.descripcion, actividad.fecha
         FROM (
             SELECT c.id_usuario, 'Cultivo registrado' AS tipo,
                    CONCAT(c.tipo, ' - fecha de siembra ', DATE_FORMAT(c.fecha_siembra, '%d/%m/%Y')) AS descripcion,
                    CAST(c.fecha_siembra AS DATETIME) AS fecha
             FROM cultivos c
             UNION ALL
             SELECT p.id_usuario, 'Monitoreo de plaga',
                    CONCAT(p.nombre, ' - lote ', l.ubicacion), p.fecha
             FROM plagas p
             JOIN lotes l ON p.id_lote = l.id_lote
             UNION ALL
             SELECT pf.id_usuario, 'Produccion registrada',
                    CONCAT(pf.nombre_producto, ' - ', pf.cantidad, ' ', COALESCE(pf.unidad_medida, ''), ' - lote ', l.ubicacion),
                    pf.fecha
             FROM productos_finales pf
             JOIN lotes l ON pf.id_lote = l.id_lote
             UNION ALL
             SELECT ps.id_agricultor, 'Solicitud de insumo',
                    CONCAT(ps.nombre, ' - estado ', ps.estado), ps.fecha
             FROM productos_solicitud ps
         ) actividad
         WHERE 1 = 1 {$whereUsuario}
         ORDER BY actividad.fecha DESC
         LIMIT 50",
        $types === '' ? '' : 's',
        $types === '' ? [] : [$params[1]]
    );

    return "Actividades agricolas pendientes:\n"
        . ada_lineas($pendientes, fn ($row) =>
            "Lote #{$row['id_lote']} ({$row['ubicacion']}) - {$row['cultivo']} - {$row['actividad_pendiente']}"
        )
        . "\n\nActividad reciente registrada:\n"
        . ada_lineas($recientes, fn ($row) =>
            ada_fecha($row['fecha']) . " - {$row['tipo']} - {$row['descripcion']}"
        );
}

function ada_contexto_movimientos(mysqli $conn): string
{
    $rows = ada_fetch_all(
        $conn,
        'SELECT mi.estado, mi.cantidad, mi.fecha_movimiento, mi.observaciones, ia.nombre AS insumo, u.nombre AS usuario
         FROM movimientos_insumos mi
         JOIN insumos_agricolas ia ON mi.id_insumo = ia.id_insumos
         LEFT JOIN usuarios u ON mi.id_usuario = u.id_usuario
         ORDER BY mi.fecha_movimiento DESC
         LIMIT 20'
    );

    return "Movimientos de insumos:\n" . ada_lineas($rows, fn ($row) =>
        ada_fecha($row['fecha_movimiento']) . " - {$row['estado']} - {$row['insumo']} - " . ada_numero($row['cantidad']) . " - Usuario: " . ($row['usuario'] ?: 'N/A') . " - " . ($row['observaciones'] ?: 'Sin observaciones')
    );
}

function ada_contexto_notificaciones(mysqli $conn): string
{
    $rows = ada_fetch_all(
        $conn,
        'SELECT mensaje, leida, fecha
         FROM notificaciones
         ORDER BY fecha DESC
         LIMIT 20'
    );

    return "Notificaciones:\n" . ada_lineas($rows, fn ($row) =>
        ada_fecha($row['fecha']) . " - " . ((int) $row['leida'] === 1 ? 'Leida' : 'Pendiente') . " - {$row['mensaje']}"
    );
}

function ada_contexto_resumen(mysqli $conn, string $rol): string
{
    if ($rol === 'Administrador') {
        $facturasPendientes = ada_table_exists($conn, 'facturas_compra')
            ? ada_fetch_value($conn, "SELECT COUNT(*) FROM facturas_compra WHERE estado = 'Registrada'")
            : ada_fetch_value($conn, "SELECT COUNT(*) FROM factura WHERE estado = 'Pendiente'");

        return "Resumen del sistema:\n"
            . "Usuarios: " . ada_fetch_value($conn, 'SELECT COUNT(*) FROM usuarios') . "\n"
            . "Proveedores: " . ada_fetch_value($conn, 'SELECT COUNT(*) FROM proveedor') . "\n"
            . "Insumos: " . ada_fetch_value($conn, 'SELECT COUNT(*) FROM insumos_agricolas') . "\n"
            . "Cultivos: " . ada_fetch_value($conn, 'SELECT COUNT(*) FROM cultivos') . "\n"
            . "Lotes: " . ada_fetch_value($conn, 'SELECT COUNT(*) FROM lotes') . "\n"
            . "Solicitudes pendientes: " . ada_fetch_value($conn, "SELECT COUNT(*) FROM productos_solicitud WHERE estado = 'Pendiente'") . "\n"
            . "Facturas pendientes: " . $facturasPendientes . "\n"
            . "Total facturado: $" . ada_numero(ada_table_exists($conn, 'facturas_compra')
                ? ada_fetch_value($conn, 'SELECT COALESCE(SUM(total), 0) FROM facturas_compra')
                : ada_fetch_value($conn, 'SELECT COALESCE(SUM(total), 0) FROM factura'));
    }

    if ($rol === 'Bodeguero') {
        return "Resumen de bodega:\n"
            . "Insumos: " . ada_fetch_value($conn, 'SELECT COUNT(*) FROM insumos_agricolas') . "\n"
            . "Stock bajo: " . ada_fetch_value($conn, 'SELECT COUNT(*) FROM insumos_agricolas WHERE cantidad <= 10') . "\n"
            . "Pedidos: " . ada_fetch_value($conn, 'SELECT COUNT(*) FROM pedidos') . "\n"
            . "Solicitudes aprobadas por atender: " . ada_fetch_value($conn, "SELECT COUNT(*) FROM productos_solicitud WHERE estado = 'Aprobado'");
    }

    $userId = (string) ($_SESSION['id_usuario'] ?? '');
    return "Resumen del agricultor:\n"
        . "Cultivos: " . ada_fetch_value($conn, 'SELECT COUNT(*) FROM cultivos WHERE id_usuario = ?', 's', [$userId]) . "\n"
        . "Solicitudes: " . ada_fetch_value($conn, 'SELECT COUNT(*) FROM productos_solicitud WHERE id_agricultor = ?', 's', [$userId]) . "\n"
        . "Produccion total: " . ada_numero(ada_fetch_value($conn, 'SELECT COALESCE(SUM(cantidad), 0) FROM productos_finales WHERE id_usuario = ?', 's', [$userId]));
}

function ada_contexto_permitido(mysqli $conn, string $rol, string $pregunta, array $intencion): string
{
    $contextos = [];
    $categoria = $intencion['categoria'] ?? ADA_INTENCION_AGRICOLA_GENERAL;

    if ($categoria === ADA_INTENCION_AGRICOLA_GENERAL) {
        return "Tipo de consulta: conocimiento agrícola general.\n"
            . "No se requiere contexto interno del sistema. Responde de forma educativa y tecnica sin revelar datos internos.";
    }

    if ($categoria === ADA_INTENCION_ACCION_ADMINISTRATIVA) {
        return "Tipo de consulta: accion administrativa autorizada para el rol {$rol}.\n"
            . "ADA no ejecuta cambios directamente desde el chat. Orienta al usuario sobre el modulo o procedimiento disponible en SEMBRIEXPORT.";
    }

    if (ada_contiene_tema($pregunta, ['resumen', 'general', 'dashboard', 'panel', 'informacion', 'todo'])) {
        $contextos[] = ada_contexto_resumen($conn, $rol);
    }

    if ($rol === 'Administrador' && ada_contiene_tema($pregunta, ['usuario', 'usuarios', 'empleado', 'empleados'])) {
        $contextos[] = ada_contexto_usuarios($conn);
    }

    if (in_array($rol, ['Administrador', 'Bodeguero'], true) && ada_contiene_tema($pregunta, ['insumo', 'insumos', 'stock', 'inventario', 'bodega'])) {
        $contextos[] = ada_contexto_inventario($conn);
    }

    if (in_array($rol, ['Administrador', 'Bodeguero'], true) && ada_contiene_tema($pregunta, ['proveedor', 'proveedores'])) {
        $contextos[] = ada_contexto_proveedores($conn);
    }

    if (in_array($rol, ['Administrador', 'Bodeguero'], true) && ada_contiene_tema($pregunta, ['pedido', 'pedidos'])) {
        $contextos[] = ada_contexto_pedidos($conn);
    }

    if (in_array($rol, ['Administrador', 'Bodeguero'], true) && ada_contiene_tema($pregunta, ['factura', 'facturas', 'financiero'])) {
        $contextos[] = ada_contexto_facturas($conn);
    }

    if (in_array($rol, ['Administrador', 'Bodeguero'], true) && ada_contiene_tema($pregunta, ['movimiento', 'movimientos', 'entrada', 'salida'])) {
        $contextos[] = ada_contexto_movimientos($conn);
    }

    if ($rol !== 'Bodeguero' && ada_contiene_tema($pregunta, ['cultivo', 'cultivos', 'lote', 'lotes', 'siembra', 'riego'])) {
        $contextos[] = ada_contexto_cultivos_lotes($conn, $rol);
    }

    if (ada_contiene_tema($pregunta, ['solicitud', 'solicitudes', 'requerimiento', 'requerimientos'])) {
        $contextos[] = ada_contexto_solicitudes($conn, $rol);
    }

    if ($rol !== 'Bodeguero' && ada_contiene_tema($pregunta, [
        'produccion', 'producido', 'produjo', 'produjeron', 'rendimiento',
        'cosecha', 'cosechado', 'producto final', 'productos finales',
    ])) {
        $contextos[] = ada_contexto_produccion($conn, $rol);
    }

    if ($rol !== 'Bodeguero' && ada_contiene_tema($pregunta, ['plaga', 'plagas'])) {
        $contextos[] = ada_contexto_plagas($conn, $rol);
    }

    if ($rol !== 'Bodeguero' && ada_contiene_tema($pregunta, [
        'monitoreo', 'monitoreos', 'seguimiento', 'estado del lote',
        'etapa', 'etapas', 'avance', 'informacion de los lotes', 'datos de los lotes',
    ])) {
        $contextos[] = ada_contexto_monitoreo($conn, $rol);
    }

    if ($rol !== 'Bodeguero' && ada_contiene_tema($pregunta, [
        'actividad', 'actividades', 'labor', 'labores', 'tarea', 'tareas',
        'pendiente', 'pendientes', 'historial', 'actividad reciente',
    ])) {
        $contextos[] = ada_contexto_actividades($conn, $rol);
    }

    if ($rol === 'Administrador' && ada_contiene_tema($pregunta, ['notificacion', 'notificaciones', 'alerta', 'alertas'])) {
        $contextos[] = ada_contexto_notificaciones($conn);
    }

    if (!$contextos) {
        $contextos[] = ada_contexto_resumen($conn, $rol);
    }

    return implode("\n\n", $contextos);
}

function ada_reglas_permisos(string $rol): string
{
    $flujoSolicitudes = "\nFlujo de solicitudes: Agricultor crea en Pendiente; Administrador solo puede pasar Pendiente a Aprobado o Rechazado; Bodeguero solo puede pasar Aprobado a Entregado o Cancelado. Solo la entrega descuenta stock.";

    if (ada_normalizar_texto($rol) === 'administrador') {
        return "Rol del usuario: {$rol}\n"
            . "Conocimiento agricola general: permitido\n"
            . "Consultas de datos internos: acceso completo al contexto permitido por el sistema\n"
            . "Acciones administrativas: autorizadas segun los modulos disponibles\n"
            . "Reglas: responde solo con el contexto disponible para datos internos. No muestres contrasenas. No inventes datos. Si la informacion no aparece en el contexto, dilo claramente."
            . $flujoSolicitudes;
    }

    $permisos = ada_permisos_por_rol($rol);

    return "Rol del usuario: {$rol}\n"
        . "Conocimiento agricola general: permitido\n"
        . "Consultas internas permitidas: " . implode(', ', $permisos['permitidos']) . "\n"
        . "Consultas internas restringidas: " . implode(', ', $permisos['restringidos']) . "\n"
        . "Acciones administrativas: no autorizadas salvo que aparezcan como permitidas\n"
        . "Reglas: responde solo con el contexto permitido. No muestres contrasenas. No inventes datos. Si la informacion no aparece en el contexto, dilo claramente."
        . $flujoSolicitudes;
}

function ada_llamar_gemini(string $rol, string $pregunta, string $contexto): string
{
    if (!function_exists('curl_init')) {
        throw new RuntimeException('La extensión cURL de PHP no está habilitada.');
    }

    $prompt = ADA_SYSTEM_PROMPT . "\n\n"
        . "Identidad interna del asistente: ADA. No repitas esta identidad salvo que el usuario pregunte quien eres.\n"
        . ada_reglas_permisos($rol) . "\n\n"
        . "Contexto permitido del sistema SEMBRIEXPORT:\n{$contexto}\n\n"
        . "Pregunta del usuario:\n{$pregunta}";

    $payload = [
        'contents' => [
            [
                'role' => 'user',
                'parts' => [
                    ['text' => $prompt],
                ],
            ],
        ],
        'generationConfig' => [
            'temperature' => 0.2,
            'maxOutputTokens' => 1000,
        ],
    ];

    $ch = curl_init(GEMINI_ENDPOINT);

    if ($ch === false) {
        throw new RuntimeException('No se pudo inicializar cURL.');
    }

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 30,
    ]);

    $rawResponse = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($rawResponse === false || $curlError !== '') {
        throw new RuntimeException('Error de conexión con Gemini: ' . $curlError);
    }

    $data = json_decode($rawResponse, true);

    if ($httpCode < 200 || $httpCode >= 300) {
        $message = $data['error']['message'] ?? 'Gemini no aceptó la solicitud.';
        throw new RuntimeException($message);
    }

    if (!is_array($data) || empty($data['candidates'][0]['content']['parts'])) {
        throw new RuntimeException('Respuesta inválida de Gemini.');
    }

    $texto = '';

    foreach ($data['candidates'][0]['content']['parts'] as $part) {
        if (isset($part['text'])) {
            $texto .= $part['text'];
        }
    }

    $texto = trim($texto);

    if ($texto === '') {
        throw new RuntimeException('Gemini respondió sin texto.');
    }

    return $texto;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ada_json(false, 'Método no permitido. Envía la pregunta por POST.', 405);
}

$rol = $_SESSION['rol'] ?? 'Invitado';
$pregunta = ada_pregunta_recibida();

if ($pregunta === '') {
    ada_json(false, 'Escribe una pregunta para que ADA pueda ayudarte.', 422, $rol);
}

$intencion = ada_clasificar_intencion($pregunta);

if (!ada_intencion_autorizada($rol, $intencion)) {
    ada_json(false, ADA_DENIED_MESSAGE, 403, $rol);
}

if (!ada_api_key_configurada()) {
    ada_json(false, 'La API Key de Gemini no está configurada. Actualiza asistente/config_gemini.php.', 500, $rol);
}

try {
    $contexto = ada_contexto_permitido($conn, $rol, $pregunta, $intencion);
    $respuesta = ada_llamar_gemini($rol, $pregunta, $contexto);
    ada_json(true, $respuesta, 200, $rol);
} catch (Throwable $exception) {
    error_log('ADA Gemini error: ' . $exception->getMessage());
    ada_json(false, 'No pude comunicarme correctamente con Gemini. ' . $exception->getMessage(), 500, $rol);
}
