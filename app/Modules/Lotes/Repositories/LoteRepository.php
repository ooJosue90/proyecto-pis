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
        'SELECT l.*, c.tipo AS cultivo, u.nombre AS agricultor
         FROM lotes l
         INNER JOIN cultivos c ON c.id_cultivo = l.id_cultivo
         INNER JOIN usuarios u ON u.id_usuario = c.id_usuario';

    public function __construct(private readonly Database $database)
    {
    }

    public function findAll(): array
    {
        return $this->fetchMany(self::SELECT_BASE . ' ORDER BY l.id_lote DESC');
    }

    public function findByUser(string $userId): array
    {
        return $this->fetchMany(
            self::SELECT_BASE . ' WHERE c.id_usuario = ? ORDER BY l.id_lote DESC',
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

    public function cultivoBelongsToUser(int $cultivoId, string $userId): bool
    {
        try {
            $statement = $this->statement(
                'SELECT COUNT(*) AS total FROM cultivos WHERE id_cultivo = ? AND id_usuario = ?',
                'is',
                [$cultivoId, $userId]
            );
            $row = $statement->get_result()->fetch_assoc();
            $statement->close();
            return (int) ($row['total'] ?? 0) > 0;
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
                    etapa_riego, etapa_siembra, etapa_cosecha,
                    fecha_inicio_riego, fecha_fin_riego, fecha_inicio_siembra, fecha_fin_siembra,
                    fecha_inicio_cosecha, fecha_fin_cosecha, fecha_registro
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())'
            );
            $values = [
                $data->cultivoId, $data->ubicacion, $data->area, $data->etapaActual, $data->estado,
                $data->etapaRiego, $data->etapaSiembra, $data->etapaCosecha,
                $data->dates['fecha_inicio_riego'], $data->dates['fecha_fin_riego'],
                $data->dates['fecha_inicio_siembra'], $data->dates['fecha_fin_siembra'],
                $data->dates['fecha_inicio_cosecha'], $data->dates['fecha_fin_cosecha'],
            ];
            $references = [];
            foreach ($values as $key => &$value) {
                $references[$key] = &$value;
            }
            $statement->bind_param('isdisiiissssss', ...$references);
            $statement->execute();
            $id = $statement->insert_id;
            $statement->close();

            return new Lote(
                $id, $data->cultivoId, $data->ubicacion, $data->area,
                $data->etapaActual, $data->estado, $data->dates
            );
        } catch (Throwable $exception) {
            throw new DatabaseException('No se pudo registrar el lote.', $exception);
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
