<?php

declare(strict_types=1);

namespace App\Modules\Plagas\Repositories;

use App\Core\Database;
use App\Modules\Plagas\DTOs\CreatePlagaData;
use App\Modules\Plagas\Models\Plaga;
use App\Shared\Exceptions\DatabaseException;
use App\Shared\Interfaces\PlagaRepositoryInterface;
use Throwable;

final class PlagaRepository implements PlagaRepositoryInterface
{
    private const SELECT_BASE =
        'SELECT p.id_plaga, p.id_lote, p.id_usuario, p.nombre, p.fecha,
                l.ubicacion, u.nombre AS agricultor
         FROM plagas p
         INNER JOIN lotes l ON l.id_lote = p.id_lote
         INNER JOIN cultivos c ON c.id_cultivo = l.id_cultivo
         INNER JOIN usuarios u ON u.id_usuario = c.id_usuario';

    public function __construct(private readonly Database $database)
    {
    }

    public function findAll(): array
    {
        return $this->fetch(self::SELECT_BASE . ' ORDER BY p.fecha DESC');
    }

    public function findByUser(string $userId): array
    {
        return $this->fetch(self::SELECT_BASE . ' WHERE c.id_usuario = ? ORDER BY p.fecha DESC', 's', [$userId]);
    }

    public function loteBelongsToUser(int $loteId, string $userId): bool
    {
        try {
            $statement = $this->database->connection()->prepare(
                'SELECT COUNT(*) AS total FROM lotes l
                 INNER JOIN cultivos c ON c.id_cultivo = l.id_cultivo
                 WHERE l.id_lote = ? AND c.id_usuario = ?'
            );
            $statement->bind_param('is', $loteId, $userId);
            $statement->execute();
            $row = $statement->get_result()->fetch_assoc();
            $statement->close();
            return (int) ($row['total'] ?? 0) > 0;
        } catch (Throwable $exception) {
            throw new DatabaseException(previous: $exception);
        }
    }

    public function create(CreatePlagaData $data): Plaga
    {
        try {
            $statement = $this->database->connection()->prepare(
                'INSERT INTO plagas (id_lote, id_usuario, nombre) VALUES (?, ?, ?)'
            );
            $loteId = $data->loteId;
            $userId = $data->userId;
            $nombre = $data->nombre;
            $statement->bind_param('iss', $loteId, $userId, $nombre);
            $statement->execute();
            $id = $statement->insert_id;
            $statement->close();
            return new Plaga($id, $loteId, $userId, $nombre, date('Y-m-d H:i:s'));
        } catch (Throwable $exception) {
            throw new DatabaseException('No se pudo registrar la plaga.', $exception);
        }
    }

    /** @param list<mixed> $parameters @return list<Plaga> */
    private function fetch(string $sql, string $types = '', array $parameters = []): array
    {
        try {
            $statement = $this->database->connection()->prepare($sql);
            if ($types !== '') {
                $references = [];
                foreach ($parameters as $key => &$parameter) {
                    $references[$key] = &$parameter;
                }
                $statement->bind_param($types, ...$references);
            }
            $statement->execute();
            $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
            $statement->close();
            return array_map(static fn (array $row): Plaga => Plaga::fromRow($row), $rows);
        } catch (Throwable $exception) {
            throw new DatabaseException(previous: $exception);
        }
    }
}
