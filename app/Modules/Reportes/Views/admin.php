<?php declare(strict_types=1);
?>

<section class="admin-reports">
    <header class="admin-reports__header">
        <span class="admin-reports__header-icon"><i class="fas fa-chart-simple"></i></span>
        <div>
            <span class="admin-section-eyebrow">Analítica administrativa</span>
            <h4>Reportes del Sistema</h4>
            <p>Indicadores de usuarios, cultivos y actividad reciente.</p>
        </div>
    </header>
                <!-- Resumen Estadístico -->
                <div class="row mb-4 admin-reports__metrics">
                    <div class="col-md-4">
                        <article class="admin-reports__metric admin-reports__metric--users">
                            <span class="admin-reports__metric-icon"><i class="fas fa-users-gear"></i></span>
                            <div>
                                <span>Resumen de cuentas</span>
                                <h3><?php echo $total_usuarios; ?></h3>
                                <p>Total Usuarios</p>
                            </div>
                        </article>
                    </div>
                    <div class="col-md-4">
                        <article class="admin-reports__metric admin-reports__metric--crops">
                            <span class="admin-reports__metric-icon"><i class="fas fa-seedling"></i></span>
                            <div>
                                <span>Producción registrada</span>
                                <h3><?php echo $total_cultivos; ?></h3>
                                <p>Cultivos Registrados</p>
                            </div>
                        </article>
                    </div>
                    <div class="col-md-4">
                        <article class="admin-reports__metric admin-reports__metric--lots">
                            <span class="admin-reports__metric-icon"><i class="fas fa-map-location-dot"></i></span>
                            <div>
                                <span>Superficie operativa</span>
                                <h3><?php echo $total_lotes; ?></h3>
                                <p>Lotes Activos</p>
                            </div>
                        </article>
                    </div>
                </div>

                <!-- Tabs para diferentes reportes -->
                <ul class="nav nav-tabs admin-reports__tabs" id="reportTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="usuarios-tab" data-bs-toggle="tab" data-bs-target="#usuarios-report" type="button">
                            <i class="fas fa-users-gear"></i> Usuarios
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

                <div class="tab-content admin-reports__content" id="reportTabsContent">
                    <!-- Reporte de Usuarios -->
                    <div class="tab-pane fade show active" id="usuarios-report">
                        <div class="row mt-3">
                            <div class="col-md-6 admin-report-panel">
                                <h5><span><i class="fas fa-chart-simple"></i></span> Distribución por Rol</h5>
                                <div class="table-responsive">
                                    <table class="table table-sm" data-app-table-owner="report-users-role">
                                        <thead>
                                            <tr>
                                                <th>Rol</th>
                                                <th>Cantidad</th>
                                                <th>Porcentaje</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($usuarios_por_rol as $row):
                                                $porcentaje = $total_usuarios > 0 ? round(($row['cantidad'] / $total_usuarios) * 100, 1) : 0;
                                            ?>
                                            <tr>
                                                <td><?php echo e($row['rol']); ?></td>
                                                <td><?php echo (int) $row['cantidad']; ?></td>
                                                <td>
                                                    <div class="progress" data-progress="<?php echo $porcentaje; ?>">
                                                        <div class="progress-bar" style="width: <?php echo $porcentaje; ?>%;">
                                                            <?php echo $porcentaje; ?>%
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="col-md-6 admin-report-panel">
                                <h5><span><i class="fas fa-address-book"></i></span> Lista Completa de Usuarios</h5>
                                <div class="table-responsive app-scroll-panel-sm">
                                    <table class="table table-sm" data-app-table-owner="report-users-list">
                                        <thead>
                                            <tr>
                                                <th>Nombre</th>
                                                <th>Email</th>
                                                <th>Rol</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($all_users as $user): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($user['nombre']); ?></td>
                                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $user['rol'] == 'Administrador' ? 'primary' : 
                                                            ($user['rol'] == 'Agricultor' ? 'success' : 'warning'); 
                                                    ?> badge-sm">
                                                        <?php echo e($user['rol']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Reporte de Cultivos -->
                    <div class="tab-pane fade" id="cultivos-report">
                        <div class="row mt-3">
                            <div class="col-md-6 admin-report-panel">
                                <h5><span><i class="fas fa-chart-bar"></i></span> Cultivos por Tipo</h5>
                                <?php if ($cultivos_por_tipo !== []): ?>
                                <div class="table-responsive">
                                    <table class="table table-sm" data-app-table-owner="report-crops-type">
                                        <thead>
                                            <tr>
                                                <th>Tipo de Cultivo</th>
                                                <th>Cantidad</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($cultivos_por_tipo as $row): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($row['tipo']); ?></td>
                                                <td><?php echo (int) $row['cantidad']; ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php else: ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-circle-info"></i> No hay cultivos registrados todavía.
                                    <br><small>Los agricultores pueden registrar cultivos desde su panel.</small>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6 admin-report-panel">
                                <h5><span><i class="fas fa-clock-rotate-left"></i></span> Últimos Cultivos Registrados</h5>
                                <?php if ($cultivos_recientes !== []): ?>
                                <div class="table-responsive app-scroll-panel-sm">
                                    <table class="table table-sm" data-app-table-owner="report-crops-recent">
                                        <thead>
                                            <tr>
                                                <th>Tipo</th>
                                                <th>Fecha Siembra</th>
                                                <th>Agricultor</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($cultivos_recientes as $row): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($row['tipo']); ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($row['fecha_siembra'])); ?></td>
                                                <td><?php echo htmlspecialchars($row['agricultor']); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
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
                            <div class="col-md-12 admin-report-panel">
                                <h5><span><i class="fas fa-signal"></i></span> Última Actividad</h5>
                                <?php if ($actividad !== []): ?>
                                <div class="table-responsive">
                                    <table class="table table-sm" data-app-table-owner="report-activity">
                                        <thead>
                                            <tr>
                                                <th>Tipo</th>
                                                <th>Descripción</th>
                                                <th>Fecha</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($actividad as $row): ?>
                                            <tr>
                                                <td>
                                                    <span class="badge bg-<?php echo $row['tipo']=='usuario' ? 'primary':'success'; ?>">
                                                        <?php echo e(ucfirst((string) $row['tipo'])); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo e($row['descripcion']); ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($row['fecha'])); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php else: ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-circle-info"></i> No hay actividad reciente registrada.
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
</section>
