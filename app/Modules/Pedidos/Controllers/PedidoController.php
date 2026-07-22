<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Modules\Pedidos\Services\PedidoService;
use App\Shared\Exceptions\ValidationException;

final class PedidoController extends Controller
{
    public function __construct(private readonly PedidoService $service, private readonly Auth $auth, private readonly Csrf $csrf) {}

    public function create(Request $request): Response
    {
        $user = $this->auth->user();
        return $this->action($request, fn (): int => $this->service->create($user['id_usuario'], $request->all()), 'Pedido creado exitosamente.');
    }

    public function update(Request $request): Response
    {
        return $this->action($request, function () use ($request): void { $this->service->update($request->all()); }, 'Pedido actualizado exitosamente.');
    }

    public function cancel(Request $request): Response
    {
        return $this->action($request, function () use ($request): void { $this->service->cancel((int) $request->input('id_pedido', 0)); }, 'Pedido cancelado correctamente.');
    }

    private function action(Request $request, callable $operation, string $message): Response
    {
        try {
            $this->csrf->validate((string) $request->input('_token', ''));
            $result = $operation();
            $payload = ['success' => true, 'message' => $message];
            if (is_int($result)) {
                $payload['id'] = $result;
            }
            return $this->json($payload);
        } catch (ValidationException $exception) {
            return $this->json(['success' => false, 'message' => implode(' ', array_merge(...array_values($exception->errors())))], 422);
        }
    }
}
