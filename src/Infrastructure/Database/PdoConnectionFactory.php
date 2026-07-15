<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

use PDO;

class PdoConnectionFactory
{
    private ?PDO $connection = null;

    public function __construct(
        private readonly string $host,
        private readonly string $database,
        private readonly string $username,
        private readonly string $password,
    ) {
    }

    public function create(): PDO
    {
        if ($this->connection instanceof PDO) {
            return $this->connection;
        }

        $this->connection = new PDO(
            sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $this->host, $this->database),
            $this->username,
            $this->password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );

        return $this->connection;
    }
}
