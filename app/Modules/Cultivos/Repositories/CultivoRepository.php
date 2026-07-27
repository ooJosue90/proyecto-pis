<?php

declare(strict_types=1);

namespace App\Modules\Cultivos\Repositories;

use App\Core\Database;
use App\Modules\Cultivos\DTOs\CreateCultivoData;
use App\Modules\Cultivos\Models\Cultivo;
use App\Shared\Domain\AssociatedCropCatalog;
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
            'SELECT c.id_cultivo, c.id_usuario, c.nombre, c.tipo, c.fecha_siembra, u.nombre AS agricultor,
                    COALESCE((
                        SELECT GROUP_CONCAT(ca.codigo ORDER BY ca.id_cultivo_asociado SEPARATOR \',\')
                        FROM cultivos_asociados ca WHERE ca.id_cultivo = c.id_cultivo
                    ), \'\') AS cultivos_asociados_codigos
             FROM cultivos c
             INNER JOIN usuarios u ON u.id_usuario = c.id_usuario
             ORDER BY c.fecha_siembra DESC, c.id_cultivo DESC'
        );
    }

    public function findByUser(string $userId): array
    {
        return $this->fetchMany(
            'SELECT c.id_cultivo, c.id_usuario, c.nombre, c.tipo, c.fecha_siembra,
                    COALESCE((
                        SELECT GROUP_CONCAT(ca.codigo ORDER BY ca.id_cultivo_asociado SEPARATOR \',\')
                        FROM cultivos_asociados ca WHERE ca.id_cultivo = c.id_cultivo
                    ), \'\') AS cultivos_asociados_codigos
             FROM cultivos c
             WHERE c.id_usuario = ?
             ORDER BY c.fecha_siembra DESC, c.id_cultivo DESC',
            's',
            [$userId]
        );
    }

    public function find(int $id): ?Cultivo
    {
        return $this->fetchOne(
            'SELECT c.id_cultivo, c.id_usuario, c.nombre, c.tipo, c.fecha_siembra, u.nombre AS agricultor,
                    COALESCE((
                        SELECT GROUP_CONCAT(ca.codigo ORDER BY ca.id_cultivo_asociado SEPARATOR \',\')
                        FROM cultivos_asociados ca WHERE ca.id_cultivo = c.id_cultivo
                    ), \'\') AS cultivos_asociados_codigos
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
            'SELECT c.id_cultivo, c.id_usuario, c.nombre, c.tipo, c.fecha_siembra,
                    COALESCE((
                        SELECT GROUP_CONCAT(ca.codigo ORDER BY ca.id_cultivo_asociado SEPARATOR \',\')
                        FROM cultivos_asociados ca WHERE ca.id_cultivo = c.id_cultivo
                    ), \'\') AS cultivos_asociados_codigos
             FROM cultivos c
             WHERE c.id_cultivo = ? AND c.id_usuario = ?',
            'is',
            [$id, $userId]
        );
    }

    public function create(CreateCultivoData $data): Cultivo
    {
        try {
            $connection = $this->database->connection();
            $connection->begin_transaction();
            $statement = $connection->prepare(
                'INSERT INTO cultivos (id_usuario, nombre, tipo, fecha_siembra) VALUES (?, ?, ?, ?)'
            );
            $userId = $data->userId;
            $nombre = $data->nombre;
            $tipo = $data->tipo;
            $fechaSiembra = $data->fechaSiembra;
            $statement->bind_param('ssss', $userId, $nombre, $tipo, $fechaSiembra);
            $statement->execute();
            $id = $statement->insert_id;
            $statement->close();

            if ($data->associatedCropCodes !== []) {
                $associatedStatement = $connection->prepare(
                    'INSERT INTO cultivos_asociados (id_cultivo, codigo, nombre) VALUES (?, ?, ?)'
                );
                $options = AssociatedCropCatalog::options();
                foreach ($data->associatedCropCodes as $code) {
                    $name = $options[$code];
                    $associatedStatement->bind_param('iss', $id, $code, $name);
                    $associatedStatement->execute();
                }
                $associatedStatement->close();
            }

            $connection->commit();
            return new Cultivo(
                $id,
                $data->userId,
                $data->tipo,
                $data->fechaSiembra,
                associatedCropCodes: $data->associatedCropCodes,
                nombre: $data->nombre
            );
        } catch (Throwable $exception) {
            if (isset($connection)) {
                $connection->rollback();
            }
            throw new DatabaseException('No se pudo registrar el cultivo.', $exception);
        }
    }

    public function nameExistsForUser(string $userId, string $name): bool
    {
        try {
            $statement = $this->statement(
                'SELECT COUNT(*) AS total
                   FROM cultivos
                  WHERE id_usuario = ? AND LOWER(TRIM(nombre)) = LOWER(TRIM(?))',
                'ss',
                [$userId, $name]
            );
            $row = $statement->get_result()->fetch_assoc();
            $statement->close();
            return (int) ($row['total'] ?? 0) > 0;
        } catch (Throwable $exception) {
            throw new DatabaseException(previous: $exception);
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
