<?php

function load_farmer_dashboard_data(mysqli $conn, string $userId): array
{
    $cultivos = db_fetch_all(
        $conn,
        "SELECT * FROM cultivos WHERE id_usuario = ? ORDER BY fecha_siembra DESC",
        "s",
        [$userId]
    );

    $lotes = db_fetch_all($conn, "
        SELECT l.*, c.tipo AS tipo_cultivo,
               GROUP_CONCAT(
                   DISTINCT CONCAT(cf.nombre_problema, ' (', cf.estado, ')')
                   ORDER BY cf.fecha_registro DESC
                   SEPARATOR ', '
               ) AS problemas_fitosanitarios
        FROM lotes l
        LEFT JOIN cultivos c ON l.id_cultivo = c.id_cultivo
        LEFT JOIN control_fitosanitario cf ON l.id_lote = cf.id_lote
        WHERE c.id_usuario = ?
        GROUP BY l.id_lote
        ORDER BY l.id_lote DESC
    ", "s", [$userId]);

    $insumos = db_fetch_all(
        $conn,
        "SELECT id_insumos, nombre, cantidad, unidad_medida FROM insumos_agricolas ORDER BY nombre"
    );

    $etapas = ['Siembra' => 0, 'Desarrollo' => 0, 'Cosecha' => 0];
    foreach ($lotes as $lote) {
        $stage = (int) $lote['etapa_actual'];
        if ($stage === 1) {
            $etapas['Siembra']++;
        } elseif ($stage === 2) {
            $etapas['Desarrollo']++;
        } elseif ($stage === 3) {
            $etapas['Cosecha']++;
        }
    }

    return [
        'cultivos' => $cultivos,
        'lotes' => $lotes,
        'insumos' => $insumos,
        'total_lotes' => count($lotes),
        'etapas' => $etapas,
    ];
}
