<?php

declare(strict_types=1);

namespace App\Modules\Asistente\Repositories;

use App\Core\Database;
use App\Shared\Exceptions\DatabaseException;
use App\Shared\Interfaces\AssistantDataRepositoryInterface;
use Throwable;

final class AssistantDataRepository implements AssistantDataRepositoryInterface
{
    public function __construct(private readonly Database $database)
    {
    }

    public function context(
        string $topic,
        string $role,
        string $userId,
        int $limit,
        array $criteria = []
    ): array {
        $limit = max(1, min($limit, 20));

        try {
            [$sql, $params] = $this->query($topic, $role, $userId, $limit, $criteria);
            $statement = $this->database->connection()->prepare($sql);
            $statement->execute($params);
            $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
            $statement->close();
            return $rows;
        } catch (Throwable $exception) {
            throw new DatabaseException('No se pudo construir el contexto de ADA.', $exception);
        }
    }

    /** @return array{0:string,1:list<mixed>} */
    private function query(string $topic, string $role, string $userId, int $limit, array $criteria): array
    {
        $operation = (string) ($criteria['operation'] ?? 'list');

        if ($topic === 'reportes' || $operation === 'summary') {
            return [$this->summarySql($role), $role === 'Agricultor' ? [$userId, $userId, $userId, $userId, $userId] : []];
        }

        if ($topic === 'agricultura') {
            return $this->agriculturalContext($role, $userId, $limit);
        }

        if ($operation === 'count') {
            return $this->countQuery($topic, $role, $userId, $criteria);
        }

        return $this->listQuery($topic, $role, $userId, $limit, $criteria);
    }

