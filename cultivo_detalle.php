<?php
require_once 'conexion.php';
require_auth('Administrador');

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    echo "<div class='alert alert-danger'>ID de cultivo inválido.</div>";
    exit;
}
$sql = "SELECT c.*, u.nombre as agricultor 
        FROM cultivos c 
        LEFT JOIN usuarios u ON c.id_usuario = u.id_usuario 
        WHERE c.id_cultivo = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    // Calcular días transcurridos
    $fecha_siembra = strtotime($row['fecha_siembra']);
    $dias_transcurridos = floor((time() - $fecha_siembra) / (60*60*24));
    
    // Determinar estado del cultivo
    if ($dias_transcurridos < 30) {
        $estado = '<span class="badge bg-info">Recién plantado</span>';
    } elseif ($dias_transcurridos < 180) {
        $estado = '<span class="badge bg-warning">En desarrollo</span>';
    } else {
        $estado = '<span class="badge bg-success">Maduro</span>';
    }
    
    echo "<div class='row'>";
    echo "<div class='col-md-6'>";
    echo "<p><strong>ID:</strong> {$row['id_cultivo']}</p>";
    echo "<p><strong>Tipo:</strong> " . htmlspecialchars($row['tipo']) . "</p>";
    echo "<p><strong>Fecha siembra:</strong> " . date('d/m/Y', strtotime($row['fecha_siembra'])) . "</p>";
    echo "<p><strong>Agricultor:</strong> " . htmlspecialchars($row['agricultor'] ?: 'No asignado') . "</p>";
    echo "</div>";
    echo "<div class='col-md-6'>";
    echo "<p><strong>Estado:</strong> $estado</p>";
    echo "<p><strong>Días transcurridos:</strong> $dias_transcurridos días</p>";
    echo "</div>";
    echo "</div>";
    
    // Información adicional si existe
    if (isset($row['descripcion']) && !empty($row['descripcion'])) {
        echo "<hr>";
        echo "<p><strong>Descripción:</strong> " . htmlspecialchars($row['descripcion']) . "</p>";
    }
} else {
    echo "<div class='alert alert-danger'>No se encontraron detalles del cultivo</div>";
}

$conn->close();
?>
