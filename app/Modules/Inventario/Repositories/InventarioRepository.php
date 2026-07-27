<?php
declare(strict_types=1);
namespace App\Modules\Inventario\Repositories;
use App\Core\Database;
use App\Shared\Exceptions\DatabaseException;
use App\Shared\Interfaces\InventarioRepositoryInterface;
use Throwable;
final class InventarioRepository implements InventarioRepositoryInterface
{
    public function __construct(private readonly Database $database) {}
    public function findAll(): array
    {
        try{$stmt=$this->database->connection()->prepare("SELECT id_insumos,id_usuario,nombre,tipo,descripcion,unidad_medida,cantidad,observaciones FROM insumos_agricolas ORDER BY CASE tipo WHEN 'Siembra' THEN 1 WHEN 'Riego' THEN 2 WHEN 'Cosecha' THEN 3 ELSE 4 END, nombre");$stmt->execute();$rows=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);$stmt->close();return $rows;}catch(Throwable $e){throw new DatabaseException(previous:$e);}
    }
    public function create(string $userId,string $name,string $type,?string $description,string $unit,float $quantity,?string $observations): int
    {
        try{$stmt=$this->database->connection()->prepare('INSERT INTO insumos_agricolas(id_usuario,nombre,tipo,descripcion,unidad_medida,cantidad,observaciones) VALUES(?,?,?,?,?,?,?)');$stmt->bind_param('sssssds',$userId,$name,$type,$description,$unit,$quantity,$observations);$stmt->execute();$id=$stmt->insert_id;$stmt->close();return $id;}catch(Throwable $e){throw new DatabaseException('No se pudo registrar el insumo.',$e);}
    }
    public function lockByIdOrName(?int $id, string $name): ?array
    {
        try {
            if ($id !== null && $id > 0) {
                $stmt=$this->database->connection()->prepare('SELECT id_insumos,cantidad FROM insumos_agricolas WHERE id_insumos=? FOR UPDATE');
                $stmt->bind_param('i',$id);
            } else {
                $stmt=$this->database->connection()->prepare('SELECT id_insumos,cantidad FROM insumos_agricolas WHERE nombre=? ORDER BY id_insumos LIMIT 1 FOR UPDATE');
                $stmt->bind_param('s',$name);
            }
            $stmt->execute(); $row=$stmt->get_result()->fetch_assoc(); $stmt->close();
            return is_array($row)?['id_insumos'=>(int)$row['id_insumos'],'cantidad'=>(float)$row['cantidad']]:null;
        } catch(Throwable $e){throw new DatabaseException(previous:$e);}
    }
    public function decrementStock(int $id,float $quantity): bool
    {
        try{$stmt=$this->database->connection()->prepare('UPDATE insumos_agricolas SET cantidad=cantidad-? WHERE id_insumos=? AND cantidad>=?');$stmt->bind_param('did',$quantity,$id,$quantity);$stmt->execute();$ok=$stmt->affected_rows===1;$stmt->close();return $ok;}catch(Throwable $e){throw new DatabaseException('No se pudo actualizar el inventario.',$e);}
    }
    public function recordDelivery(int $insumoId,string $userId,int $solicitudId,float $quantity): void
    {
        try{$stmt=$this->database->connection()->prepare("INSERT INTO movimientos_insumos(id_insumo,id_usuario,id_producto_solicitud,tipo,estado,cantidad,cantidad_solicitada,cantidad_entregada,observaciones,fecha_movimiento) VALUES(?,?,?,'Salida','Salida',?,?,?,'Entrega a agricultor',NOW())");$stmt->bind_param('isiddd',$insumoId,$userId,$solicitudId,$quantity,$quantity,$quantity);$stmt->execute();$stmt->close();}catch(Throwable $e){throw new DatabaseException('No se pudo registrar el movimiento.',$e);}
    }
    public function setStock(int $id,float $quantity): bool
    {
        try{$stmt=$this->database->connection()->prepare('UPDATE insumos_agricolas SET cantidad=? WHERE id_insumos=?');$stmt->bind_param('di',$quantity,$id);$stmt->execute();$ok=$stmt->affected_rows===1;$stmt->close();return $ok;}catch(Throwable $e){throw new DatabaseException('No se pudo ajustar el stock.',$e);}
    }
    public function recordAdjustment(int $id,string $userId,float $change,float $previous,float $new,?string $observations): void
    {
        try{$stmt=$this->database->connection()->prepare("INSERT INTO movimientos_inventario(id_insumo,id_usuario,tipo,cantidad,stock_anterior,stock_nuevo,observaciones) VALUES(?,?,'Ajuste',?,?,?,?)");$stmt->bind_param('isddds',$id,$userId,$change,$previous,$new,$observations);$stmt->execute();$stmt->close();}catch(Throwable $e){throw new DatabaseException('No se pudo registrar el ajuste.',$e);}
    }
}
