<?php

declare(strict_types=1);

namespace App\Modules\Reportes\Repositories;

use App\Core\Database;use App\Shared\Exceptions\DatabaseException;use App\Shared\Interfaces\ReporteRepositoryInterface;use Throwable;

final class ReporteRepository implements ReporteRepositoryInterface
{
    public function __construct(private readonly Database $database){}
    public function totals():array{$row=$this->rows('SELECT (SELECT COUNT(*) FROM usuarios) total_usuarios,(SELECT COUNT(*) FROM cultivos) total_cultivos,(SELECT COUNT(*) FROM lotes) total_lotes')[0]??[];return array_map('intval',$row);}
    public function usersByRole():array{return $this->rows('SELECT rol,COUNT(*) cantidad FROM usuarios GROUP BY rol ORDER BY cantidad DESC');}
    public function users():array{return $this->rows('SELECT nombre,email,rol FROM usuarios ORDER BY nombre');}
    public function cropsByType():array{return $this->rows('SELECT tipo,COUNT(*) cantidad FROM cultivos GROUP BY tipo ORDER BY cantidad DESC');}
    public function recentCrops():array{return $this->rows('SELECT c.tipo,c.fecha_siembra,u.nombre agricultor FROM cultivos c JOIN usuarios u ON c.id_usuario=u.id_usuario ORDER BY c.fecha_siembra DESC LIMIT 10');}
    public function recentActivity():array{return $this->rows("SELECT 'usuario' tipo,nombre descripcion,fecha_registro fecha FROM usuarios UNION ALL SELECT 'cultivo' tipo,tipo descripcion,fecha_siembra fecha FROM cultivos ORDER BY fecha DESC LIMIT 10");}
    public function processedRequests():array{return $this->rows("SELECT ps.*,u.nombre agricultor_nombre,COALESCE(l.ubicacion,'Sin lote asignado') lote_ubicacion,COALESCE(ia.unidad_medida,'') unidad_medida FROM productos_solicitud ps JOIN usuarios u ON ps.id_agricultor=u.id_usuario LEFT JOIN lotes l ON ps.id_lote=l.id_lote LEFT JOIN insumos_agricolas ia ON ps.id_insumos=ia.id_insumos WHERE ps.estado IN ('Entregado','Rechazado','Cancelado') ORDER BY CASE ps.etapa WHEN 'Siembra' THEN 1 WHEN 'Riego' THEN 2 WHEN 'Cosecha' THEN 3 ELSE 4 END, ps.fecha DESC");}
    public function invoiceProducts():array{return $this->rows('SELECT pf.nombre,pf.tipo,pf.descripcion,pf.unidad_medida,pf.cantidad,pf.precio,pf.fecha_ingreso,pf.fecha_vencimiento,pf.procesado,f.fecha fecha_factura,u.nombre usuario_nombre FROM productos_factura pf INNER JOIN factura f ON pf.id_factura=f.id_factura INNER JOIN usuarios u ON f.id_usuario=u.id_usuario ORDER BY f.fecha DESC');}
    private function rows(string $sql):array{try{$stmt=$this->database->connection()->prepare($sql);$stmt->execute();$rows=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);$stmt->close();return $rows;}catch(Throwable $e){throw new DatabaseException('No se pudo generar el reporte.',$e);}}
}
