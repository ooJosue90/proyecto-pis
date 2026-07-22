<?php
require_once 'conexion.php';
require_once __DIR__ . '/includes/poscosecha_helpers.php';
require_once __DIR__ . '/includes/poscosecha_data.php';
require_once __DIR__ . '/includes/poscosecha_view.php';

poscosecha_require_role(['Agricultor', 'Bodeguero']);

$role = (string) $_SESSION['rol'];
$id_usuario = (string) $_SESSION['id_usuario'];
?>
<?php render_head('Poscosecha', [
    'https://fonts.googleapis.com/css2?family=Raleway:wght@500;600;700;800;900&family=Roboto+Condensed:wght@400;500;600;700;800;900&display=swap',
    'assets/vendor/bootstrap/bootstrap.min.css',
    'assets/vendor/fontawesome/css/all.min.css',
    'css/admin.css?v=' . filemtime(__DIR__ . '/css/admin.css'),
]); ?>
<body class="farmer-dashboard-page admin-dashboard-page farmer-admin-page posharvest-page">
    <div class="admin-tablet-shell">
        <aside class="sidebar" id="mainSidebar" aria-label="Navegación principal">
            <div class="logo-container">
                <div class="admin-sidebar-logo">
                    <span class="material-symbols-outlined" aria-hidden="true">agriculture</span>
                </div>
                <span class="nav-label admin-sidebar-brand">SembriExport</span>
            </div>

            <nav class="app-sidebar-nav admin-reference-nav">
                <?php if ($role === 'Bodeguero'): ?>
                    <a class="nav-item app-sidebar-link" href="bodeguero.php" title="Bodega">
                        <span class="material-symbols-outlined" aria-hidden="true">warehouse</span>
                        <span class="nav-label">Bodega</span>
                    </a>
                    <a class="nav-item app-sidebar-link" href="fitosanitario.php" title="Fitosanitario">
                        <span class="material-symbols-outlined" aria-hidden="true">health_and_safety</span>
                        <span class="nav-label">Fitosanitario</span>
                    </a>
                    <a class="nav-item app-sidebar-link" href="cosechas.php" title="Cosecha">
                        <span class="material-symbols-outlined" aria-hidden="true">agriculture</span>
                        <span class="nav-label">Cosecha</span>
                    </a>
                    <a class="nav-item app-sidebar-link active" href="poscosecha.php" title="Poscosecha">
                        <span class="material-symbols-outlined" aria-hidden="true">inventory_2</span>
                        <span class="nav-label">Poscosecha</span>
                    </a>
                    <a class="nav-item app-sidebar-link" href="bodeguero_facturas.php" title="Facturas">
                        <span class="material-symbols-outlined" aria-hidden="true">receipt_long</span>
                        <span class="nav-label">Facturas</span>
                    </a>
                    <a class="nav-item app-sidebar-link" href="imprimir_solicitudes.php" title="Solicitudes">
                        <span class="material-symbols-outlined" aria-hidden="true">assignment</span>
                        <span class="nav-label">Solicitudes</span>
                    </a>
                <?php else: ?>
                    <a class="nav-item app-sidebar-link" href="agricultor.php" title="Dashboard">
                        <span class="material-symbols-outlined" aria-hidden="true">dashboard</span>
                        <span class="nav-label">Dashboard</span>
                    </a>
                    <a class="nav-item app-sidebar-link" href="calcular_insumos.php" title="Calculadora">
                        <span class="material-symbols-outlined" aria-hidden="true">calculate</span>
                        <span class="nav-label">Calculadora</span>
                    </a>
                    <a class="nav-item app-sidebar-link" href="fitosanitario.php" title="Fitosanitario">
                        <span class="material-symbols-outlined" aria-hidden="true">health_and_safety</span>
                        <span class="nav-label">Fitosanitario</span>
                    </a>
                    <a class="nav-item app-sidebar-link" href="cosechas.php" title="Cosecha">
                        <span class="material-symbols-outlined" aria-hidden="true">agriculture</span>
                        <span class="nav-label">Cosecha</span>
                    </a>
                    <a class="nav-item app-sidebar-link active" href="poscosecha.php" title="Poscosecha">
                        <span class="material-symbols-outlined" aria-hidden="true">inventory_2</span>
                        <span class="nav-label">Poscosecha</span>
                    </a>
                    <a class="nav-item app-sidebar-link" href="historial_solicitudes.php" title="Historial">
                        <span class="material-symbols-outlined" aria-hidden="true">route</span>
                        <span class="nav-label">Historial</span>
                    </a>
                <?php endif; ?>
            </nav>

            <div class="admin-sidebar-actions">
                <a class="nav-item" href="logout.php" title="Cerrar sesión">
                    <span class="material-symbols-outlined" aria-hidden="true">logout</span>
                    <span class="nav-label">Log out</span>
                </a>
            </div>
        </aside>

        <main class="admin-inner-container">
            <header class="admin-reference-topbar">
                <div class="admin-topbar-user">
                    <span class="admin-topbar-avatar"><?php echo e(app_user_initials()); ?></span>
                    <div>
                        <h2>Saludos, <?php echo e(current_user_name()); ?></h2>
                        <p><?php echo $role === 'Agricultor' ? 'Consulta el avance posterior a la cosecha de tus lotes.' : 'Gestiona recepción, clasificación y cierre de poscosecha.'; ?></p>
                    </div>
                </div>
                <div class="admin-topbar-actions">
                    <div class="admin-account-menu" data-admin-account-menu>
                        <button class="admin-account-button" type="button" aria-haspopup="menu" aria-expanded="false" data-admin-account-trigger>
                            <span class="admin-account-initials" aria-hidden="true"><?php echo e(app_user_initials()); ?></span>
                            <span>Cuenta</span>
                            <span class="material-symbols-outlined" aria-hidden="true">expand_more</span>
                        </button>
                        <div class="admin-account-dropdown" role="menu" aria-label="Opciones de cuenta">
                            <div class="admin-account-dropdown__profile" aria-hidden="true">
                                <strong><?php echo e($role); ?></strong>
                                <small><?php echo e(current_user_name()); ?></small>
                            </div>
                            <a href="logout.php" role="menuitem">
                                <span class="material-symbols-outlined" aria-hidden="true">logout</span>
                                <span>Cerrar sesión</span>
                            </a>
                        </div>
                    </div>
                </div>
            </header>

