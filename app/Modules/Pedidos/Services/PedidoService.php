<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Services;

use App\Core\Validator;
use App\Shared\Exceptions\ValidationException;
use App\Shared\Interfaces\PedidoRepositoryInterface;
use App\Shared\Interfaces\ProveedorRepositoryInterface;
use App\Shared\Interfaces\TransactionManagerInterface;

final class PedidoService
{
    public const SUPPLY_TYPES = ['Fungicidas','Insecticidas','Herbicidas','Fertilizantes','Coadyuvantes','Trampas','Herramientas','Equipos de protección','Otros'];

    public function __construct(
        private readonly PedidoRepositoryInterface $repository,
        private readonly ProveedorRepositoryInterface $providers,
        private readonly TransactionManagerInterface $transactions,
        private readonly Validator $validator
    ) {}

    /** @return array{pedidos:array,usuarios:array,insumos:array,stats:array} */
    public function dashboard(): array
    {
        return ['pedidos' => $this->repository->findAll(), 'usuarios' => $this->repository->findUsers(), 'insumos' => $this->repository->findSupplies(), 'stats' => $this->repository->stats()];
    }

    /** @param array<string, mixed> $input */
    public function create(string $actorId, array $input): int
    {
        $data = $this->validateBase($input, false);
        return $this->transactions->transaction(function () use ($actorId, $input, $data): int {
            $supplyId = $data['supply_id'];
            if (($input['crear_producto_nuevo'] ?? '') === '1') {
                $new = $this->validateNewSupply($input);
                if ($this->repository->supplyNameExists($new['nombre'])) {
                    throw new ValidationException(['pedido' => ['Ya existe un producto con ese nombre. Selecciónelo en la lista.']]);
                }
                $supplyId = $this->repository->createSupply($actorId, $new['nombre'], $new['tipo'], $new['unidad'], $new['observaciones']);
            }
            $supply = $this->relations($data['provider_id'], $data['user_id'], $supplyId);
            return $this->repository->create($data['provider_id'], $data['user_id'], $supplyId, $supply['nombre'], $data['cantidad'], $supply['unidad_medida'], $data['observaciones']);
        });
    }

    /** @param array<string, mixed> $input */
    public function update(array $input): void
    {
        $data = $this->validateBase($input, true);
        $supply = $this->relations($data['provider_id'], $data['user_id'], $data['supply_id']);
        if (!$this->repository->update($data['id'], $data['provider_id'], $data['user_id'], $data['supply_id'], $supply['nombre'], $data['cantidad'], $supply['unidad_medida'], $data['observaciones'])) {
            throw new ValidationException(['pedido' => ['Solo se pueden editar pedidos pendientes.']]);
        }
    }

    public function cancel(int $id): void
    {
        if ($id <= 0 || !$this->repository->cancel($id)) {
            throw new ValidationException(['pedido' => ['Solo se pueden cancelar pedidos pendientes.']]);
        }
    }

    /** @param array<string, mixed> $input @return array{id:int,provider_id:int,user_id:string,supply_id:int,cantidad:float,observaciones:?string} */
    private function validateBase(array $input, bool $editing): array
    {
        $data = ['id' => (int) ($input['id_pedido'] ?? 0), 'provider_id' => (int) ($input['id_proveedor'] ?? 0), 'user_id' => trim((string) ($input['id_usuario'] ?? '')), 'supply_id' => (int) ($input['id_insumo'] ?? 0), 'cantidad' => $input['cantidad'] ?? null, 'observaciones' => trim((string) ($input['observaciones'] ?? ''))];
        $this->validator->validate($data, ['user_id' => 'required|max_length:20', 'cantidad' => 'required|numeric|min:0', 'observaciones' => 'max_length:2000']);
        $newSupply = ($input['crear_producto_nuevo'] ?? '') === '1';
        if (($editing && $data['id'] <= 0) || $data['provider_id'] <= 0 || (!$newSupply && $data['supply_id'] <= 0) || (float) $data['cantidad'] <= 0) {
            throw new ValidationException(['pedido' => ['Proveedor, producto y cantidad deben ser válidos.']]);
        }
        $data['cantidad'] = (float) $data['cantidad'];
        $data['observaciones'] = $data['observaciones'] === '' ? null : $data['observaciones'];
        return $data;
    }

    /** @return array{id_insumos:int,nombre:string,unidad_medida:string} */
    private function relations(int $providerId, string $userId, int $supplyId): array
    {
        $supply = $this->repository->findSupply($supplyId);
        if (!$this->providers->exists($providerId) || !$this->repository->userExists($userId) || $supply === null) {
            throw new ValidationException(['pedido' => ['Proveedor, usuario responsable o producto no válido.']]);
        }
        return $supply;
    }

    /** @param array<string, mixed> $input @return array{nombre:string,tipo:string,unidad:string,observaciones:?string} */
    private function validateNewSupply(array $input): array
    {
        $data = ['nombre' => trim((string) ($input['nuevo_insumo_nombre'] ?? '')), 'tipo' => trim((string) ($input['nuevo_insumo_tipo'] ?? '')), 'unidad' => trim((string) ($input['nuevo_insumo_unidad'] ?? '')), 'observaciones' => trim((string) ($input['nuevo_insumo_observaciones'] ?? ''))];
        $this->validator->validate($data, ['nombre' => 'required|max_length:200', 'tipo' => 'required|in:' . implode(',', self::SUPPLY_TYPES), 'unidad' => 'required|max_length:50', 'observaciones' => 'max_length:2000']);
        $data['observaciones'] = $data['observaciones'] === '' ? null : $data['observaciones'];
        return $data;
    }
}
