<?php

declare(strict_types=1);

namespace App\Modules\Produccion\Repositories;

use App\Core\Database;
use App\Modules\Produccion\DTOs\FinalizeHarvestData;
use App\Modules\Produccion\Models\Produccion;
use App\Shared\Exceptions\DatabaseException;
use App\Shared\Interfaces\ProduccionRepositoryInterface;
use Throwable;

final class ProduccionRepository implements ProduccionRepositoryInterface
{
    private const SELECT_BASE =
        'SELECT pf.*, l.ubicacion, u.nombre AS agricultor
         FROM productos_finales pf
         INNER JOIN lotes l ON l.id_lote = pf.id_lote
         INNER JOIN usuarios u ON u.id_usuario = pf.id_usuario';

    public function __construct(private readonly Database $database)
    {
    }

    public function findAll(): array { return $this->fetch(self::SELECT_BASE . ' ORDER BY pf.fecha DESC'); }
    public function findByUser(string $userId): array { return $this->fetch(self::SELECT_BASE . ' WHERE pf.id_usuario = ? ORDER BY pf.fecha DESC', 's', [$userId]); }

    public function lockOwnedHarvest(int $loteId, string $userId): ?array
    {
        try {
            $statement = $this->database->connection()->prepare(
                'SELECT l.id_lote, l.estado_cultivo, l.fecha_inicio_cosecha, c.tipo FROM lotes l
                 INNER JOIN cultivos c ON c.id_cultivo = l.id_cultivo
                 WHERE l.id_lote = ? AND c.id_usuario = ? FOR UPDATE'
            );
            $statement->bind_param('is', $loteId, $userId);
            $statement->execute();
            $row = $statement->get_result()->fetch_assoc();
            $statement->close();
            return is_array($row) ? [
                'id_lote' => (int) $row['id_lote'],
                'estado_cultivo' => (string) $row['estado_cultivo'],
                'fecha_inicio_cosecha' => $row['fecha_inicio_cosecha'] !== null ? (string) $row['fecha_inicio_cosecha'] : null,
                'tipo' => (string) $row['tipo'],
            ] : null;
        } catch (Throwable $exception) { throw new DatabaseException(previous: $exception); }
    }

    public function create(FinalizeHarvestData $data, string $productName): Produccion
    {
        try {
            $statement = $this->database->connection()->prepare(
                'INSERT INTO productos_finales (id_usuario,id_lote,nombre_producto,cantidad,unidad_medida,observaciones,fecha)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $userId = $data->userId; $loteId = $data->loteId; $quantity = $data->quantityKg;
            $unit = 'kg'; $observations = $data->formattedObservations(); $date = $data->harvestDate . ' 00:00:00';
            $statement->bind_param('sisdsss', $userId, $loteId, $productName, $quantity, $unit, $observations, $date);
            $statement->execute(); $id = $statement->insert_id; $statement->close();
            return new Produccion($id, $userId, $loteId, $productName, $quantity, $unit, $observations, $date);
        } catch (Throwable $exception) { throw new DatabaseException('No se pudo registrar la producción.', $exception); }
    }

    public function markLoteFinalized(int $loteId, string $harvestDate): bool
    {
        try {
            $statement = $this->database->connection()->prepare(
                "UPDATE lotes SET estado_cultivo = 'finalizado',
                                  estado_fase_cosecha = 'completada',
                                  fecha_fin_cosecha_real = ?
                 WHERE id_lote = ? AND estado_cultivo = 'en_cosecha'"
            );
            $statement->bind_param('si', $harvestDate, $loteId); $statement->execute();
            $updated = $statement->affected_rows === 1; $statement->close(); return $updated;
        } catch (Throwable $exception) { throw new DatabaseException('No se pudo finalizar el lote.', $exception); }
    }

    /** @param list<mixed> $parameters @return list<Produccion> */
    private function fetch(string $sql, string $types = '', array $parameters = []): array
    {
        try {
            $statement = $this->database->connection()->prepare($sql);
            if ($types !== '') { $refs = []; foreach ($parameters as $key => &$parameter) { $refs[$key] = &$parameter; } $statement->bind_param($types, ...$refs); }
            $statement->execute(); $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC); $statement->close();
            return array_map(static fn (array $row): Produccion => Produccion::fromRow($row), $rows);
        } catch (Throwable $exception) { throw new DatabaseException(previous: $exception); }
    }
}
