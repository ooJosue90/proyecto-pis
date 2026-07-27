<?php

declare(strict_types=1);

use App\Core\Middleware\AuthMiddleware;
use App\Core\Middleware\PermissionMiddleware;
use App\Core\Router;
use App\Modules\Cultivos\Controllers\CultivoController;
use App\Modules\Lotes\Controllers\LoteController;
use App\Modules\Asistente\Controllers\ChatController;

return static function (
    Router $router,
    CultivoController $cultivoController,
    LoteController $loteController,
    ChatController $chatController,
    AuthMiddleware $authMiddleware,
    PermissionMiddleware $cultivosView,
    PermissionMiddleware $lotesView,
    PermissionMiddleware $assistantUse
): void {
    $router->get('/api/cultivos/{id}', [$cultivoController, 'show'], [$authMiddleware, $cultivosView]);
    $router->get('/api/lotes/{id}', [$loteController, 'show'], [$authMiddleware, $lotesView]);
    $router->post('/api/asistente/chat', [$chatController, 'chat'], [$authMiddleware, $assistantUse]);
};
