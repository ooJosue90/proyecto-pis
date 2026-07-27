<?php

declare(strict_types=1);

use App\Core\Auth;
use App\Core\Authorization;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\ExceptionHandler;
use App\Core\Middleware\AuthMiddleware;
use App\Core\Middleware\PermissionMiddleware;
use App\Core\Request;
use App\Core\Router;
use App\Core\Session;
use App\Core\Validator;
use App\Modules\Auth\Controllers\AuthController;
use App\Modules\Auth\Repositories\UserRepository;
use App\Modules\Auth\Services\AuthService;
use App\Modules\Auth\Services\PasswordResetService;
use App\Modules\Cultivos\Controllers\CultivoController;
use App\Modules\Cultivos\Repositories\CultivoRepository;
use App\Modules\Cultivos\Services\CultivoService;
use App\Modules\Lotes\Controllers\LoteController;
use App\Modules\Lotes\Repositories\LoteRepository;
use App\Modules\Lotes\Services\LoteService;
use App\Modules\Plagas\Controllers\PlagaController;
use App\Modules\Plagas\Repositories\PlagaRepository;
use App\Modules\Plagas\Services\PlagaService;
use App\Modules\Produccion\Controllers\ProduccionController;
use App\Modules\Produccion\Repositories\ProduccionRepository;
use App\Modules\Produccion\Services\ProduccionService;
use App\Modules\Inventario\Repositories\InventarioRepository;
use App\Modules\Inventario\Controllers\InventarioController;
use App\Modules\Inventario\Services\InventarioService;
use App\Modules\Insumos\Controllers\InsumoController;
use App\Modules\Insumos\Services\InsumoCalculator;
use App\Modules\Solicitudes\Controllers\SolicitudController;
use App\Modules\Solicitudes\Repositories\SolicitudRepository;
use App\Modules\Solicitudes\Services\SolicitudService;
use App\Modules\Pedidos\Controllers\PedidoController;
use App\Modules\Pedidos\Repositories\PedidoRepository;
use App\Modules\Pedidos\Services\PedidoService;
use App\Modules\Proveedores\Controllers\ProveedorController;
use App\Modules\Proveedores\Repositories\ProveedorRepository;
use App\Modules\Proveedores\Services\ProveedorService;
use App\Modules\Facturas\Controllers\FacturaController;
use App\Modules\Facturas\Repositories\FacturaRepository;
use App\Modules\Facturas\Services\FacturaService;
use App\Modules\Reportes\Controllers\ReporteController;
use App\Modules\Reportes\Repositories\ReporteRepository;
use App\Modules\Reportes\Services\ReporteService;
use App\Modules\Asistente\Controllers\ChatController;
use App\Modules\Asistente\Repositories\AssistantDataRepository;
use App\Modules\Asistente\Services\ContextBuilder;
use App\Modules\Asistente\Services\GeminiService;
use App\Modules\Asistente\Services\PermissionFilter;
use App\Modules\Usuarios\Controllers\AdminUserController;
use App\Modules\Usuarios\Repositories\AdminUserRepository;
use App\Modules\Usuarios\Services\AdminUserService;
use App\Modules\Movimientos\Controllers\MovimientoController;
use App\Modules\Movimientos\Repositories\MovimientoRepository;
use App\Modules\Dashboard\Controllers\DashboardController;
use App\Modules\Dashboard\Repositories\DashboardRepository;
use App\Modules\Dashboard\Services\DashboardService;
use App\Modules\Cultivos\Controllers\AdminAgricultureController;
use App\Modules\Cultivos\Repositories\AdminAgricultureRepository;

$root = dirname(__DIR__);
$autoload = $root . '/vendor/autoload.php';
if (!is_file($autoload)) {
    http_response_code(500);
    exit('No se encontró vendor/autoload.php. Ejecute composer install.');
}

require_once $autoload;
require_once $root . '/config/env.php';
env_load();

$appConfig = require $root . '/config/app.php';
$databaseConfig = require $root . '/config/database.php';
$permissions = require $root . '/config/permissions.php';
$geminiConfig = require $root . '/config/gemini.php';
date_default_timezone_set((string) $appConfig['timezone']);

$session = new Session($root . '/storage/sessions');
$session->start();

$exceptionHandler = new ExceptionHandler(
    (string) $appConfig['environment'],
    (bool) $appConfig['debug'],
    $root . '/storage/logs'
);
$exceptionHandler->register();