<div class="container farmer-dashboard admin-dashboard posharvest-dashboard mt-4">
    <?php render_flash_messages(); ?>

    <section class="farmer-page-heading admin-page-heading farmer-dashboard-hero">
        <div class="farmer-hero-copy">
            <span class="farmer-kicker">Proceso posterior a cosecha</span>
            <h1>Control de Poscosecha</h1>
            <p><?php echo $role === 'Agricultor'
                ? 'Consulta el avance de poscosecha de tus cosechas recibidas.'
                : 'Registra recepción, avance y cierre de procesos de poscosecha.'; ?></p>
        </div>
        <div class="farmer-hero-status">
            <span class="farmer-hero-status-icon"><span class="material-symbols-outlined" aria-hidden="true">inventory_2</span></span>
            <div>
                <small>Estado del módulo</small>
                <strong><span class="material-symbols-outlined" aria-hidden="true">circle</span> <?php echo $role === 'Agricultor' ? 'Consulta activa' : 'Flujo operativo'; ?></strong>
            </div>
        </div>
    </section>

    <?php render_poscosecha_panel($conn, $role, $id_usuario); ?>
</div>
        </main>
    </div>

<?php render_ada_chat(); ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const menu = document.querySelector('[data-admin-account-menu]');
    const trigger = menu?.querySelector('[data-admin-account-trigger]');

    if (!menu || !trigger) return;

    const closeMenu = function () {
        menu.classList.remove('is-open');
        trigger.setAttribute('aria-expanded', 'false');
    };

    trigger.addEventListener('click', function (event) {
        event.preventDefault();
        event.stopPropagation();
        const open = menu.classList.toggle('is-open');
        trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
    });

    document.addEventListener('click', function (event) {
        if (!menu.contains(event.target)) {
            closeMenu();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeMenu();
        }
    });
});
</script>
<?php render_scripts(['js/poscosecha.js?v=' . filemtime(__DIR__ . '/js/poscosecha.js')]); ?>
</body>
</html>
