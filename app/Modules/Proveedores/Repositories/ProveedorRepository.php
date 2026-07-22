<?php

declare(strict_types=1);

namespace App\Modules\Proveedores\Repositories;

use App\Core\Database;
use App\Shared\Exceptions\DatabaseException;
use App\Shared\Interfaces\ProveedorRepositoryInterface;
use Throwable;

final class ProveedorRepository implements ProveedorRepositoryInterface
{
    public function __construct(private readonly Database $database) {}

    public function findAll(): array
    {
        try {
            $sql = 'SELECT p.id_proveedor,p.Nombre,p.ruc_cedula,p.telefono,p.email,p.direccion,
                    (SELECT COUNT(*) FROM pedidos pe WHERE pe.id_proveedor=p.id_proveedor) AS pedidos_count
                    FROM proveedor p ORDER BY p.Nombre';
            $stmt = $this->database->connection()->prepare($sql);
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $rows;
        } catch (Throwable $exception) {
            throw new DatabaseException('No se pudieron consultar los proveedores.', $exception);
        }
    }

    public function duplicateExists(string $name, string $ruc, ?string $email, ?int $excludeId = null): bool
    {
        try {
            if ($excludeId !== null) {
                $stmt = $this->database->connection()->prepare('SELECT COUNT(*) FROM proveedor WHERE id_proveedor<>? AND (Nombre=? OR ruc_cedula=? OR (? IS NOT NULL AND email=?))');
                $stmt->bind_param('issss', $excludeId, $name, $ruc, $email, $email);
            } else {
                $stmt = $this->database->connection()->prepare('SELECT COUNT(*) FROM proveedor WHERE Nombre=? OR ruc_cedula=? OR (? IS NOT NULL AND email=?)');
                $stmt->bind_param('ssss', $name, $ruc, $email, $email);
            }
            $stmt->execute();
            $count = (int) $stmt->get_result()->fetch_row()[0];
            $stmt->close();
            return $count > 0;
        } catch (Throwable $exception) {
            throw new DatabaseException(previous: $exception);
        }
    }

    public function create(string $name, string $ruc, ?string $phone, ?string $email, ?string $address): int
    {
        try {
            $stmt = $this->database->connection()->prepare('INSERT INTO proveedor(Nombre,ruc_cedula,telefono,email,direccion) VALUES(?,?,?,?,?)');
            $stmt->bind_param('sssss', $name, $ruc, $phone, $email, $address);
            $stmt->execute();
            $id = $stmt->insert_id;
            $stmt->close();
            return $id;
        } catch (Throwable $exception) {
            throw new DatabaseException('No se pudo crear el proveedor.', $exception);
        }
    }

    public function update(int $id, string $name, ?string $phone, ?string $email, ?string $address): bool
    {
        try {
            $stmt = $this->database->connection()->prepare('UPDATE proveedor SET Nombre=?,telefono=?,email=?,direccion=? WHERE id_proveedor=?');
            $stmt->bind_param('ssssi', $name, $phone, $email, $address, $id);
            $stmt->execute();
            $exists = $stmt->affected_rows === 1 || $this->exists($id);
            $stmt->close();
            return $exists;
        } catch (Throwable $exception) {
            throw new DatabaseException('No se pudo actualizar el proveedor.', $exception);
        }
    }

    public function orderCount(int $id): int
    {
        $stmt = $this->database->connection()->prepare('SELECT COUNT(*) FROM pedidos WHERE id_proveedor=?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $count = (int) $stmt->get_result()->fetch_row()[0];
        $stmt->close();
        return $count;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->database->connection()->prepare('DELETE FROM proveedor WHERE id_proveedor=?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $deleted = $stmt->affected_rows === 1;
        $stmt->close();
        return $deleted;
    }

    public function exists(int $id): bool
    {
        $stmt = $this->database->connection()->prepare('SELECT COUNT(*) FROM proveedor WHERE id_proveedor=?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $exists = (int) $stmt->get_result()->fetch_row()[0] > 0;
        $stmt->close();
        return $exists;
    }
}