    /** @return array{0:string,1:list<mixed>} */
    private function listQuery(string $topic, string $role, string $userId, int $limit, array $criteria): array
    {
        $owned = $role === 'Agricultor';
        $status = is_string($criteria['status'] ?? null) ? $criteria['status'] : null;
        $operation = (string) ($criteria['operation'] ?? 'list');
        $period = (string) ($criteria['period'] ?? 'all');
        $params = [];

        [$sql, $conditions, $dateColumn] = match ($topic) {
            'usuarios' => [
                'SELECT nombre,email,rol,fecha_registro FROM usuarios',
                [],
                'fecha_registro',
            ],
            'inventario' => [
                'SELECT nombre,tipo,unidad_medida,cantidad,observaciones FROM insumos_agricolas',
                $operation === 'low_stock' ? ['cantidad <= 10'] : [],
                null,
            ],
            'proveedores' => [
                'SELECT Nombre nombre,ruc_cedula,telefono,email,direccion FROM proveedor',
                [],
                null,
            ],
            'pedidos' => [
                'SELECT p.nombre_producto,p.cantidad,p.unidad_medida,p.estado,p.fecha,pr.Nombre proveedor
                 FROM pedidos p JOIN proveedor pr ON p.id_proveedor=pr.id_proveedor',
                $status === 'Pendiente' ? ["p.estado = 'Pendiente'"] : [],
                'p.fecha',
            ],
            'facturas' => [
                'SELECT fc.numero_factura,fc.fecha,fc.total,fc.estado,p.Nombre proveedor
                 FROM facturas_compra fc JOIN proveedor p ON fc.id_proveedor=p.id_proveedor',
                $status === 'Pendiente' ? ["fc.estado = 'Registrada'"] : [],
                'fc.fecha',
            ],
            'movimientos' => [
                'SELECT mi.estado,mi.cantidad,mi.fecha_movimiento,ia.nombre insumo
                 FROM movimientos_insumos mi JOIN insumos_agricolas ia ON mi.id_insumo=ia.id_insumos',
                [],
                'mi.fecha_movimiento',
            ],
            'cultivos' => [
                'SELECT c.nombre,c.tipo,c.fecha_siembra,u.nombre agricultor,
                        (SELECT COUNT(*) FROM lotes lx WHERE lx.id_cultivo=c.id_cultivo) total_lotes
                 FROM cultivos c JOIN usuarios u ON c.id_usuario=u.id_usuario',
                $owned ? ['c.id_usuario = ?'] : [],
                'c.fecha_siembra',
            ],
            'lotes' => [
                'SELECT l.id_lote,l.ubicacion,l.area,l.etapa_actual,l.estado_cultivo,
                        CASE l.etapa_actual WHEN 0 THEN \'Sin iniciar\' WHEN 1 THEN \'Siembra\'
                             WHEN 2 THEN \'Riego y desarrollo\' WHEN 3 THEN \'Cosecha\'
                             ELSE \'Desconocida\' END etapa,
                        l.estado_fase_siembra,l.estado_fase_riego,l.estado_fase_cosecha,
                        c.nombre cultivo,c.tipo tipo_cultivo
                 FROM lotes l JOIN cultivos c ON l.id_cultivo=c.id_cultivo',
                array_values(array_filter([
                    $owned ? 'c.id_usuario = ?' : null,
                    in_array($status, ['finalizado', 'cancelado'], true) ? 'l.estado_cultivo = ?' : null,
                ])),
                'l.fecha_registro',
            ],
            'solicitudes' => [
                'SELECT nombre,cantidad_solicitada,estado,fecha,etapa,id_lote FROM productos_solicitud',
                array_values(array_filter([
                    $owned ? 'id_agricultor = ?' : null,
                    $status !== null ? 'estado = ?' : null,
                ])),
                'fecha',
            ],
            'produccion' => [
                'SELECT pf.nombre_producto,pf.cantidad,pf.unidad_medida,pf.fecha,l.ubicacion lote
                 FROM productos_finales pf JOIN lotes l ON pf.id_lote=l.id_lote',
                $owned ? ['pf.id_usuario = ?'] : [],
                'pf.fecha',
            ],
            'plagas' => [
                'SELECT p.nombre,p.fecha,l.ubicacion lote,c.nombre cultivo,l.etapa_actual
                 FROM plagas p JOIN lotes l ON p.id_lote=l.id_lote
                 JOIN cultivos c ON l.id_cultivo=c.id_cultivo',
                $owned ? ['p.id_usuario = ?'] : [],
                'p.fecha',
            ],
            'notificaciones' => [
                'SELECT mensaje,leida,fecha FROM notificaciones',
                $status === 'Pendiente' ? ['leida = 0'] : [],
                'fecha',
            ],
            default => throw new DatabaseException('Tema de contexto no permitido.'),
        };

        if ($owned && in_array($topic, ['cultivos', 'lotes', 'solicitudes', 'produccion', 'plagas'], true)) {
            $params[] = $userId;
        }
        if ($topic === 'lotes' && in_array($status, ['finalizado', 'cancelado'], true)) {
            $params[] = $status;
        }
        if ($topic === 'solicitudes' && $status !== null) {
            $params[] = $status;
        }

        $periodCondition = $this->periodCondition($dateColumn, $period);
        if ($periodCondition !== null) {
            $conditions[] = $periodCondition;
        }

        if ($conditions !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= match ($topic) {
            'usuarios', 'proveedores' => ' ORDER BY nombre',
            'inventario' => ' ORDER BY cantidad ASC,nombre',
            'cultivos' => ' ORDER BY c.fecha_siembra DESC',
            'lotes' => ' ORDER BY l.etapa_actual ASC,l.fecha_registro DESC',
            'solicitudes' => ' ORDER BY fecha DESC',
            'produccion' => ' ORDER BY pf.fecha DESC',
            'plagas' => ' ORDER BY p.fecha DESC',
            'pedidos' => ' ORDER BY p.fecha DESC',
            'facturas' => ' ORDER BY fc.fecha DESC',
            'movimientos' => ' ORDER BY mi.fecha_movimiento DESC',
            'notificaciones' => ' ORDER BY fecha DESC',
            default => '',
        };

        return [$sql . " LIMIT {$limit}", $params];
    }

    /** @return array{0:string,1:list<mixed>} */
    private function countQuery(string $topic, string $role, string $userId, array $criteria): array
    {
        $owned = $role === 'Agricultor';
        $status = is_string($criteria['status'] ?? null) ? $criteria['status'] : null;
        $params = [];

        $sql = match ($topic) {
            'usuarios' => 'SELECT COUNT(*) total FROM usuarios',
            'inventario' => 'SELECT COUNT(*) registros,COALESCE(SUM(cantidad),0) cantidad_total,
                                    SUM(CASE WHEN cantidad<=10 THEN 1 ELSE 0 END) stock_bajo
                             FROM insumos_agricolas',
            'proveedores' => 'SELECT COUNT(*) total FROM proveedor',
            'pedidos' => "SELECT COUNT(*) total,
                                 SUM(CASE WHEN estado='Pendiente' THEN 1 ELSE 0 END) pendientes
                          FROM pedidos",
            'facturas' => "SELECT COUNT(*) total,COALESCE(SUM(total),0) valor_total,
                                  SUM(CASE WHEN estado='Registrada' THEN 1 ELSE 0 END) registradas
                           FROM facturas_compra",
            'movimientos' => "SELECT COUNT(*) total,
                                     SUM(CASE WHEN estado='Entrada' THEN cantidad ELSE 0 END) entradas,
                                     SUM(CASE WHEN estado='Salida' THEN cantidad ELSE 0 END) salidas
                              FROM movimientos_insumos",
            'cultivos' => 'SELECT COUNT(*) total FROM cultivos' . ($owned ? ' WHERE id_usuario=?' : ''),
            'lotes' => 'SELECT COUNT(*) total,COALESCE(SUM(l.area),0) area_total
                        FROM lotes l JOIN cultivos c ON l.id_cultivo=c.id_cultivo'
                        . ($owned ? ' WHERE c.id_usuario=?' : ''),
            'solicitudes' => 'SELECT COUNT(*) total,
                                     SUM(CASE WHEN estado=\'Pendiente\' THEN 1 ELSE 0 END) pendientes
                              FROM productos_solicitud'
                              . ($owned || $status !== null ? ' WHERE ' : '')
                              . implode(' AND ', array_values(array_filter([
                                  $owned ? 'id_agricultor=?' : null,
                                  $status !== null ? 'estado=?' : null,
                              ]))),
            'produccion' => 'SELECT COUNT(*) registros,COALESCE(SUM(cantidad),0) cantidad_total
                             FROM productos_finales' . ($owned ? ' WHERE id_usuario=?' : ''),
            'plagas' => 'SELECT COUNT(*) total FROM plagas' . ($owned ? ' WHERE id_usuario=?' : ''),
            'notificaciones' => 'SELECT COUNT(*) total,SUM(CASE WHEN leida=0 THEN 1 ELSE 0 END) no_leidas
                                 FROM notificaciones',
            default => throw new DatabaseException('No existe un conteo para el tema solicitado.'),
        };

        if ($owned && in_array($topic, ['cultivos', 'lotes', 'solicitudes', 'produccion', 'plagas'], true)) {
            $params[] = $userId;
        }
        if ($topic === 'solicitudes' && $status !== null) {
            $params[] = $status;
        }

        return [$sql, $params];
    }

    /** @return array{0:string,1:list<mixed>} */
    private function agriculturalContext(string $role, string $userId, int $limit): array
    {
        $owned = $role === 'Agricultor';
        $sql = 'SELECT l.id_lote,l.ubicacion,l.area,l.etapa_actual,
                       CASE l.etapa_actual WHEN 0 THEN \'Sin iniciar\' WHEN 1 THEN \'Siembra\'
                            WHEN 2 THEN \'Riego y desarrollo\' WHEN 3 THEN \'Cosecha\'
                            ELSE \'Desconocida\' END etapa,
                       l.estado_cultivo,
                       l.estado_fase_siembra,l.estado_fase_riego,l.estado_fase_cosecha,
                       l.fecha_inicio_siembra,l.fecha_fin_siembra,l.fecha_inicio_riego,l.fecha_fin_riego,
                       l.fecha_inicio_cosecha,l.fecha_fin_cosecha,l.fecha_fin_cosecha_real,
                       c.nombre cultivo,c.tipo tipo_cultivo,c.fecha_siembra,
                       COALESCE((SELECT GROUP_CONCAT(ca.nombre ORDER BY ca.nombre SEPARATOR \', \')
                                 FROM cultivos_asociados ca WHERE ca.id_cultivo=c.id_cultivo), \'\') cultivos_asociados,
                       COALESCE((SELECT GROUP_CONCAT(CONCAT(p.nombre, \' (\', DATE(p.fecha), \')\')
                                                    ORDER BY p.fecha DESC SEPARATOR \'; \')
                                 FROM plagas p WHERE p.id_lote=l.id_lote), \'\') historial_plagas,
                       (SELECT COUNT(*) FROM productos_solicitud ps
                        WHERE ps.id_lote=l.id_lote AND ps.estado=\'Pendiente\') solicitudes_pendientes,
                       (SELECT MAX(pf.fecha) FROM productos_finales pf WHERE pf.id_lote=l.id_lote) ultima_produccion,
                       (SELECT COALESCE(SUM(pf.cantidad),0) FROM productos_finales pf
                        WHERE pf.id_lote=l.id_lote) produccion_acumulada
                FROM lotes l JOIN cultivos c ON l.id_cultivo=c.id_cultivo'
                . ($owned ? ' WHERE c.id_usuario=?' : '')
                . " ORDER BY FIELD(l.estado_cultivo,'activo','en_cosecha','finalizado','cancelado'),
                           l.etapa_actual ASC,l.fecha_registro DESC LIMIT {$limit}";

        return [$sql, $owned ? [$userId] : []];
    }

    private function summarySql(string $role): string
    {
        return match ($role) {
            'Bodeguero' => "SELECT
                (SELECT COUNT(*) FROM insumos_agricolas) insumos,
                (SELECT COUNT(*) FROM insumos_agricolas WHERE cantidad<=10) stock_bajo,
                (SELECT COUNT(*) FROM pedidos WHERE estado='Pendiente') pedidos_pendientes,
                (SELECT COUNT(*) FROM productos_solicitud WHERE estado='Aprobado') solicitudes_por_atender",
            'Agricultor' => "SELECT
                (SELECT COUNT(*) FROM cultivos WHERE id_usuario=?) cultivos,
                (SELECT COUNT(*) FROM lotes l JOIN cultivos c ON l.id_cultivo=c.id_cultivo WHERE c.id_usuario=?) lotes,
                (SELECT COUNT(*) FROM productos_solicitud WHERE id_agricultor=? AND estado='Pendiente') solicitudes_pendientes,
                (SELECT COUNT(*) FROM plagas WHERE id_usuario=?) plagas_registradas,
                (SELECT COALESCE(SUM(cantidad),0) FROM productos_finales WHERE id_usuario=?) produccion_acumulada",
            default => "SELECT
                (SELECT COUNT(*) FROM usuarios) usuarios,
                (SELECT COUNT(*) FROM cultivos) cultivos,
                (SELECT COUNT(*) FROM lotes) lotes,
                (SELECT COUNT(*) FROM productos_solicitud WHERE estado='Pendiente') solicitudes_pendientes,
                (SELECT COUNT(*) FROM facturas_compra WHERE estado='Registrada') facturas_pendientes,
                (SELECT COUNT(*) FROM insumos_agricolas WHERE cantidad<=10) insumos_stock_bajo",
        };
    }

    private function periodCondition(?string $column, string $period): ?string
    {
        if ($column === null) {
            return null;
        }

        return match ($period) {
            'today' => "DATE({$column})=CURDATE()",
            'yesterday' => "DATE({$column})=DATE_SUB(CURDATE(),INTERVAL 1 DAY)",
            'week' => "YEARWEEK({$column},1)=YEARWEEK(CURDATE(),1)",
            'month' => "YEAR({$column})=YEAR(CURDATE()) AND MONTH({$column})=MONTH(CURDATE())",
            default => null,
        };
    }
}
