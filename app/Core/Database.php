<?php

declare(strict_types=1);

namespace App\Core;

use App\Shared\Exceptions\DatabaseException;
use App\Shared\Interfaces\TransactionManagerInterface;
use mysqli;
use Throwable;

final class Database implements TransactionManagerInterface
{
    private ?mysqli $connection = null;

    /** @param array{host:string,username:string,password:string,name:string,port:int,charset:string} $config */
    public function __construct(private readonly array $config, ?mysqli $connection = null)
    {
        $this->connection = $connection;
    }

    public static function fromConnection(mysqli $connection): self
    {
        return new self([
            'host' => '', 'username' => '', 'password' => '',
            'name' => '', 'port' => 0, 'charset' => 'utf8mb4',
        ], $connection);
    }

    public function connection(): mysqli
    {
        if ($this->connection instanceof mysqli) {
            return $this->connection;
        }

        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        try {
            $this->connection = new mysqli(
                $this->config['host'],
                $this->config['username'],
                $this->config['password'],
                $this->config['name'],
                $this->config['port']
            );
            $this->connection->set_charset($this->config['charset']);
        } catch (Throwable $exception) {
            throw new DatabaseException(previous: $exception);
        }

        return $this->connection;
    }

    public function transaction(callable $operation): mixed
    {
        $connection = $this->connection();
        $connection->begin_transaction();

        try {
            $result = $operation($connection);
            $connection->commit();
            return $result;
        } catch (Throwable $exception) {
            $connection->rollback();
            throw $exception;
        }
    }
}