try {
    $database = new Database($databaseConfig);
    $database->connection();
    $auth = new Auth($session);
    $authorization = new Authorization($auth, $permissions);
    $csrf = new Csrf($session);
    $validator = new Validator();

    $userRepository = new UserRepository($database);
    $authService = new AuthService($userRepository, $validator);
    $passwordResetService = new PasswordResetService($userRepository, $validator);
    $authController = new AuthController($authService, $passwordResetService, $auth, $csrf, $session);

    $cultivoRepository = new CultivoRepository($database);
    $cultivoService = new CultivoService($cultivoRepository, $validator);
    $cultivoController = new CultivoController($cultivoService, $auth, $csrf, $session);

    $loteRepository = new LoteRepository($database);
    $loteService = new LoteService($loteRepository, $validator);
    $plagaRepository = new PlagaRepository($database);
    $plagaService = new PlagaService($plagaRepository, $validator);
    $loteController = new LoteController($loteService, $auth, $csrf, $session, $plagaService, $database);
    $plagaController = new PlagaController($plagaService, $loteService, $auth, $csrf, $session);

    $produccionRepository = new ProduccionRepository($database);
    $produccionService = new ProduccionService($produccionRepository, $database, $validator);
    $produccionController = new ProduccionController($produccionService, $auth, $csrf, $session);

    $solicitudService = new SolicitudService(new SolicitudRepository($database), new InventarioRepository($database), $database);
    $solicitudController = new SolicitudController($solicitudService, $auth, $csrf, $session);
    $inventarioService = new InventarioService(new InventarioRepository($database), $database, $validator);
    $inventarioController = new InventarioController($inventarioService, $auth, $csrf, $session);
    $insumoController = new InsumoController(new InsumoCalculator(), $loteService, $auth);
    $proveedorRepository = new ProveedorRepository($database);
    $proveedorService = new ProveedorService($proveedorRepository, $validator);
    $pedidoService = new PedidoService(new PedidoRepository($database), $proveedorRepository, $database, $validator);
    $proveedorController = new ProveedorController($proveedorService, $pedidoService, $csrf);
    $pedidoController = new PedidoController($pedidoService, $auth, $csrf);
    $facturaService = new FacturaService(new FacturaRepository($database), $database, $validator);
    $facturaController = new FacturaController($facturaService, $auth, $csrf, $session);
    $reporteController = new ReporteController(new ReporteService(new ReporteRepository($database)));
    $chatController = new ChatController(
        new PermissionFilter(),
        new ContextBuilder(new AssistantDataRepository($database), (int) $geminiConfig['max_rows_per_topic'], (int) $geminiConfig['max_context_chars']),
        new GeminiService((string) $geminiConfig['api_key'], (string) $geminiConfig['model'], (int) $geminiConfig['timeout']),
        $auth,
        $csrf,
        $session
    );
    $adminUserController = new AdminUserController(new AdminUserService(new AdminUserRepository($database), $validator), $auth, $csrf);
    $movimientoController = new MovimientoController(new MovimientoRepository($database));
    $dashboardController = new DashboardController(new DashboardService(new DashboardRepository($database)), $auth);
    $adminAgricultureController = new AdminAgricultureController(new AdminAgricultureRepository($database), $cultivoService, $csrf);

    $authMiddleware = new AuthMiddleware($auth);
    $cultivosView = new PermissionMiddleware($authorization, 'cultivos.ver');
    $cultivosCreate = new PermissionMiddleware($authorization, 'cultivos.crear');
    $cultivosDelete = new PermissionMiddleware($authorization, 'cultivos.eliminar');
    $lotesView = new PermissionMiddleware($authorization, 'lotes.ver');
    $lotesCreate = new PermissionMiddleware($authorization, 'lotes.crear');
    $lotesUpdate = new PermissionMiddleware($authorization, 'lotes.actualizar');
    $plagasView = new PermissionMiddleware($authorization, 'plagas.ver');
    $plagasCreate = new PermissionMiddleware($authorization, 'plagas.crear');
    $produccionView = new PermissionMiddleware($authorization, 'produccion.ver');
    $produccionCreate = new PermissionMiddleware($authorization, 'produccion.crear');
    $solicitudesReview = new PermissionMiddleware($authorization, 'solicitudes.revisar');
    $solicitudesProcess = new PermissionMiddleware($authorization, 'solicitudes.procesar');
    $solicitudesCreate = new PermissionMiddleware($authorization, 'solicitudes.crear');
    $inventarioView = new PermissionMiddleware($authorization, 'inventario.ver');
    $inventarioUpdate = new PermissionMiddleware($authorization, 'inventario.actualizar');
    $proveedoresManage = new PermissionMiddleware($authorization, 'proveedores.gestionar');
    $pedidosManage = new PermissionMiddleware($authorization, 'pedidos.gestionar');
    $facturasView = new PermissionMiddleware($authorization, 'facturas.ver');
    $facturasCreate = new PermissionMiddleware($authorization, 'facturas.crear');
    $facturasReview = new PermissionMiddleware($authorization, 'facturas.revisar');
    $reportesAdmin = new PermissionMiddleware($authorization, 'reportes.ver');
    $reportesBodega = new PermissionMiddleware($authorization, 'reportes.bodega');
    $assistantUse = new PermissionMiddleware($authorization, 'asistente.usar');
    $usersManage = new PermissionMiddleware($authorization, 'usuarios.gestionar');
    $movimientosView = new PermissionMiddleware($authorization, 'movimientos.ver');
    $adminDashboard = new PermissionMiddleware($authorization, 'dashboard.administrador');
    $farmerDashboard = new PermissionMiddleware($authorization, 'dashboard.agricultor');
    $warehouseDashboard = new PermissionMiddleware($authorization, 'dashboard.bodeguero');

    $router = new Router();
    $registerWebRoutes = require $root . '/routes/web.php';
    $registerWebRoutes($router, $auth, $authController, $cultivoController, $loteController, $plagaController, $produccionController, $solicitudController, $inventarioController, $insumoController, $proveedorController, $pedidoController, $facturaController, $reporteController, $adminUserController, $movimientoController, $dashboardController, $adminAgricultureController, $authMiddleware, $cultivosView, $cultivosCreate, $cultivosDelete, $lotesView, $lotesCreate, $lotesUpdate, $plagasView, $plagasCreate, $produccionView, $produccionCreate, $solicitudesReview, $solicitudesProcess, $solicitudesCreate, $inventarioView, $inventarioUpdate, $proveedoresManage, $pedidosManage, $facturasView, $facturasCreate, $facturasReview, $reportesAdmin, $reportesBodega, $usersManage, $movimientosView, $adminDashboard, $farmerDashboard, $warehouseDashboard);
    $registerApiRoutes = require $root . '/routes/api.php';
    $registerApiRoutes($router, $cultivoController, $loteController, $chatController, $authMiddleware, $cultivosView, $lotesView, $assistantUse);

    $request = Request::capture();
    $router->dispatch($request)->send();
} catch (Throwable $exception) {
    $exceptionHandler->render($exception, isset($request) ? $request : null);
}
