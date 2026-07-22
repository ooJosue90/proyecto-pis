<?php

declare(strict_types=1);

namespace App\Modules\Cultivos\Repositories;

use App\Core\Database;
use App\Modules\Cultivos\DTOs\CreateCultivoData;
use App\Modules\Cultivos\Models\Cultivo;
use App\Shared\Exceptions\DatabaseException;
use App\Shared\Interfaces\CultivoRepositoryInterface;
use mysqli_stmt;
use Throwable;

final class CultivoRepository implements CultivoRepositoryInterface
{
    public function __construct(private readonly Database $database)
    {
    }

    public function findAll(): array
    {
        return $this->fetchMany(
            'SELECT c.id_cultivo, c.id_usuario, c.tipo, c.fecha_siembra, u.nombre AS agricultor
             FROM cultivos c
             INNER JOIN usuarios u ON u.id_usuario = c.id_usuario
             ORDER BY c.fecha_siembra DESC, c.id_cultivo DESC'
        );
    }

    public function findByUser(string $userId): array
    {
        return $this->fetchMany(
            'SELECT id_cultivo, id_usuario, tipo, fecha_siembra
             FROM cultivos
             WHERE id_usuario = ?
             ORDER BY fecha_siembra DESC, id_cultivo DESC',
            's',
            [$userId]
        );
    }

    public function find(int $id): ?Cultivo
    {
        return $this->fetchOne(
            'SELECT c.id_cultivo, c.id_usuario, c.tipo, c.fecha_siembra, u.nombre AS agricultor
             FROM cultivos c
             INNER JOIN usuarios u ON u.id_usuario = c.id_usuario
             WHERE c.id_cultivo = ?',
            'i',
            [$id]
        );
    }

    public function findOwnedBy(int $id, string $userId): ?Cultivo
    {
        return $this->fetchOne(
            'SELECT id_cultivo, id_usuario, tipo, fecha_siembra
             FROM cultivos
             WHERE id_cultivo = ? AND id_usuario = ?',
            'is',
            [$id, $userId]
        );
    }

    public function create(CreateCultivoData $data): Cultivo
    {
        try {
            $connection = $this->database->connection();
            $statement = $connection->prepare(
                'INSERT INTO cultivos (id_usuario, tipo, fecha_siembra) VALUES (?, ?, ?)'
            );
            $userId = $data->userId;
            $tipo = $data->tipo;
            $fechaSiembra = $data->fechaSiembra;
            $statement->bind_param('sss', $userId, $tipo, $fechaSiembra);
            $statement->execute();
            $id = $statement->insert_id;
            $statement->close();

            return new Cultivo($id, $data->userId, $data->tipo, $data->fechaSiembra);
        } catch (Throwable $exception) {
            throw new DatabaseException('No se pudo registrar el cultivo.', $exception);
        }
    }

    public function countLotes(int $id): int
    {
        try {
            $statement = $this->statement(
                'SELECT COUNT(*) AS total FROM lotes WHERE id_cultivo = ?',
                'i',
                [$id]
            );
            $row = $statement->get_result()->fetch_assoc();
            $statement->close();
            return (int) ($row['total'] ?? 0);
        } catch (Throwable $exception) {
            throw new DatabaseException(previous: $exception);
        }
    }

    public function delete(int $id): bool
    {
        try {
            $statement = $this->statement(
                'DELETE FROM cultivos WHERE id_cultivo = ?',
                'i',
                [$id]
            );
            $deleted = $statement->affected_rows === 1;
            $statement->close();
            return $deleted;
        } catch (Throwable $exception) {
            throw new DatabaseException('No se pudo eliminar el cultivo.', $exception);
        }
    }

    /** @param list<mixed> $parameters @return list<Cultivo> */
    private function fetchMany(string $sql, string $types = '', array $parameters = []): array
    {
        try {
            $statement = $this->statement($sql, $types, $parameters);
            $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
            $statement->close();
            return array_map(static fn (array $row): Cultivo => Cultivo::fromRow($row), $rows);
        } catch (Throwable $exception) {
            throw new DatabaseException(previous: $exception);
        }
    }

    /** @param list<mixed> $parameters */
    private function fetchOne(string $sql, string $types, array $parameters): ?Cultivo
    {
        try {
            $statement = $this->statement($sql, $types, $parameters);
            $row = $statement->get_result()->fetch_assoc();
            $statement->close();
            return is_array($row) ? Cultivo::fromRow($row) : null;
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
