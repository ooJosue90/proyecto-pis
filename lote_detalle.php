<?php
require_once 'conexion.php';
require_auth('Administrador');

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    echo "<div class='alert alert-danger'>ID de lote inválido.</div>";
    exit;
}
$sql = "SELECT l.*, c.tipo as cultivo, c.fecha_siembra, u.nombre as agricultor 
        FROM lotes l
        LEFT JOIN cultivos c ON l.id_cultivo = c.id_cultivo
        LEFT JOIN usuarios u ON c.id_usuario = u.id_usuario
        WHERE l.id_lote = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $etapa = match ((int) $row['etapa_actual']) {
        1 => 'Siembra',
        2 => 'Desarrollo',
        3 => 'Cosecha',
        default => 'Sin etapa',
    };
    $estado = match ($row['estado_cultivo']) {
        'en_cosecha' => 'En cosecha',
        'finalizado' => 'Finalizado',
        'cancelado' => 'Cancelado',
        default => 'Activo',
    };

    echo "<div class='admin-crop-detail'>";
    echo "<div class='admin-crop-detail__hero admin-crop-detail__hero--lot'>";
    echo "<span class='admin-crop-detail__hero-icon'><i class='fas fa-map-location-dot'></i></span>";
    echo "<div><small>Lote registrado</small><h3>" . htmlspecialchars($row['ubicacion']) . "</h3><p>" . htmlspecialchars($row['area']) . "</p></div>";
    echo "<span class='admin-crop-detail__lot-badge'>Lote #{$row['id_lote']}</span>";
    echo "</div>";
    echo "<div class='admin-crop-detail__grid'>";
    echo "<article><span><i class='fas fa-location-dot'></i> Ubicación</span><strong>" . htmlspecialchars($row['ubicacion']) . "</strong></article>";
    echo "<article><span><i class='fas fa-ruler-combined'></i> Área / zona</span><strong>" . htmlspecialchars($row['area']) . "</strong></article>";
    echo "<article><span><i class='fas fa-seedling'></i> Cultivo</span><strong>" . htmlspecialchars($row['cultivo'] ?: 'Sin cultivo') . "</strong></article>";
    echo "<article><span><i class='fas fa-user'></i> Agricultor</span><strong>" . htmlspecialchars($row['agricultor'] ?: 'No asignado') . "</strong></article>";
    echo "<article><span><i class='fas fa-list-check'></i> Etapa actual</span><strong>" . htmlspecialchars($etapa) . "</strong></article>";
    echo "<article><span><i class='fas fa-circle-info'></i> Estado</span><strong>" . htmlspecialchars($estado) . "</strong></article>";
    if ($row['fecha_siembra']) {
        echo "<article><span><i class='fas fa-calendar-days'></i> Fecha de siembra</span><strong>" . date('d/m/Y', strtotime($row['fecha_siembra'])) . "</strong></article>";
    }
    if ($row['fecha_fin_cosecha_real']) {
        echo "<article><span><i class='fas fa-calendar-check'></i> Cosecha finalizada</span><strong>" . date('d/m/Y', strtotime($row['fecha_fin_cosecha_real'])) . "</strong></article>";
    }
    echo "</div>";
    
    // Información adicional si existe
    if (isset($row['descripcion']) && !empty($row['descripcion'])) {
        echo "<div class='admin-crop-detail__description'><span>Descripción</span><p>" . htmlspecialchars($row['descripcion']) . "</p></div>";
    }
    echo "</div>";
} else {
    echo "<div class='alert alert-danger'>No se encontraron detalles del lote</div>";
}

$conn->close();
?>
