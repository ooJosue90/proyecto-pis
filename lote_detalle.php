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
    echo "<div class='row'>";
    echo "<div class='col-md-6'>";
    echo "<p><strong>ID Lote:</strong> {$row['id_lote']}</p>";
    echo "<p><strong>Ubicación:</strong> " . htmlspecialchars($row['ubicacion']) . "</p>";
    echo "<p><strong>Área/Zona:</strong> " . htmlspecialchars($row['area']) . "</p>";
    echo "</div>";
    echo "<div class='col-md-6'>";
    echo "<p><strong>Cultivo:</strong> " . htmlspecialchars($row['cultivo'] ?: 'Sin cultivo') . "</p>";
    echo "<p><strong>Agricultor:</strong> " . htmlspecialchars($row['agricultor'] ?: 'No asignado') . "</p>";
    
    if ($row['fecha_siembra']) {
        echo "<p><strong>Fecha siembra:</strong> " . date('d/m/Y', strtotime($row['fecha_siembra'])) . "</p>";
    }
    echo "</div>";
    echo "</div>";
    
    // Información adicional si existe
    if (isset($row['descripcion']) && !empty($row['descripcion'])) {
        echo "<hr>";
        echo "<p><strong>Descripción:</strong> " . htmlspecialchars($row['descripcion']) . "</p>";
    }
} else {
    echo "<div class='alert alert-danger'>No se encontraron detalles del lote</div>";
}

$conn->close();
?>
