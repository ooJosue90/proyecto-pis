<?php

declare(strict_types=1);

namespace App\Modules\Proveedores\Services;

use App\Core\Validator;
use App\Shared\Exceptions\ValidationException;
use App\Shared\Interfaces\ProveedorRepositoryInterface;

final class ProveedorService
{
    public function __construct(private readonly ProveedorRepositoryInterface $repository, private readonly Validator $validator) {}

    public function all(): array
    {
        return $this->repository->findAll();
    }

    /** @param array<string, mixed> $input */
    public function create(array $input): int
    {
        $data = $this->data($input, true);
        if ($this->repository->duplicateExists($data['nombre'], $data['ruc'], $data['email'])) {
            throw new ValidationException(['proveedor' => ['Ya existe un proveedor con ese nombre, RUC o correo.']]);
        }
        return $this->repository->create($data['nombre'], $data['ruc'], $data['telefono'], $data['email'], $data['direccion']);
    }

    /** @param array<string, mixed> $input */
    public function update(array $input): void
    {
        $id = (int) ($input['id_proveedor'] ?? 0);
        if ($id <= 0) {
            throw new ValidationException(['proveedor' => ['El proveedor no es válido.']]);
        }
        $data = $this->data($input, false);
        if (!$this->repository->update($id, $data['nombre'], $data['telefono'], $data['email'], $data['direccion'])) {
            throw new ValidationException(['proveedor' => ['El proveedor no existe.']]);
        }
    }

    public function delete(int $id): void
    {
        if ($id <= 0 || !$this->repository->exists($id)) {
            throw new ValidationException(['proveedor' => ['El proveedor no existe.']]);
        }
        $orders = $this->repository->orderCount($id);
        if ($orders > 0) {
            throw new ValidationException(['proveedor' => ["No se puede eliminar: tiene {$orders} pedido(s) asociado(s)."]]);
        }
        if (!$this->repository->delete($id)) {
            throw new ValidationException(['proveedor' => ['No se pudo eliminar el proveedor.']]);
        }
    }

    /** @param array<string, mixed> $input @return array{nombre:string,ruc:string,telefono:?string,email:?string,direccion:?string} */
    private function data(array $input, bool $requireRuc): array
    {
        $data = [
            'nombre' => trim((string) ($input['nombre'] ?? '')),
            'ruc' => trim((string) ($input['ruc_cedula'] ?? '')),
            'telefono' => trim((string) ($input['telefono'] ?? '')),
            'email' => trim((string) ($input['email'] ?? '')),
            'direccion' => trim((string) ($input['direccion'] ?? '')),
        ];
        $rules = ['nombre' => 'required|max_length:150', 'telefono' => 'max_length:20', 'email' => 'email|max_length:100', 'direccion' => 'max_length:2000'];
        if ($requireRuc) {
            $rules['ruc'] = 'required|max_length:20';
        }
        $this->validator->validate($data, $rules);
        return [
            'nombre' => $data['nombre'], 'ruc' => $data['ruc'],
            'telefono' => $data['telefono'] === '' ? null : $data['telefono'],
            'email' => $data['email'] === '' ? null : $data['email'],
            'direccion' => $data['direccion'] === '' ? null : $data['direccion'],
        ];
    }
}
