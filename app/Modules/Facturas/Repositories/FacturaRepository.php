<?php

declare(strict_types=1);

namespace App\Modules\Facturas\Repositories;

use App\Core\Database;
use App\Shared\Exceptions\DatabaseException;
use App\Shared\Interfaces\FacturaRepositoryInterface;
use Throwable;

final class FacturaRepository implements FacturaRepositoryInterface
{
    public function __construct(private readonly Database $database) {}

    public function schemaReady(): bool
    {
        try {
            foreach (['facturas_compra','factura_compra_detalle','movimientos_inventario'] as $table) {
                $stmt=$this->database->connection()->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=?');$stmt->bind_param('s',$table);$stmt->execute();$ready=(int)$stmt->get_result()->fetch_row()[0]===1;$stmt->close();if(!$ready){return false;}
            }
            foreach ([['pedidos','id_insumo'],['pedidos','estado'],['pedidos','observaciones'],['facturas_compra','id_pedido']] as [$table,$column]) {
                $stmt=$this->database->connection()->prepare('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name=? AND column_name=?');$stmt->bind_param('ss',$table,$column);$stmt->execute();$ready=(int)$stmt->get_result()->fetch_row()[0]===1;$stmt->close();if(!$ready){return false;}
            }
            return true;
        } catch (Throwable) { return false; }
    }

    public function pendingOrders(): array{return $this->rows("SELECT p.id_pedidos,p.id_proveedor,p.id_insumo,p.nombre_producto,p.cantidad,p.unidad_medida,p.observaciones,p.fecha,pr.Nombre proveedor_nombre,pr.ruc_cedula,u.nombre usuario_responsable FROM pedidos p JOIN proveedor pr ON p.id_proveedor=pr.id_proveedor JOIN usuarios u ON p.id_usuario=u.id_usuario LEFT JOIN facturas_compra fc ON fc.id_pedido=p.id_pedidos WHERE p.estado='Pendiente' AND fc.id_factura_compra IS NULL ORDER BY p.fecha,p.id_pedidos");}
    public function supplies(): array{return $this->rows('SELECT id_insumos,nombre,tipo,unidad_medida,cantidad FROM insumos_agricolas ORDER BY nombre');}
    public function recentByUser(string $userId): array{return $this->preparedRows('SELECT fc.*,p.Nombre proveedor_nombre FROM facturas_compra fc JOIN proveedor p ON fc.id_proveedor=p.id_proveedor WHERE fc.id_usuario=? ORDER BY fc.fecha_registro DESC LIMIT 10',[$userId]);}
    public function lockOrder(int $id): ?array{return $this->one('SELECT id_pedidos,id_proveedor,id_insumo,nombre_producto,cantidad,unidad_medida,estado FROM pedidos WHERE id_pedidos=? FOR UPDATE',[$id]);}
    public function invoiceExistsForOrder(int $orderId): bool{return $this->scalar('SELECT COUNT(*) FROM facturas_compra WHERE id_pedido=?',[$orderId])>0;}
    public function invoiceNumberExists(int $providerId,string $number): bool{return $this->scalar('SELECT COUNT(*) FROM facturas_compra WHERE id_proveedor=? AND numero_factura=?',[$providerId,$number])>0;}
    public function supplyNameExists(string $name): bool{return $this->scalar('SELECT COUNT(*) FROM insumos_agricolas WHERE LOWER(nombre)=LOWER(?)',[$name])>0;}
    public function createSupply(string $userId,string $name,string $type,?string $description,string $unit,?string $observations):int{return $this->insert('INSERT INTO insumos_agricolas(id_usuario,nombre,tipo,descripcion,unidad_medida,cantidad,observaciones) VALUES(?,?,?,?,?,0,?)',[$userId,$name,$type,$description,$unit,$observations]);}
    public function lockSupply(int $id):?array{return $this->one('SELECT id_insumos,nombre,tipo,unidad_medida,cantidad FROM insumos_agricolas WHERE id_insumos=? FOR UPDATE',[$id]);}
    public function linkOrderSupply(int $orderId,int $supplyId,string $name,string $unit):bool{return $this->execute("UPDATE pedidos SET id_insumo=?,nombre_producto=?,unidad_medida=? WHERE id_pedidos=? AND estado='Pendiente'",[$supplyId,$name,$unit,$orderId])===1;}
    public function createInvoice(int $orderId,int $providerId,string $userId,string $number,string $date,float $total,?string $observations):int{return $this->insert("INSERT INTO facturas_compra(id_pedido,id_proveedor,id_usuario,numero_factura,fecha,total,estado,observaciones) VALUES(?,?,?,?,?,?,'Registrada',?)",[$orderId,$providerId,$userId,$number,$date,$total,$observations]);}
    public function createDetail(int $invoiceId,int $supplyId,string $name,string $unit,float $quantity,float $unitPrice,float $subtotal):int{return $this->insert('INSERT INTO factura_compra_detalle(id_factura_compra,id_insumo,nombre_insumo,unidad_medida,cantidad,precio_unitario,subtotal) VALUES(?,?,?,?,?,?,?)',[$invoiceId,$supplyId,$name,$unit,$quantity,$unitPrice,$subtotal]);}
    public function setSupplyStock(int $supplyId,float $stock):bool{return $this->execute('UPDATE insumos_agricolas SET cantidad=? WHERE id_insumos=?',[$stock,$supplyId])===1;}
    public function recordInventoryEntry(int $invoiceId,int $detailId,int $supplyId,string $userId,float $quantity,float $previous,float $new,string $observations):void{$this->insert("INSERT INTO movimientos_inventario(id_factura_compra,id_factura_compra_detalle,id_insumo,id_usuario,tipo,cantidad,stock_anterior,stock_nuevo,observaciones) VALUES(?,?,?,?,'Entrada',?,?,?,?)",[$invoiceId,$detailId,$supplyId,$userId,$quantity,$previous,$new,$observations]);}
    public function recordSupplyEntry(int $supplyId,string $userId,float $quantity,string $observations):void{$this->insert("INSERT INTO movimientos_insumos(id_insumo,id_usuario,tipo,estado,cantidad,observaciones,fecha_movimiento) VALUES(?,?,'Entrada','Entrada',?,?,NOW())",[$supplyId,$userId,$quantity,$observations]);}
    public function markOrderReceived(int $orderId):bool{return $this->execute("UPDATE pedidos SET estado='Recibido' WHERE id_pedidos=? AND estado='Pendiente'",[$orderId])===1;}
    public function providers():array{return $this->rows('SELECT id_proveedor,Nombre FROM proveedor ORDER BY Nombre');}

