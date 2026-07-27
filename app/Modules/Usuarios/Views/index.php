<?php declare(strict_types=1);
?>

<section class="admin-users" data-users-csrf="<?= e($csrfToken) ?>">
    <header class="admin-users__header">
        <div class="admin-users__title">
            <span class="admin-users__header-icon"><i class="fas fa-users-gear"></i></span>
            <div>
                <span class="admin-section-eyebrow">Accesos</span>
                <h4>Gestión de usuarios</h4>
                <p>Administra identidades, roles y permisos del sistema.</p>
            </div>
        </div>
        <button class="admin-users__new-button" type="button" data-bs-toggle="modal" data-bs-target="#modalCrearUsuario" data-app-no-ripple>
            <i class="fas fa-user-check"></i>
            <span>Nuevo usuario</span>
        </button>
    </header>
                <div class="row admin-users__metrics">
                    <div class="col-md-3 col-sm-6">
                        <article class="admin-users__metric">
                            <span class="admin-users__metric-icon admin-users__metric-icon--total"><i class="fas fa-users-gear"></i></span>
                            <div>
                                <span>Total cuentas</span>
                                <strong><?= $total_usuarios ?></strong>
                            </div>
                        </article>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <article class="admin-users__metric">
                            <span class="admin-users__metric-icon admin-users__metric-icon--admin"><i class="fas fa-user-shield"></i></span>
                            <div>
                                <span>Administradores</span>
                                <strong><?= $usuarios_por_rol['Administrador'] ?></strong>
                            </div>
                        </article>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <article class="admin-users__metric">
                            <span class="admin-users__metric-icon admin-users__metric-icon--farmer"><i class="fas fa-seedling"></i></span>
                            <div>
                                <span>Agricultores</span>
                                <strong><?= $usuarios_por_rol['Agricultor'] ?></strong>
                            </div>
                        </article>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <article class="admin-users__metric">
                            <span class="admin-users__metric-icon admin-users__metric-icon--warehouse"><i class="fas fa-box-archive"></i></span>
                            <div>
                                <span>Bodegueros</span>
                                <strong><?= $usuarios_por_rol['Bodeguero'] ?></strong>
                            </div>
                        </article>
                    </div>
                </div>

                <section class="admin-users__panel" aria-label="Lista de usuarios">
                    <div class="admin-users__panel-heading">
                        <span class="admin-users__panel-icon"><i class="fas fa-address-book"></i></span>
                        <div>
                            <h5>Directorio completo</h5>
                            <p><?= $ultimo_registro ? 'Último registro: ' . date('d/m/Y', strtotime($ultimo_registro)) : 'Sin registros recientes' ?></p>
                        </div>
                    </div>

                    <div class="table-responsive admin-users__table-wrap">
                        <table class="table admin-users__table" data-app-table-owner="admin-users-table">
                            <thead>
                            <tr>
                                <th>ID/Cédula</th>
                                <th>Nombre</th>
                                <th>Email</th>
                                <th>Rol</th>
                                <th>Fecha Registro</th>
                                <th>Acciones</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($usuarios_rows as $usuario): ?>
                                <?php
                                    $rol = $usuario['rol'] ?? '';
                                    $rol_class = $rol === 'Administrador'
                                        ? 'admin-users__role--admin'
                                        : ($rol === 'Agricultor' ? 'admin-users__role--farmer' : 'admin-users__role--warehouse');
                                ?>
                                <tr>
                                    <td>
                                        <span class="admin-users__id"><?= htmlspecialchars($usuario['id_usuario']) ?></span>
                                    </td>
                                    <td>
                                        <div class="admin-users__person">
                                            <span><?= htmlspecialchars($usuario['nombre']) ?></span>
                                            <small>Cuenta activa</small>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($usuario['email']) ?></td>
                                    <td>
                                        <span class="admin-users__role <?= $rol_class ?>"><?= htmlspecialchars($rol) ?></span>
                                    </td>
                                    <td>
                                        <?php
                                        if (isset($usuario['fecha_registro'])) {
                                            echo date('d/m/Y', strtotime($usuario['fecha_registro']));
                                        } else {
                                            echo 'N/A';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <div class="admin-users__actions">
                                            <button class="admin-users__action admin-users__action--edit" type="button" onclick="editarUsuario(<?= htmlspecialchars(json_encode($usuario['id_usuario']), ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars(json_encode($usuario['nombre']), ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars(json_encode($usuario['email']), ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars(json_encode($usuario['rol']), ENT_QUOTES, 'UTF-8') ?>)" aria-label="Editar usuario <?= htmlspecialchars($usuario['nombre'], ENT_QUOTES, 'UTF-8') ?>">
                                            <i class="fas fa-user-pen"></i>
                                        </button>
                                        <?php if ($usuario['id_usuario'] != '1'): ?>
                                            <button
                                                class="admin-users__action admin-users__action--delete"
                                                type="button"
                                                data-user-id="<?= htmlspecialchars($usuario['id_usuario'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-user-name="<?= htmlspecialchars($usuario['nombre'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-user-email="<?= htmlspecialchars($usuario['email'], ENT_QUOTES, 'UTF-8') ?>"
                                                onclick="eliminarUsuario(this)"
                                                aria-label="Eliminar usuario <?= htmlspecialchars($usuario['nombre'], ENT_QUOTES, 'UTF-8') ?>"
                                            >
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
</section>
