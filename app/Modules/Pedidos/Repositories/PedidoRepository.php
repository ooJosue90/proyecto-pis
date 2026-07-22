<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Repositories;

use App\Core\Database;
use App\Shared\Exceptions\DatabaseException;
use App\Shared\Interfaces\PedidoRepositoryInterface;
use Throwable;

final class PedidoRepository implements PedidoRepositoryInterface
{
    public function __construct(private readonly Database $database) {}

    public function findAll(): array
    {
        return $this->rows('SELECT p.*,pr.Nombre AS proveedor_nombre,pr.telefono AS proveedor_telefono,u.nombre AS usuario_nombre FROM pedidos p LEFT JOIN proveedor pr ON p.id_proveedor=pr.id_proveedor LEFT JOIN usuarios u ON p.id_usuario=u.id_usuario ORDER BY p.fecha DESC');
    }

    public function findUsers(): array
    {
        return $this->rows('SELECT id_usuario,nombre FROM usuarios ORDER BY nombre');
    }

    public function findSupplies(): array
    {
        return $this->rows('SELECT id_insumos,nombre,tipo,unidad_medida,cantidad FROM insumos_agricolas ORDER BY tipo,nombre');
    }

    public function stats(): array
    {
        $rows = $this->rows("SELECT (SELECT COUNT(*) FROM proveedor) total_proveedores,(SELECT COUNT(*) FROM pedidos) total_pedidos,(SELECT COUNT(*) FROM pedidos WHERE estado='Pendiente') pedidos_pendientes,(SELECT COUNT(*) FROM pedidos WHERE estado='Recibido') pedidos_recibidos");
        $row = $rows[0] ?? [];
        return array_map('intval', $row);
    }

    public function userExists(string $id): bool
    {
        return $this->countString('SELECT COUNT(*) FROM usuarios WHERE id_usuario=?', $id) > 0;
    }

    public function findSupply(int $id): ?array
    {
        try {
            $stmt = $this->database->connection()->prepare('SELECT id_insumos,nombre,unidad_medida FROM insumos_agricolas WHERE id_insumos=?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return is_array($row) ? ['id_insumos' => (int) $row['id_insumos'], 'nombre' => (string) $row['nombre'], 'unidad_medida' => (string) ($row['unidad_medida'] ?: 'unid')] : null;
        } catch (Throwable $exception) {
            throw new DatabaseException(previous: $exception);
        }
    }

    public function supplyNameExists(string $name): bool
    {
        return $this->countString('SELECT COUNT(*) FROM insumos_agricolas WHERE LOWER(nombre)=LOWER(?)', $name) > 0;
    }

    public function createSupply(string $userId, string $name, string $type, string $unit, ?string $observations): int
    {
        try {
            $description = 'Producto creado desde pedido a proveedor.';
            $stmt = $this->database->connection()->prepare('INSERT INTO insumos_agricolas(id_usuario,nombre,tipo,descripcion,unidad_medida,cantidad,observaciones) VALUES(?,?,?,?,?,0,?)');
            $stmt->bind_param('ssssss', $userId, $name, $type, $description, $unit, $observations);
            $stmt->execute();
            $id = $stmt->insert_id;
            $stmt->close();
            return $id;
        } catch (Throwable $exception) {
            throw new DatabaseException('No se pudo crear el producto del pedido.', $exception);
        }
    }

    public function create(int $providerId, string $userId, int $supplyId, string $product, float $quantity, string $unit, ?string $observations): int
    {
        try {
            $stmt = $this->database->connection()->prepare("INSERT INTO pedidos(id_proveedor,id_usuario,id_insumo,nombre_producto,cantidad,unidad_medida,observaciones,estado,fecha) VALUES(?,?,?,?,?,?,?,'Pendiente',NOW())");
            $stmt->bind_param('isisdss', $providerId, $userId, $supplyId, $product, $quantity, $unit, $observations);
            $stmt->execute();
            $id = $stmt->insert_id;
            $stmt->close();
            return $id;
        } catch (Throwable $exception) {
            throw new DatabaseException('No se pudo crear el pedido.', $exception);
        }
    }

    public function update(int $id, int $providerId, string $userId, int $supplyId, string $product, float $quantity, string $unit, ?string $observations): bool
    {
        try {
            $stmt = $this->database->connection()->prepare("UPDATE pedidos SET id_proveedor=?,id_usuario=?,id_insumo=?,nombre_producto=?,cantidad=?,unidad_medida=?,observaciones=? WHERE id_pedidos=? AND estado='Pendiente'");
            $stmt->bind_param('isisdssi', $providerId, $userId, $supplyId, $product, $quantity, $unit, $observations, $id);
            $stmt->execute();
            $updated = $stmt->affected_rows === 1;
            $stmt->close();
            return $updated;
        } catch (Throwable $exception) {
            throw new DatabaseException('No se pudo actualizar el pedido.', $exception);
        }
    }

    public function cancel(int $id): bool
    {
        try {
            $stmt = $this->database->connection()->prepare("UPDATE pedidos SET estado='Cancelado' WHERE id_pedidos=? AND estado='Pendiente'");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $updated = $stmt->affected_rows === 1;
            $stmt->close();
            return $updated;
        } catch (Throwable $exception) {
            throw new DatabaseException('No se pudo cancelar el pedido.', $exception);
        }
    }

    /** @return list<array<string, mixed>> */
    private function rows(string $sql): array
    {
        try {
            $stmt = $this->database->connection()->prepare($sql);
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $rows;
        } catch (Throwable $exception) {
            throw new DatabaseException(previous: $exception);
        }
    }

    private function countString(string $sql, string $value): int
    {
        try {
            $stmt = $this->database->connection()->prepare($sql);
            $stmt->bind_param('s', $value);
            $stmt->execute();
            $count = (int) $stmt->get_result()->fetch_row()[0];
            $stmt->close();
            return $count;
        } catch (Throwable $exception) {
            throw new DatabaseException(previous: $exception);
        }
    }
}
