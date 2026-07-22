<?php
declare(strict_types=1);
namespace App\Modules\Solicitudes\Repositories;
use App\Core\Database;
use App\Shared\Exceptions\DatabaseException;
use App\Shared\Interfaces\SolicitudRepositoryInterface;
use Throwable;
final class SolicitudRepository implements SolicitudRepositoryInterface
{
    public function __construct(private readonly Database $database) {}
    public function adminDashboard(): array
    {
        try {
            $stmt=$this->database->connection()->prepare('SELECT ps.*,u.nombre agricultor_nombre,u.email agricultor_email FROM productos_solicitud ps JOIN usuarios u ON ps.id_agricultor=u.id_usuario ORDER BY ps.fecha DESC');
            $stmt->execute();$solicitudes=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);$stmt->close();
            $stmt=$this->database->connection()->prepare("SELECT COUNT(*) total,SUM(CASE WHEN estado='Pendiente' THEN 1 ELSE 0 END) pendientes,SUM(CASE WHEN estado='Entregado' THEN 1 ELSE 0 END) entregadas,SUM(CASE WHEN estado='Rechazado' THEN 1 ELSE 0 END) rechazadas FROM productos_solicitud");
            $stmt->execute();$stats=$stmt->get_result()->fetch_assoc()?:[];$stmt->close();
            return ['solicitudes'=>$solicitudes,'stats_solicitudes'=>$stats];
        } catch(Throwable $e) { throw new DatabaseException('No se pudieron consultar las solicitudes.',$e); }
    }
    public function historyByUser(string $userId): array
    {
        try{$stmt=$this->database->connection()->prepare("SELECT ps.id_producto_solicitud,ps.id_lote,ps.nombre,ps.cantidad_solicitada,ps.observaciones,ps.fecha,ps.estado,l.ubicacion lote_ubicacion,ia.unidad_medida FROM productos_solicitud ps LEFT JOIN lotes l ON l.id_lote=ps.id_lote LEFT JOIN insumos_agricolas ia ON ia.id_insumos=ps.id_insumos WHERE ps.id_agricultor=? ORDER BY ps.fecha DESC,ps.id_producto_solicitud DESC");$stmt->bind_param('s',$userId);$stmt->execute();$rows=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);$stmt->close();return $rows;}catch(Throwable $e){throw new DatabaseException('No se pudo consultar el historial de solicitudes.',$e);}
    }
    public function lockInState(int $id,string $state): ?array
    {
        try{$stmt=$this->database->connection()->prepare('SELECT id_producto_solicitud,id_insumos,nombre,cantidad_solicitada,estado FROM productos_solicitud WHERE id_producto_solicitud=? AND estado=? FOR UPDATE');$stmt->bind_param('is',$id,$state);$stmt->execute();$row=$stmt->get_result()->fetch_assoc();$stmt->close();return is_array($row)?$row:null;}catch(Throwable $e){throw new DatabaseException(previous:$e);}
    }
    public function transition(int $id,string $from,string $to,?int $insumoId=null): bool
    {
        try{if($insumoId!==null){$stmt=$this->database->connection()->prepare('UPDATE productos_solicitud SET id_insumos=?,estado=? WHERE id_producto_solicitud=? AND estado=?');$stmt->bind_param('isis',$insumoId,$to,$id,$from);}else{$stmt=$this->database->connection()->prepare('UPDATE productos_solicitud SET estado=? WHERE id_producto_solicitud=? AND estado=?');$stmt->bind_param('sis',$to,$id,$from);}$stmt->execute();$ok=$stmt->affected_rows===1;$stmt->close();return $ok;}catch(Throwable $e){throw new DatabaseException('No se pudo actualizar la solicitud.',$e);}
    }
    public function ownedLoteArea(int $loteId,string $userId): ?float
    {
        try{$stmt=$this->database->connection()->prepare('SELECT l.area FROM lotes l INNER JOIN cultivos c ON c.id_cultivo=l.id_cultivo WHERE l.id_lote=? AND c.id_usuario=?');$stmt->bind_param('is',$loteId,$userId);$stmt->execute();$row=$stmt->get_result()->fetch_assoc();$stmt->close();return is_array($row)?(float)$row['area']:null;}catch(Throwable $e){throw new DatabaseException(previous:$e);}
    }
    public function findInsumo(int $id): ?array
    {
        try{$stmt=$this->database->connection()->prepare('SELECT id_insumos,nombre,tipo FROM insumos_agricolas WHERE id_insumos=?');$stmt->bind_param('i',$id);$stmt->execute();$row=$stmt->get_result()->fetch_assoc();$stmt->close();return is_array($row)?['id_insumos'=>(int)$row['id_insumos'],'nombre'=>(string)$row['nombre'],'tipo'=>(string)($row['tipo']??'')]:null;}catch(Throwable $e){throw new DatabaseException(previous:$e);}
    }
    public function create(string $userId,int $loteId,int $insumoId,string $stage,string $name,float $quantity,?string $observations): int
    {
        try{$stmt=$this->database->connection()->prepare("INSERT INTO productos_solicitud(id_agricultor,id_lote,id_insumos,etapa,nombre,cantidad_solicitada,observaciones,fecha,estado) VALUES(?,?,?,?,?,?,?,NOW(),'Pendiente')");$stmt->bind_param('siissds',$userId,$loteId,$insumoId,$stage,$name,$quantity,$observations);$stmt->execute();$id=$stmt->insert_id;$stmt->close();return $id;}catch(Throwable $e){throw new DatabaseException('No se pudo registrar la solicitud.',$e);}
    }
}
