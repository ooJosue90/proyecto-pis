<?php

declare(strict_types=1);

namespace App\Modules\Movimientos\Repositories;

use App\Core\Database;use App\Shared\Exceptions\DatabaseException;use Throwable;
final class MovimientoRepository
{
    public function __construct(private readonly Database $database){}
    public function dashboard():array{try{$queries=['movimientos_insumos'=>'SELECT mi.*,ia.nombre insumo_nombre,ia.tipo insumo_tipo,u.nombre usuario_nombre,ps.nombre producto_solicitud_nombre FROM movimientos_insumos mi JOIN insumos_agricolas ia ON mi.id_insumo=ia.id_insumos JOIN usuarios u ON mi.id_usuario=u.id_usuario LEFT JOIN productos_solicitud ps ON mi.id_producto_solicitud=ps.id_producto_solicitud ORDER BY mi.fecha_movimiento DESC','cosechas'=>'SELECT pf.*,pf.cantidad cantidad_cosechada,pf.unidad_medida unidad_cosecha,pf.fecha fecha_cosecha,pf.observaciones observacion,u.nombre usuario_nombre,l.ubicacion lote_ubicacion,l.estado_cultivo,l.fecha_fin_cosecha_real,c.tipo cultivo_tipo FROM productos_finales pf JOIN usuarios u ON pf.id_usuario=u.id_usuario JOIN lotes l ON pf.id_lote=l.id_lote LEFT JOIN cultivos c ON l.id_cultivo=c.id_cultivo ORDER BY pf.fecha DESC,pf.id_producto_final DESC','stats_movimientos'=>"SELECT COUNT(*) total_movimientos,SUM(CASE WHEN estado='Entrada' THEN 1 ELSE 0 END) entradas,SUM(CASE WHEN estado='Salida' THEN 1 ELSE 0 END) salidas FROM movimientos_insumos"];$out=[];foreach($queries as $key=>$sql){$stmt=$this->database->connection()->prepare($sql);$stmt->execute();$rows=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);$stmt->close();$out[$key]=$key==='stats_movimientos'?($rows[0]??[]):$rows;}$out['total_cosechas']=count($out['cosechas']);return $out;}catch(Throwable $e){throw new DatabaseException('No se pudieron consultar los movimientos.',$e);}}
}
