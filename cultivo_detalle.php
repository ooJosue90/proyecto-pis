<?php
require_once 'conexion.php';
require_auth('Administrador');

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    echo "<div class='alert alert-danger'>ID de cultivo inválido.</div>";
    exit;
}
$sql = "SELECT c.*, u.nombre as agricultor,
               COUNT(l.id_lote) AS total_lotes,
               SUM(CASE WHEN l.estado_cultivo = 'activo' THEN 1 ELSE 0 END) AS lotes_activos,
               SUM(CASE WHEN l.estado_cultivo = 'en_cosecha' THEN 1 ELSE 0 END) AS lotes_en_cosecha,
               SUM(CASE WHEN l.estado_cultivo = 'finalizado' THEN 1 ELSE 0 END) AS lotes_finalizados,
               SUM(CASE WHEN l.estado_cultivo = 'cancelado' THEN 1 ELSE 0 END) AS lotes_cancelados,
               MAX(l.fecha_fin_cosecha_real) AS ultima_cosecha_real
        FROM cultivos c 
        LEFT JOIN usuarios u ON c.id_usuario = u.id_usuario 
        LEFT JOIN lotes l ON c.id_cultivo = l.id_cultivo
        WHERE c.id_cultivo = ?
        GROUP BY c.id_cultivo";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    // Calcular días transcurridos
    $fecha_siembra = strtotime($row['fecha_siembra']);
    $dias_transcurridos = floor((time() - $fecha_siembra) / (60*60*24));
    
    // El estado operativo depende de los lotes, no solo del tiempo transcurrido.
    if ((int) $row['lotes_en_cosecha'] > 0) {
        $estado = '<span class="admin-crop-status admin-crop-status--harvest"><i></i>En cosecha</span>';
    } elseif ((int) $row['total_lotes'] > 0 && (int) $row['lotes_finalizados'] === (int) $row['total_lotes']) {
        $estado = '<span class="admin-crop-status admin-crop-status--finished"><i></i>Finalizado</span>';
    } elseif ((int) $row['total_lotes'] > 0 && (int) $row['lotes_cancelados'] === (int) $row['total_lotes']) {
        $estado = '<span class="admin-crop-status admin-crop-status--cancelled"><i></i>Cancelado</span>';
    } else {
        $estado = '<span class="admin-crop-status admin-crop-status--active"><i></i>Activo</span>';
    }
    
    echo "<div class='admin-crop-detail'>";
    echo "<div class='admin-crop-detail__hero'>";
    echo "<span class='admin-crop-detail__hero-icon'><i class='fas fa-seedling'></i></span>";
    echo "<div><small>Cultivo registrado</small><h3>" . htmlspecialchars($row['tipo']) . "</h3><p>Sembrado el " . date('d/m/Y', strtotime($row['fecha_siembra'])) . "</p></div>";
    echo "<span class='admin-crop-detail__status'>$estado</span>";
    echo "</div>";
    echo "<div class='admin-crop-detail__grid'>";
    echo "<article><span><i class='fas fa-hashtag'></i> Identificador</span><strong>#{$row['id_cultivo']}</strong></article>";
    echo "<article><span><i class='fas fa-user'></i> Agricultor</span><strong>" . htmlspecialchars($row['agricultor'] ?: 'No asignado') . "</strong></article>";
    echo "<article><span><i class='fas fa-calendar-days'></i> Fecha de siembra</span><strong>" . date('d/m/Y', strtotime($row['fecha_siembra'])) . "</strong></article>";
    echo "<article><span><i class='fas fa-clock'></i> Ciclo transcurrido</span><strong>$dias_transcurridos días</strong></article>";
    echo "<article><span><i class='fas fa-map'></i> Lotes</span><strong>" . (int) $row['total_lotes'] . "</strong></article>";
    if ($row['ultima_cosecha_real']) {
        echo "<article><span><i class='fas fa-calendar-check'></i> Última cosecha real</span><strong>" . date('d/m/Y', strtotime($row['ultima_cosecha_real'])) . "</strong></article>";
    }
    echo "</div>";
    
    // Información adicional si existe
    if (isset($row['descripcion']) && !empty($row['descripcion'])) {
        echo "<div class='admin-crop-detail__description'><span>Descripción</span><p>" . htmlspecialchars($row['descripcion']) . "</p></div>";
    }
    echo "</div>";
} else {
    echo "<div class='alert alert-danger'>No se encontraron detalles del cultivo</div>";
}

$conn->close();
?>