    public function findAll(array $filters):array
    {
        $where=[];$params=[];
        if(($filters['id_proveedor']??0)>0){$where[]='fc.id_proveedor=?';$params[]=(int)$filters['id_proveedor'];}
        if(in_array($filters['estado']??'', ['Registrada','Aprobada','Rechazada','Anulada'],true)){$where[]='fc.estado=?';$params[]=$filters['estado'];}
        if(($filters['fecha_desde']??'')!==''){$where[]='fc.fecha>=?';$params[]=$filters['fecha_desde'];}
        if(($filters['fecha_hasta']??'')!==''){$where[]='fc.fecha<=?';$params[]=$filters['fecha_hasta'];}
        $sql='SELECT fc.*,p.Nombre proveedor_nombre,u.nombre bodeguero_nombre,ur.nombre revisor_nombre FROM facturas_compra fc JOIN proveedor p ON fc.id_proveedor=p.id_proveedor JOIN usuarios u ON fc.id_usuario=u.id_usuario LEFT JOIN usuarios ur ON fc.id_usuario_revision=ur.id_usuario '.($where?'WHERE '.implode(' AND ',$where):'').' ORDER BY fc.fecha DESC,fc.id_factura_compra DESC';
        return $this->preparedRows($sql,$params);
    }
    public function stats():array{return $this->rows("SELECT COUNT(*) total_facturas,COALESCE(SUM(total),0) total_monto,COALESCE(SUM(CASE WHEN estado='Aprobada' THEN total ELSE 0 END),0) total_aprobado,SUM(CASE WHEN estado='Registrada' THEN 1 ELSE 0 END) registradas,SUM(CASE WHEN estado='Aprobada' THEN 1 ELSE 0 END) aprobadas,SUM(CASE WHEN estado='Rechazada' THEN 1 ELSE 0 END) rechazadas FROM facturas_compra")[0]??[];}
    public function review(int $invoiceId,string $status,string $reviewerId):bool{return $this->execute("UPDATE facturas_compra SET estado=?,fecha_revision=NOW(),id_usuario_revision=? WHERE id_factura_compra=? AND estado='Registrada'",[$status,$reviewerId,$invoiceId])===1;}
    public function findDetailHeader(int $invoiceId):?array{return $this->one('SELECT fc.*,p.Nombre proveedor_nombre,p.ruc_cedula,p.telefono,p.email proveedor_email,u.nombre bodeguero_nombre,ur.nombre revisor_nombre,pe.nombre_producto pedido_producto,pe.cantidad pedido_cantidad,pe.unidad_medida pedido_unidad FROM facturas_compra fc JOIN proveedor p ON fc.id_proveedor=p.id_proveedor JOIN usuarios u ON fc.id_usuario=u.id_usuario LEFT JOIN usuarios ur ON fc.id_usuario_revision=ur.id_usuario LEFT JOIN pedidos pe ON fc.id_pedido=pe.id_pedidos WHERE fc.id_factura_compra=?',[$invoiceId]);}
    public function findDetailItems(int $invoiceId):array{return $this->preparedRows('SELECT d.*,ia.tipo FROM factura_compra_detalle d JOIN insumos_agricolas ia ON d.id_insumo=ia.id_insumos WHERE d.id_factura_compra=? ORDER BY d.id_factura_compra_detalle',[$invoiceId]);}

    private function rows(string $sql):array{return $this->preparedRows($sql,[]);}
    private function preparedRows(string $sql,array $params):array{try{$stmt=$this->database->connection()->prepare($sql);$stmt->execute($params);$rows=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);$stmt->close();return $rows;}catch(Throwable $e){throw new DatabaseException(previous:$e);}}
    private function one(string $sql,array $params):?array{$rows=$this->preparedRows($sql,$params);return $rows[0]??null;}
    private function scalar(string $sql,array $params):int{$row=$this->one($sql,$params);return (int)array_values($row??[0])[0];}
    private function insert(string $sql,array $params):int{try{$stmt=$this->database->connection()->prepare($sql);$stmt->execute($params);$id=$stmt->insert_id;$stmt->close();return $id;}catch(Throwable $e){throw new DatabaseException('No se pudo guardar la factura de compra.',$e);}}
    private function execute(string $sql,array $params):int{try{$stmt=$this->database->connection()->prepare($sql);$stmt->execute($params);$affected=$stmt->affected_rows;$stmt->close();return $affected;}catch(Throwable $e){throw new DatabaseException('No se pudo actualizar el flujo de facturación.',$e);}}
}
