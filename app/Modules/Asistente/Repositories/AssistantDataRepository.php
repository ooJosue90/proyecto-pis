<?php

declare(strict_types=1);

namespace App\Modules\Asistente\Repositories;

use App\Core\Database;use App\Shared\Exceptions\DatabaseException;use App\Shared\Interfaces\AssistantDataRepositoryInterface;use Throwable;

final class AssistantDataRepository implements AssistantDataRepositoryInterface
{
    public function __construct(private readonly Database $database){}
    public function context(string $topic,string $role,string $userId,int $limit):array
    {
        $limit=max(1,min($limit,20));$owned=$role==='Agricultor';$params=[];
        $sql=match($topic){
            'usuarios'=>"SELECT nombre,email,rol,fecha_registro FROM usuarios ORDER BY nombre LIMIT {$limit}",
            'inventario'=>"SELECT nombre,tipo,unidad_medida,cantidad,observaciones FROM insumos_agricolas ORDER BY cantidad,nombre LIMIT {$limit}",
            'proveedores'=>"SELECT Nombre nombre,ruc_cedula,telefono,email,direccion FROM proveedor ORDER BY Nombre LIMIT {$limit}",
            'pedidos'=>"SELECT p.nombre_producto,p.cantidad,p.unidad_medida,p.estado,p.fecha,pr.Nombre proveedor FROM pedidos p JOIN proveedor pr ON p.id_proveedor=pr.id_proveedor ORDER BY p.fecha DESC LIMIT {$limit}",
            'facturas'=>"SELECT fc.numero_factura,fc.fecha,fc.total,fc.estado,p.Nombre proveedor FROM facturas_compra fc JOIN proveedor p ON fc.id_proveedor=p.id_proveedor ORDER BY fc.fecha DESC LIMIT {$limit}",
            'movimientos'=>"SELECT mi.estado,mi.cantidad,mi.fecha_movimiento,ia.nombre insumo FROM movimientos_insumos mi JOIN insumos_agricolas ia ON mi.id_insumo=ia.id_insumos ORDER BY mi.fecha_movimiento DESC LIMIT {$limit}",
            'cultivos'=>"SELECT c.tipo,c.fecha_siembra,u.nombre agricultor FROM cultivos c JOIN usuarios u ON c.id_usuario=u.id_usuario ".($owned?'WHERE c.id_usuario=? ':'')."ORDER BY c.fecha_siembra DESC LIMIT {$limit}",
            'lotes'=>"SELECT l.id_lote,l.ubicacion,l.area,l.etapa_actual,l.estado_cultivo,c.tipo cultivo FROM lotes l JOIN cultivos c ON l.id_cultivo=c.id_cultivo ".($owned?'WHERE c.id_usuario=? ':'')."ORDER BY l.fecha_registro DESC LIMIT {$limit}",
            'solicitudes'=>"SELECT nombre,cantidad_solicitada,estado,fecha,etapa FROM productos_solicitud ".($owned?'WHERE id_agricultor=? ':'')."ORDER BY fecha DESC LIMIT {$limit}",
            'produccion'=>"SELECT pf.nombre_producto,pf.cantidad,pf.unidad_medida,pf.fecha,l.ubicacion lote FROM productos_finales pf JOIN lotes l ON pf.id_lote=l.id_lote ".($owned?'WHERE pf.id_usuario=? ':'')."ORDER BY pf.fecha DESC LIMIT {$limit}",
            'plagas'=>"SELECT p.nombre,p.fecha,l.ubicacion lote FROM plagas p JOIN lotes l ON p.id_lote=l.id_lote ".($owned?'WHERE p.id_usuario=? ':'')."ORDER BY p.fecha DESC LIMIT {$limit}",
            'notificaciones'=>"SELECT mensaje,leida,fecha FROM notificaciones ORDER BY fecha DESC LIMIT {$limit}",
            'reportes'=>$this->summarySql($role),
            default=>throw new DatabaseException('Tema de contexto no permitido.'),
        };
        if($owned&&in_array($topic,['cultivos','lotes','solicitudes','produccion','plagas'],true)){$params=[$userId];}
        try{$stmt=$this->database->connection()->prepare($sql);$stmt->execute($params);$rows=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);$stmt->close();return $rows;}catch(Throwable $e){throw new DatabaseException('No se pudo construir el contexto de ADA.',$e);}
    }
    private function summarySql(string $role):string{return match($role){'Bodeguero'=>"SELECT (SELECT COUNT(*) FROM insumos_agricolas) insumos,(SELECT COUNT(*) FROM insumos_agricolas WHERE cantidad<=10) stock_bajo,(SELECT COUNT(*) FROM pedidos WHERE estado='Pendiente') pedidos_pendientes,(SELECT COUNT(*) FROM productos_solicitud WHERE estado='Aprobado') solicitudes_por_atender",'Agricultor'=>"SELECT 'Resumen personal disponible por tema' resumen",default=>"SELECT (SELECT COUNT(*) FROM usuarios) usuarios,(SELECT COUNT(*) FROM cultivos) cultivos,(SELECT COUNT(*) FROM lotes) lotes,(SELECT COUNT(*) FROM productos_solicitud WHERE estado='Pendiente') solicitudes_pendientes,(SELECT COUNT(*) FROM facturas_compra WHERE estado='Registrada') facturas_pendientes"};}
}
