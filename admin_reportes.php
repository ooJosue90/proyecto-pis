<?php
require_once 'conexion.php';
require_auth('Administrador');

// Obtener datos para reportes
$total_usuarios = $conn->query("SELECT COUNT(*) as count FROM usuarios")->fetch_assoc()['count'];
$total_cultivos = $conn->query("SELECT COUNT(*) as count FROM cultivos")->fetch_assoc()['count'];
$total_lotes = $conn->query("SELECT COUNT(*) as count FROM lotes")->fetch_assoc()['count'];

// Usuarios por rol
$usuarios_por_rol = $conn->query("
    SELECT rol, COUNT(*) as cantidad 
    FROM usuarios 
    GROUP BY rol 
    ORDER BY cantidad DESC
");

// Cultivos más recientes
$cultivos_recientes = $conn->query("
    SELECT c.tipo, c.fecha_siembra, u.nombre as agricultor 
    FROM cultivos c 
    JOIN usuarios u ON c.id_usuario = u.id_usuario 
    ORDER BY c.fecha_siembra DESC 
    LIMIT 10
");

// Cultivos por tipo
$cultivos_por_tipo = $conn->query("
    SELECT tipo, COUNT(*) as cantidad 
    FROM cultivos 
    GROUP BY tipo 
    ORDER BY cantidad DESC
");
?>

<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4><i class="fas fa-chart-bar"></i> Reportes del Sistema</h4>
            </div>
            <div class="card-body">
                <!-- Resumen Estadístico -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card bg-primary text-white">
                            <div class="card-body text-center">
                                <i class="fas fa-users fa-2x mb-2"></i>
                                <h3><?php echo $total_usuarios; ?></h3>
                                <p>Total Usuarios</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-success text-white">
                            <div class="card-body text-center">
                                <i class="fas fa-leaf fa-2x mb-2"></i>
                                <h3><?php echo $total_cultivos; ?></h3>
                                <p>Cultivos Registrados</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-warning text-white">
                            <div class="card-body text-center">
                                <i class="fas fa-map-marked-alt fa-2x mb-2"></i>
                                <h3><?php echo $total_lotes; ?></h3>
                                <p>Lotes Activos</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabs para diferentes reportes -->
                <ul class="nav nav-tabs" id="reportTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="usuarios-tab" data-bs-toggle="tab" data-bs-target="#usuarios-report" type="button">
                            <i class="fas fa-users"></i> Usuarios
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="cultivos-tab" data-bs-toggle="tab" data-bs-target="#cultivos-report" type="button">
                            <i class="fas fa-seedling"></i> Cultivos
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="actividad-tab" data-bs-toggle="tab" data-bs-target="#actividad-report" type="button">
                            <i class="fas fa-chart-line"></i> Actividad Reciente
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="reportTabsContent">
                    <!-- Reporte de Usuarios -->
                    <div class="tab-pane fade show active" id="usuarios-report">
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <h5>Distribución por Rol</h5>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Rol</th>
                                                <th>Cantidad</th>
                                                <th>Porcentaje</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($row = $usuarios_por_rol->fetch_assoc()): 
                                                $porcentaje = round(($row['cantidad'] / $total_usuarios) * 100, 1);
                                            ?>
                                            <tr>
                                                <td><?php echo $row['rol']; ?></td>
                                                <td><?php echo $row['cantidad']; ?></td>
                                                <td>
                                                    <div class="progress" data-progress="<?php echo $porcentaje; ?>">
                                                        <div class="progress-bar">
                                                            <?php echo $porcentaje; ?>%
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h5>Lista Completa de Usuarios</h5>
                                <div class="table-responsive app-scroll-panel-sm">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Nombre</th>
                                                <th>Email</th>
                                                <th>Rol</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $all_users = $conn->query("SELECT nombre, email, rol FROM usuarios ORDER BY nombre");
                                            while ($user = $all_users->fetch_assoc()): 
                                            ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($user['nombre']); ?></td>
                                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $user['rol'] == 'Administrador' ? 'primary' : 
                                                            ($user['rol'] == 'Agricultor' ? 'success' : 'warning'); 
                                                    ?> badge-sm">
                                                        <?php echo $user['rol']; ?>
                                                    </span>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Reporte de Cultivos -->
                    <div class="tab-pane fade" id="cultivos-report">
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <h5>Cultivos por Tipo</h5>
                                <?php if ($cultivos_por_tipo->num_rows > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Tipo de Cultivo</th>
                                                <th>Cantidad</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($row = $cultivos_por_tipo->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($row['tipo']); ?></td>
                                                <td><?php echo $row['cantidad']; ?></td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php else: ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> No hay cultivos registrados todavía.
                                    <br><small>Los agricultores pueden registrar cultivos desde su panel.</small>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <h5>Últimos Cultivos Registrados</h5>
                                <?php if ($cultivos_recientes->num_rows > 0): ?>
                                <div class="table-responsive app-scroll-panel-sm">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Tipo</th>
                                                <th>Fecha Siembra</th>
                                                <th>Agricultor</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($row = $cultivos_recientes->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($row['tipo']); ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($row['fecha_siembra'])); ?></td>
                                                <td><?php echo htmlspecialchars($row['agricultor']); ?></td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Reporte de Actividad Reciente -->
                    <div class="tab-pane fade" id="actividad-report">
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <h5>Última Actividad</h5>
                                <?php
                                // ejemplo: combinamos actividades de usuarios y cultivos
                                $actividad = $conn->query("
                                    SELECT 'usuario' AS tipo, nombre AS descripcion, fecha_registro AS fecha 
                                    FROM usuarios
                                    UNION ALL
                                    SELECT 'cultivo' AS tipo, tipo AS descripcion, fecha_siembra AS fecha 
                                    FROM cultivos
                                    ORDER BY fecha DESC
                                    LIMIT 10
                                ");
                                ?>

                                <?php if ($actividad && $actividad->num_rows > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Tipo</th>
                                                <th>Descripción</th>
                                                <th>Fecha</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($row = $actividad->fetch_assoc()): ?>
                                            <tr>
                                                <td>
                                                    <span class="badge bg-<?php echo $row['tipo']=='usuario' ? 'primary':'success'; ?>">
                                                        <?php echo ucfirst($row['tipo']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($row['descripcion']); ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($row['fecha'])); ?></td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php else: ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> No hay actividad reciente registrada.
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
