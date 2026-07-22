<?php

declare(strict_types=1);

use App\Core\Auth;
use App\Core\Middleware\AuthMiddleware;
use App\Core\Middleware\PermissionMiddleware;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Core\Url;
use App\Modules\Auth\Controllers\AuthController;
use App\Modules\Cultivos\Controllers\CultivoController;
use App\Modules\Lotes\Controllers\LoteController;
use App\Modules\Plagas\Controllers\PlagaController;
use App\Modules\Produccion\Controllers\ProduccionController;
use App\Modules\Solicitudes\Controllers\SolicitudController;
use App\Modules\Inventario\Controllers\InventarioController;
use App\Modules\Insumos\Controllers\InsumoController;
use App\Modules\Pedidos\Controllers\PedidoController;
use App\Modules\Proveedores\Controllers\ProveedorController;
use App\Modules\Facturas\Controllers\FacturaController;
use App\Modules\Reportes\Controllers\ReporteController;
use App\Modules\Usuarios\Controllers\AdminUserController;
use App\Modules\Movimientos\Controllers\MovimientoController;
use App\Modules\Dashboard\Controllers\DashboardController;
use App\Modules\Cultivos\Controllers\AdminAgricultureController;

return static function (
    Router $router,
    Auth $auth,
    AuthController $authController,
    CultivoController $cultivoController,
    LoteController $loteController,
    PlagaController $plagaController,
    ProduccionController $produccionController,
    SolicitudController $solicitudController,
    InventarioController $inventarioController,
    InsumoController $insumoController,
    ProveedorController $proveedorController,
    PedidoController $pedidoController,
    FacturaController $facturaController,
    ReporteController $reporteController,
    AdminUserController $adminUserController,
    MovimientoController $movimientoController,
    DashboardController $dashboardController,
    AdminAgricultureController $adminAgricultureController,
    AuthMiddleware $authMiddleware,
    PermissionMiddleware $cultivosView,
    PermissionMiddleware $cultivosCreate,
    PermissionMiddleware $cultivosDelete,
    PermissionMiddleware $lotesView,
    PermissionMiddleware $lotesCreate,
    PermissionMiddleware $plagasView,
    PermissionMiddleware $plagasCreate,
    PermissionMiddleware $produccionView,
    PermissionMiddleware $produccionCreate
    ,PermissionMiddleware $solicitudesReview
    ,PermissionMiddleware $solicitudesProcess
    ,PermissionMiddleware $solicitudesCreate
    ,PermissionMiddleware $inventarioView
    ,PermissionMiddleware $inventarioUpdate
    ,PermissionMiddleware $proveedoresManage
    ,PermissionMiddleware $pedidosManage
    ,PermissionMiddleware $facturasView
    ,PermissionMiddleware $facturasCreate
    ,PermissionMiddleware $facturasReview
    ,PermissionMiddleware $reportesAdmin
    ,PermissionMiddleware $reportesBodega
    ,PermissionMiddleware $usersManage
    ,PermissionMiddleware $movimientosView
    ,PermissionMiddleware $adminDashboard
    ,PermissionMiddleware $farmerDashboard
    ,PermissionMiddleware $warehouseDashboard
): void {
    $router->get('/login', [$authController, 'showLogin']);
    $router->post('/login', [$authController, 'login']);
    $router->post('/logout', [$authController, 'logout'], [$authMiddleware]);
    $router->get('/password/forgot', [$authController, 'showForgotPassword']);
    $router->post('/password/forgot', [$authController, 'requestPasswordReset']);

    $router->get('/', static function (Request $request) use ($auth): Response {
        if (!$auth->check()) {
            return Response::redirect(Url::route('/login'));
        }

        $dashboard = match ($auth->role()) {
            'Administrador' => '/dashboard/admin',
            'Agricultor' => '/dashboard/agricultor',
            'Bodeguero' => '/dashboard/bodega',
            default => '/login',
        };
        return Response::redirect(Url::route($dashboard));
    });

    $router->get('/cultivos', [$cultivoController, 'index'], [$authMiddleware, $cultivosView]);
    $router->post('/cultivos', [$cultivoController, 'store'], [$authMiddleware, $cultivosCreate]);
    $router->get('/cultivos/{id}', [$cultivoController, 'show'], [$authMiddleware, $cultivosView]);
    $router->delete('/cultivos/{id}', [$cultivoController, 'destroy'], [$authMiddleware, $cultivosDelete]);

    $router->get('/lotes', [$loteController, 'index'], [$authMiddleware, $lotesView]);
    $router->post('/lotes', [$loteController, 'store'], [$authMiddleware, $lotesCreate]);
    $router->post('/lotes/con-plagas', [$loteController, 'storeWithPests'], [$authMiddleware, $lotesCreate, $plagasCreate]);
    $router->get('/lotes/{id}', [$loteController, 'show'], [$authMiddleware, $lotesView]);

    $router->get('/plagas', [$plagaController, 'index'], [$authMiddleware, $plagasView]);
    $router->post('/plagas', [$plagaController, 'store'], [$authMiddleware, $plagasCreate]);

    $router->get('/produccion', [$produccionController, 'index'], [$authMiddleware, $produccionView]);
    $router->post('/produccion/finalizar', [$produccionController, 'finalize'], [$authMiddleware, $produccionCreate]);
    $router->get('/solicitudes/admin', [$solicitudController, 'adminPage'], [$authMiddleware, $solicitudesReview]);
    $router->get('/solicitudes/historial', [$solicitudController, 'history'], [$authMiddleware, $solicitudesCreate]);
    $router->post('/solicitudes/revisar', [$solicitudController, 'review'], [$authMiddleware, $solicitudesReview]);
    $router->post('/solicitudes/procesar', [$solicitudController, 'process'], [$authMiddleware, $solicitudesProcess]);
    $router->post('/solicitudes', [$solicitudController, 'create'], [$authMiddleware, $solicitudesCreate]);
    $router->get('/inventario', [$inventarioController, 'index'], [$authMiddleware, $inventarioView]);
    $router->post('/inventario', [$inventarioController, 'store'], [$authMiddleware, $inventarioUpdate]);
    $router->post('/inventario/ajustar', [$inventarioController, 'adjust'], [$authMiddleware, $inventarioUpdate]);
    $router->get('/insumos/calculadora', [$insumoController, 'page'], [$authMiddleware, $solicitudesCreate]);
    $router->get('/api/insumos/calcular/{id}', [$insumoController, 'calculate'], [$authMiddleware, $solicitudesCreate]);
    $router->get('/abastecimiento', [$proveedorController, 'index'], [$authMiddleware, $proveedoresManage]);
    $router->post('/proveedores', [$proveedorController, 'create'], [$authMiddleware, $proveedoresManage]);
    $router->post('/proveedores/actualizar', [$proveedorController, 'update'], [$authMiddleware, $proveedoresManage]);
    $router->post('/proveedores/eliminar', [$proveedorController, 'delete'], [$authMiddleware, $proveedoresManage]);
    $router->post('/pedidos', [$pedidoController, 'create'], [$authMiddleware, $pedidosManage]);
    $router->post('/pedidos/actualizar', [$pedidoController, 'update'], [$authMiddleware, $pedidosManage]);
    $router->post('/pedidos/cancelar', [$pedidoController, 'cancel'], [$authMiddleware, $pedidosManage]);
    $router->get('/facturas/recepcion', [$facturaController, 'reception'], [$authMiddleware, $facturasCreate]);
    $router->post('/facturas/recepcion', [$facturaController, 'receive'], [$authMiddleware, $facturasCreate]);
    $router->get('/facturas', [$facturaController, 'index'], [$authMiddleware, $facturasReview]);
    $router->post('/facturas/revisar', [$facturaController, 'review'], [$authMiddleware, $facturasReview]);
    $router->get('/facturas/{id}', [$facturaController, 'detail'], [$authMiddleware, $facturasView]);
    $router->get('/reportes', [$reporteController, 'admin'], [$authMiddleware, $reportesAdmin]);
    $router->get('/reportes/solicitudes', [$reporteController, 'requests'], [$authMiddleware, $reportesBodega]);
    $router->get('/reportes/productos-factura', [$reporteController, 'invoiceProducts'], [$authMiddleware, $reportesBodega]);
    $router->get('/usuarios', [$adminUserController, 'index'], [$authMiddleware, $usersManage]);
    $router->post('/usuarios', [$adminUserController, 'create'], [$authMiddleware, $usersManage]);
    $router->post('/usuarios/actualizar', [$adminUserController, 'update'], [$authMiddleware, $usersManage]);
    $router->post('/usuarios/eliminar', [$adminUserController, 'delete'], [$authMiddleware, $usersManage]);
    $router->get('/movimientos', [$movimientoController, 'index'], [$authMiddleware, $movimientosView]);
    $router->get('/dashboard/admin', [$dashboardController, 'admin'], [$authMiddleware, $adminDashboard]);
    $router->get('/dashboard/agricultor', [$dashboardController, 'farmer'], [$authMiddleware, $farmerDashboard]);
    $router->get('/dashboard/bodega', [$dashboardController, 'warehouse'], [$authMiddleware, $warehouseDashboard]);
    $router->get('/admin/agricultura', [$adminAgricultureController, 'fragment'], [$authMiddleware, $adminDashboard]);
    $router->post('/admin/agricultura', [$adminAgricultureController, 'delete'], [$authMiddleware, $adminDashboard]);
    $router->get('/admin/agricultura/cultivos/{id}', [$adminAgricultureController, 'cropDetail'], [$authMiddleware, $adminDashboard]);
    $router->get('/admin/agricultura/lotes/{id}', [$adminAgricultureController, 'lotDetail'], [$authMiddleware, $adminDashboard]);
    $router->get('/admin/agricultura/lotes/{id}/historial', [$adminAgricultureController, 'lotHistory'], [$authMiddleware, $adminDashboard]);
};
