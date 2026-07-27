<?php

declare(strict_types=1);

namespace App\Modules\Proveedores\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Modules\Pedidos\Services\PedidoService;
use App\Modules\Proveedores\Services\ProveedorService;
use App\Shared\Exceptions\ValidationException;

final class ProveedorController extends Controller
{
    public function __construct(private readonly ProveedorService $service, private readonly PedidoService $orders, private readonly Csrf $csrf) {}

    public function index(Request $request): Response
    {
        return $this->render(__DIR__ . '/../Views/index.php', array_merge($this->orders->dashboard(), [
            'proveedores' => $this->service->all(),
            'tiposInsumoPermitidos' => PedidoService::SUPPLY_TYPES,
            'csrfToken' => $this->csrf->token(),
        ]));
    }

    public function create(Request $request): Response
    {
        return $this->action($request, fn (): int => $this->service->create($request->all()), 'Proveedor creado exitosamente.');
    }

    public function update(Request $request): Response
    {
        return $this->action($request, function () use ($request): void { $this->service->update($request->all()); }, 'Proveedor actualizado exitosamente.');
    }

    public function delete(Request $request): Response
    {
        return $this->action($request, function () use ($request): void { $this->service->delete((int) $request->input('id_proveedor', 0)); }, 'Proveedor eliminado exitosamente.');
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
