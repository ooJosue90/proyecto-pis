<?php

declare(strict_types=1);

namespace App\Modules\Cultivos\Repositories;

use App\Core\Database;
use App\Shared\Exceptions\DatabaseException;
use Throwable;

final class AdminAgricultureRepository
{
    public function __construct(private readonly Database $database)
    {
    }

    /** @return array{cultivos:list<array<string,mixed>>,lotes:list<array<string,mixed>>,stats_cultivos:array<string,mixed>,stats_lotes:array<string,mixed>,area_total:float} */
    public function dashboard(): array
    {
        try {
            $cultivos = $this->rows(
                'SELECT c.*, u.nombre AS agricultor_nombre, COUNT(l.id_lote) AS total_lotes,
                        SUM(CASE WHEN l.estado_cultivo = \'en_cosecha\' THEN 1 ELSE 0 END) AS lotes_en_cosecha,
                        SUM(CASE WHEN l.estado_cultivo = \'finalizado\' THEN 1 ELSE 0 END) AS lotes_finalizados,
                        SUM(CASE WHEN l.estado_cultivo = \'cancelado\' THEN 1 ELSE 0 END) AS lotes_cancelados
                 FROM cultivos c
                 LEFT JOIN usuarios u ON c.id_usuario = u.id_usuario
                 LEFT JOIN lotes l ON c.id_cultivo = l.id_cultivo
                 GROUP BY c.id_cultivo
                 ORDER BY c.fecha_siembra DESC'
            );
            $lotes = $this->rows(
                'SELECT l.*, c.tipo AS cultivo_tipo, u.nombre AS agricultor_nombre
                 FROM lotes l
                 LEFT JOIN cultivos c ON l.id_cultivo = c.id_cultivo
                 LEFT JOIN usuarios u ON c.id_usuario = u.id_usuario
                 ORDER BY l.id_lote DESC'
            );
            $statsCultivos = $this->one('SELECT COUNT(*) AS total_cultivos, COUNT(DISTINCT tipo) AS tipos_diferentes FROM cultivos');
            $statsLotes = $this->one('SELECT COUNT(*) AS total_lotes FROM lotes');
            $areaTotal = array_reduce(
                $lotes,
                static fn (float $total, array $lote): float => $total + (float) ($lote['area'] ?? 0),
                0.0
            );

            return compact('cultivos', 'lotes') + [
                'stats_cultivos' => $statsCultivos,
                'stats_lotes' => $statsLotes,
                'area_total' => $areaTotal,
            ];
        } catch (Throwable $exception) {
            throw new DatabaseException('No se pudo consultar la gestión de cultivos.', $exception);
        }
    }

    /** @return array<string,mixed>|null */
    public function cropDetail(int $id): ?array
    {
        return $this->preparedOne(
            'SELECT c.*, u.nombre AS agricultor, COUNT(l.id_lote) AS total_lotes,
                    SUM(CASE WHEN l.estado_cultivo = \'activo\' THEN 1 ELSE 0 END) AS lotes_activos,
                    SUM(CASE WHEN l.estado_cultivo = \'en_cosecha\' THEN 1 ELSE 0 END) AS lotes_en_cosecha,
                    SUM(CASE WHEN l.estado_cultivo = \'finalizado\' THEN 1 ELSE 0 END) AS lotes_finalizados,
                    SUM(CASE WHEN l.estado_cultivo = \'cancelado\' THEN 1 ELSE 0 END) AS lotes_cancelados,
                    MAX(l.fecha_fin_cosecha_real) AS ultima_cosecha_real
             FROM cultivos c
             LEFT JOIN usuarios u ON c.id_usuario = u.id_usuario
             LEFT JOIN lotes l ON c.id_cultivo = l.id_cultivo
             WHERE c.id_cultivo = ? GROUP BY c.id_cultivo',
            $id
        );
    }

    /** @return array<string,mixed>|null */
    public function lotDetail(int $id): ?array
    {
        return $this->preparedOne(
            'SELECT l.*, c.tipo AS cultivo, c.fecha_siembra, u.nombre AS agricultor
             FROM lotes l
             LEFT JOIN cultivos c ON l.id_cultivo = c.id_cultivo
             LEFT JOIN usuarios u ON c.id_usuario = u.id_usuario
             WHERE l.id_lote = ?',
            $id
        );
    }

    /** @return list<array<string,mixed>> */
    public function lotHistory(int $id): array
    {
        $statement = $this->database->connection()->prepare(
            'SELECT ps.*, u.nombre AS agricultor_nombre
             FROM productos_solicitud ps
             JOIN usuarios u ON ps.id_agricultor = u.id_usuario
             WHERE ps.id_lote = ? ORDER BY ps.fecha DESC'
        );
        $statement->bind_param('i', $id);
        $statement->execute();
        $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();
        return $rows;
    }

    public function deleteLot(int $id): bool
    {
        try {
            $statement = $this->database->connection()->prepare('DELETE FROM lotes WHERE id_lote = ?');
            $statement->bind_param('i', $id);
            $statement->execute();
            $deleted = $statement->affected_rows === 1;
            $statement->close();
            return $deleted;
        } catch (Throwable $exception) {
            throw new DatabaseException('No se puede eliminar un lote con registros relacionados.', $exception);
        }
    }

    /** @return list<array<string,mixed>> */
    private function rows(string $sql): array
    {
        $statement = $this->database->connection()->prepare($sql);
        $statement->execute();
        $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();
        return $rows;
    }

    /** @return array<string,mixed> */
    private function one(string $sql): array
    {
        return $this->rows($sql)[0] ?? [];
    }

    /** @return array<string,mixed>|null */
    private function preparedOne(string $sql, int $id): ?array
    {
        try {
            $statement = $this->database->connection()->prepare($sql);
            $statement->bind_param('i', $id);
            $statement->execute();
            $row = $statement->get_result()->fetch_assoc();
            $statement->close();
            return is_array($row) ? $row : null;
        } catch (Throwable $exception) {
            throw new DatabaseException('No se pudo consultar el detalle solicitado.', $exception);
        }
    }
}
