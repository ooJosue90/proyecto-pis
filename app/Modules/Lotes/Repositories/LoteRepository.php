<?php

declare(strict_types=1);

namespace App\Modules\Lotes\Repositories;

use App\Core\Database;
use App\Modules\Lotes\DTOs\CreateLoteData;
use App\Modules\Lotes\Models\Lote;
use App\Shared\Exceptions\DatabaseException;
use App\Shared\Interfaces\LoteRepositoryInterface;
use mysqli_stmt;
use Throwable;

final class LoteRepository implements LoteRepositoryInterface
{
    private const SELECT_BASE =
        'SELECT l.*, CONCAT(c.nombre, \' · \', c.tipo) AS cultivo, u.nombre AS agricultor
         FROM lotes l
         INNER JOIN cultivos c ON c.id_cultivo = l.id_cultivo
         INNER JOIN usuarios u ON u.id_usuario = c.id_usuario';

    public function __construct(private readonly Database $database)
    {
    }

    public function findAll(): array
    {
        return $this->fetchMany(self::SELECT_BASE . ' ORDER BY l.etapa_actual ASC, l.id_lote DESC');
    }

    public function findByUser(string $userId): array
    {
        return $this->fetchMany(
            self::SELECT_BASE . ' WHERE c.id_usuario = ? ORDER BY l.etapa_actual ASC, l.id_lote DESC',
            's',
            [$userId]
        );
    }

    public function find(int $id): ?Lote
    {
        return $this->fetchOne(self::SELECT_BASE . ' WHERE l.id_lote = ?', 'i', [$id]);
    }

    public function findOwnedBy(int $id, string $userId): ?Lote
    {
        return $this->fetchOne(
            self::SELECT_BASE . ' WHERE l.id_lote = ? AND c.id_usuario = ?',
            'is',
            [$id, $userId]
        );
    }

    public function findCultivoPlantingDate(int $cultivoId, string $userId): ?string
    {
        try {
            $statement = $this->statement(
                'SELECT fecha_siembra FROM cultivos WHERE id_cultivo = ? AND id_usuario = ? LIMIT 1',
                'is',
                [$cultivoId, $userId]
            );
            $row = $statement->get_result()->fetch_assoc();
            $statement->close();
            return is_array($row) ? (string) $row['fecha_siembra'] : null;
        } catch (Throwable $exception) {
            throw new DatabaseException(previous: $exception);
        }
    }

    public function create(CreateLoteData $data): Lote
    {
        try {
            $statement = $this->database->connection()->prepare(
                'INSERT INTO lotes (
                    id_cultivo, ubicacion, area, etapa_actual, estado_cultivo,
                    etapa_siembra, etapa_riego, etapa_cosecha,
                    estado_fase_siembra, estado_fase_riego, estado_fase_cosecha,
                    fecha_inicio_siembra, fecha_fin_siembra, fecha_inicio_riego, fecha_fin_riego,
                    fecha_inicio_cosecha, fecha_fin_cosecha, fecha_registro
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())'
            );
            $values = [
                $data->cultivoId, $data->ubicacion, $data->area, $data->etapaActual, $data->estado,
                $data->etapaSiembra, $data->etapaRiego, $data->etapaCosecha,
                $data->phaseStates[1], $data->phaseStates[2], $data->phaseStates[3],
                $data->dates['fecha_inicio_siembra'], $data->dates['fecha_fin_siembra'],
                $data->dates['fecha_inicio_riego'], $data->dates['fecha_fin_riego'],
                $data->dates['fecha_inicio_cosecha'], $data->dates['fecha_fin_cosecha'],
            ];
            $references = [];
            foreach ($values as $key => &$value) {
                $references[$key] = &$value;
            }
            $types = 'isdisiii' . str_repeat('s', 9);
            $statement->bind_param($types, ...$references);
            $statement->execute();
            $id = $statement->insert_id;
            $statement->close();

            return new Lote(
                $id, $data->cultivoId, $data->ubicacion, $data->area,
                $data->etapaActual, $data->estado, $data->dates, null, null, $data->phaseStates
            );
        } catch (Throwable $exception) {
            throw new DatabaseException('No se pudo registrar el lote.', $exception);
        }
    }

    public function advanceStage(
        int $id,
        ?string $ownerId,
        int $expectedCurrentStage,
        int $nextStage,
        array $phaseStates,
        string $cropState
    ): bool {
        try {
            $statement = $this->database->connection()->prepare(
                'UPDATE lotes l
                 INNER JOIN cultivos c ON c.id_cultivo = l.id_cultivo
                 SET l.etapa_actual = ?,
                     l.etapa_siembra = ?,
                     l.etapa_riego = ?,
                     l.etapa_cosecha = ?,
                     l.estado_fase_siembra = ?,
                     l.estado_fase_riego = ?,
                     l.estado_fase_cosecha = ?,
                     l.estado_cultivo = ?
                 WHERE l.id_lote = ?
                   AND (? IS NULL OR c.id_usuario = ?)
                   AND l.etapa_actual = ?'
            );
            $planting = $nextStage >= 1 ? 1 : 0;
            $irrigation = $nextStage >= 2 ? 1 : 0;
            $harvest = $nextStage >= 3 ? 1 : 0;
            $plantingStatus = $phaseStates[1];
            $irrigationStatus = $phaseStates[2];
            $harvestStatus = $phaseStates[3];
            $statement->bind_param(
                'iiiissssissi',
                $nextStage,
                $planting,
                $irrigation,
                $harvest,
                $plantingStatus,
                $irrigationStatus,
                $harvestStatus,
                $cropState,
                $id,
                $ownerId,
                $ownerId,
                $expectedCurrentStage
            );
            $statement->execute();
            $updated = $statement->affected_rows === 1;
            $statement->close();
            return $updated;
        } catch (Throwable $exception) {
            throw new DatabaseException('No se pudo actualizar la fase del lote.', $exception);
        }
    }

    /** @param list<mixed> $parameters @return list<Lote> */
    private function fetchMany(string $sql, string $types = '', array $parameters = []): array
    {
        try {
            $statement = $this->statement($sql, $types, $parameters);
            $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
            $statement->close();
            return array_map(static fn (array $row): Lote => Lote::fromRow($row), $rows);
        } catch (Throwable $exception) {
            throw new DatabaseException(previous: $exception);
        }
    }

    /** @param list<mixed> $parameters */
    private function fetchOne(string $sql, string $types, array $parameters): ?Lote
    {
        try {
            $statement = $this->statement($sql, $types, $parameters);
            $row = $statement->get_result()->fetch_assoc();
            $statement->close();
            return is_array($row) ? Lote::fromRow($row) : null;
        } catch (Throwable $exception) {
            throw new DatabaseException(previous: $exception);
        }
    }

    /** @param list<mixed> $parameters */
    private function statement(string $sql, string $types, array $parameters): mysqli_stmt
    {
        $statement = $this->database->connection()->prepare($sql);
        if ($types !== '') {
            $references = [];
            foreach ($parameters as $key => &$parameter) {
                $references[$key] = &$parameter;
            }
            $statement->bind_param($types, ...$references);
        }
        $statement->execute();
        return $statement;
    }
}
