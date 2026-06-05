<?php
require_once 'conexion.php';
require_auth('Administrador');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<div class='alert alert-danger'>ID de lote inválido.</div>";
    exit();
}

$id_lote = intval($_GET['id']);

// Obtener historial de solicitudes para el lote
$sql = "
    SELECT ps.*, u.nombre as agricultor_nombre
    FROM productos_solicitud ps
    JOIN usuarios u ON ps.id_agricultor = u.id_usuario
    WHERE ps.id_lote = ?
    ORDER BY ps.fecha DESC
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo "<div class='alert alert-danger'>Error en la consulta: " . $conn->error . "</div>";
    exit();
}
$stmt->bind_param("i", $id_lote);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<div class='alert alert-info'>No hay historial para este lote.</div>";
    exit();
}

echo "<table class='table table-striped'>";
echo "<thead><tr><th>ID</th><th>Agricultor</th><th>Producto</th><th>Cantidad</th><th>Estado</th><th>Fecha</th></tr></thead><tbody>";

while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['id_producto_solicitud']) . "</td>";
    echo "<td>" . htmlspecialchars($row['agricultor_nombre']) . "</td>";
    echo "<td>" . htmlspecialchars($row['nombre']) . "</td>";
    echo "<td>" . htmlspecialchars($row['cantidad_solicitada']) . "</td>";
    echo "<td>" . htmlspecialchars($row['estado']) . "</td>";
    echo "<td>" . htmlspecialchars($row['fecha']) . "</td>";
    echo "</tr>";
}

echo "</tbody></table>";

$stmt->close();
$conn->close();
?>
