<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Repositories;

use App\Core\Database;use App\Shared\Exceptions\DatabaseException;use Throwable;
final class DashboardRepository
{
    public function __construct(private readonly Database $database){}
    public function admin():array
    {
        try{return ['alertas_insumos'=>$this->rows("SELECT i.*,CASE WHEN i.cantidad=0 THEN 'CRÍTICO' WHEN i.cantidad<5 THEN 'BAJO' ELSE 'NORMAL' END nivel_alerta FROM insumos_agricolas i WHERE i.cantidad<=5 ORDER BY i.cantidad"),'alertas_productos'=>$this->rows("SELECT pf.*,CASE WHEN pf.cantidad=0 THEN 'CRÍTICO' WHEN pf.cantidad<5 THEN 'BAJO' ELSE 'NORMAL' END nivel_alerta FROM productos_factura pf WHERE pf.cantidad<=5 ORDER BY pf.cantidad"),'metrics'=>$this->rows("SELECT (SELECT COUNT(*) FROM lotes) total_lotes,(SELECT COUNT(*) FROM cultivos) total_cultivos,(SELECT COUNT(*) FROM insumos_agricolas WHERE cantidad=0) total_insumos_criticos,(SELECT COUNT(*) FROM productos_factura WHERE cantidad<=5) total_productos_bajos,(SELECT COUNT(*) FROM insumos_agricolas WHERE cantidad>0 AND cantidad<=5)+(SELECT COUNT(*) FROM productos_factura WHERE cantidad=0) total_stock_critico,(SELECT COUNT(*) FROM insumos_agricolas WHERE cantidad>5)+(SELECT COUNT(*) FROM productos_factura WHERE cantidad>5) total_inventario_operativo,(SELECT COUNT(*) FROM usuarios) total_usuarios,(SELECT COUNT(*) FROM usuarios WHERE rol='Agricultor') agricultores")[0]??[],'event_counts'=>$this->rows("SELECT (SELECT COUNT(*) FROM productos_solicitud WHERE estado='Pendiente') solicitudes,(SELECT MAX(fecha) FROM productos_solicitud WHERE estado='Pendiente') solicitudes_fecha,(SELECT COUNT(*) FROM facturas_compra WHERE estado='Registrada') facturas,(SELECT MAX(fecha_registro) FROM facturas_compra WHERE estado='Registrada') facturas_fecha,(SELECT COUNT(*) FROM pedidos WHERE estado='Recibido' AND fecha>=DATE_SUB(NOW(),INTERVAL 7 DAY)) pedidos,(SELECT MAX(fecha) FROM pedidos WHERE estado='Recibido' AND fecha>=DATE_SUB(NOW(),INTERVAL 7 DAY)) pedidos_fecha,(SELECT COUNT(*) FROM usuarios WHERE fecha_registro>=DATE_SUB(NOW(),INTERVAL 7 DAY)) usuarios,(SELECT MAX(fecha_registro) FROM usuarios WHERE fecha_registro>=DATE_SUB(NOW(),INTERVAL 7 DAY)) usuarios_fecha")[0]??[],'notifications'=>$this->rows('SELECT mensaje,fecha FROM notificaciones WHERE leida=0 ORDER BY fecha DESC'),'last_activity'=>$this->rows("SELECT MAX(fecha_evento) ultima_fecha FROM (SELECT MAX(fecha) fecha_evento FROM productos_solicitud UNION ALL SELECT MAX(fecha_registro) FROM facturas_compra UNION ALL SELECT MAX(fecha) FROM pedidos UNION ALL SELECT MAX(fecha_registro) FROM usuarios UNION ALL SELECT MAX(fecha) FROM notificaciones) eventos")[0]['ultima_fecha']??null,'lotes'=>$this->rows('SELECT l.id_lote,l.ubicacion,l.area,c.nombre cultivo_nombre,c.tipo cultivo_tipo FROM lotes l LEFT JOIN cultivos c ON l.id_cultivo=c.id_cultivo ORDER BY l.etapa_actual ASC, l.id_lote')];}catch(Throwable $e){throw new DatabaseException('No se pudo cargar el panel administrativo.',$e);}
    }
    public function warehouse():array
    {
        try{$metrics=$this->rows("SELECT (SELECT COUNT(*) FROM insumos_agricolas) total_insumos,(SELECT COUNT(*) FROM facturas_compra) total_facturas_compra,(SELECT COUNT(*) FROM pedidos p WHERE p.estado='Pendiente' AND NOT EXISTS (SELECT 1 FROM facturas_compra fc WHERE fc.id_pedido=p.id_pedidos)) total_pedidos_pendientes,(SELECT COUNT(*) FROM productos_solicitud WHERE estado='Aprobado') total_solicitudes_aprobadas")[0]??[];return array_merge(array_map('intval',$metrics),['insumos'=>$this->rows("SELECT * FROM insumos_agricolas ORDER BY CASE tipo WHEN 'Siembra' THEN 1 WHEN 'Riego' THEN 2 WHEN 'Cosecha' THEN 3 ELSE 4 END, nombre"),'pedidos_pendientes'=>$this->rows("SELECT p.id_pedidos,p.id_insumo,p.nombre_producto,p.cantidad,p.unidad_medida,p.fecha,p.estado,pr.Nombre proveedor_nombre,u.nombre usuario_responsable FROM pedidos p JOIN proveedor pr ON p.id_proveedor=pr.id_proveedor JOIN usuarios u ON p.id_usuario=u.id_usuario LEFT JOIN facturas_compra fc ON fc.id_pedido=p.id_pedidos WHERE p.estado='Pendiente' AND fc.id_factura_compra IS NULL ORDER BY p.fecha,p.id_pedidos"),'solicitudes'=>$this->rows("SELECT ps.*,u.nombre agricultor_nombre,COALESCE(l.ubicacion,'Sin lote asignado') lote_ubicacion,COALESCE(ps.etapa,'Sin etapa') etapa_lote,ia.cantidad stock_disponible,ia.unidad_medida FROM productos_solicitud ps JOIN usuarios u ON ps.id_agricultor=u.id_usuario LEFT JOIN lotes l ON ps.id_lote=l.id_lote LEFT JOIN insumos_agricolas ia ON ps.id_insumos=ia.id_insumos WHERE ps.estado='Aprobado' ORDER BY CASE ps.etapa WHEN 'Siembra' THEN 1 WHEN 'Riego' THEN 2 WHEN 'Cosecha' THEN 3 ELSE 4 END, ps.fecha DESC"),'solicitudes_procesadas'=>$this->rows("SELECT ps.*,u.nombre agricultor_nombre FROM productos_solicitud ps JOIN usuarios u ON ps.id_agricultor=u.id_usuario WHERE ps.estado IN ('Entregado','Rechazado','Cancelado') ORDER BY CASE ps.etapa WHEN 'Siembra' THEN 1 WHEN 'Riego' THEN 2 WHEN 'Cosecha' THEN 3 ELSE 4 END, ps.fecha DESC")]);}catch(Throwable $e){throw new DatabaseException('No se pudo cargar el panel de bodega.',$e);}
    }
    public function farmer(string $userId): array
    {
        try {
            $cultivos = $this->rowsForUser(
                'SELECT * FROM cultivos WHERE id_usuario = ? ORDER BY fecha_siembra DESC',
                $userId
            );
            $lotes = $this->rowsForUser(
                "SELECT l.*, c.tipo AS tipo_cultivo, c.nombre AS nombre_cultivo,
                        GROUP_CONCAT(DISTINCT p.nombre ORDER BY p.fecha DESC SEPARATOR ', ') AS problemas_fitosanitarios
                 FROM lotes l
                 LEFT JOIN cultivos c ON l.id_cultivo = c.id_cultivo
                 LEFT JOIN plagas p ON l.id_lote = p.id_lote
                 WHERE c.id_usuario = ?
                 GROUP BY l.id_lote
                 ORDER BY l.etapa_actual ASC, l.id_lote DESC",
                $userId
            );

            return [
                'cultivos' => $cultivos,
                'lotes' => $lotes,
                'insumos' => $this->rows(
                    "SELECT id_insumos, nombre, cantidad, unidad_medida FROM insumos_agricolas ORDER BY CASE tipo WHEN 'Siembra' THEN 1 WHEN 'Riego' THEN 2 WHEN 'Cosecha' THEN 3 ELSE 4 END, nombre"
                ),
            ];
        } catch (Throwable $exception) {
            throw new DatabaseException('No se pudo cargar el panel del agricultor.', $exception);
        }
    }

    private function rowsForUser(string $sql, string $userId): array
    {
        $statement = $this->database->connection()->prepare($sql);
        $statement->bind_param('s', $userId);
        $statement->execute();
        $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();

        return $rows;
    }

    private function rows(string $sql):array{$stmt=$this->database->connection()->prepare($sql);$stmt->execute();$rows=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);$stmt->close();return $rows;}
}
