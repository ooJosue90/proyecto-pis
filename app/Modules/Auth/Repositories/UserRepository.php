<?php

declare(strict_types=1);

namespace App\Modules\Auth\Repositories;

use App\Core\Database;
use App\Modules\Auth\Models\User;
use App\Shared\Exceptions\DatabaseException;
use App\Shared\Interfaces\UserRepositoryInterface;
use Throwable;

final class UserRepository implements UserRepositoryInterface
{
    public function __construct(private readonly Database $database)
    {
    }

    public function findByEmail(string $email): ?User
    {
        try {
            $statement = $this->database->connection()->prepare(
                'SELECT id_usuario, nombre, email, contrasena, rol FROM usuarios WHERE email = ? LIMIT 1'
            );
            $statement->bind_param('s', $email);
            $statement->execute();
            $row = $statement->get_result()->fetch_assoc();
            $statement->close();
            return is_array($row) ? User::fromRow($row) : null;
        } catch (Throwable $exception) {
            throw new DatabaseException(previous: $exception);
        }
    }

    public function updatePasswordHash(string $userId, string $passwordHash): void
    {
        try {
            $statement = $this->database->connection()->prepare(
                'UPDATE usuarios SET contrasena = ? WHERE id_usuario = ?'
            );
            $statement->bind_param('ss', $passwordHash, $userId);
            $statement->execute();
            $statement->close();
        } catch (Throwable $exception) {
            throw new DatabaseException('No se pudo actualizar la contraseña.', $exception);
        }
    }

    public function createPasswordResetNotification(string $name, string $email): void
    {
        try {
            $message = "El usuario {$name} ({$email}) ha solicitado restablecer su contraseña.";
            $statement = $this->database->connection()->prepare(
                'INSERT INTO notificaciones (mensaje) VALUES (?)'
            );
            $statement->bind_param('s', $message);
            $statement->execute();
            $statement->close();
        } catch (Throwable $exception) {
            throw new DatabaseException('No se pudo registrar la solicitud de recuperación.', $exception);
        }
    }

    public function findAdministratorEmail(): ?string
    {
        try {
            $statement = $this->database->connection()->prepare(
                "SELECT email FROM usuarios WHERE rol = 'Administrador' ORDER BY id_usuario LIMIT 1"
            );
            $statement->execute();
            $row = $statement->get_result()->fetch_assoc();
            $statement->close();
            return is_array($row) ? (string) $row['email'] : null;
        } catch (Throwable $exception) {
            throw new DatabaseException(previous: $exception);
        }
    }
}
