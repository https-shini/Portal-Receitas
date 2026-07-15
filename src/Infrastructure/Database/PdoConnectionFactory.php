<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

use PDO;

class PdoConnectionFactory
{
    private ?PDO $connection = null;

    /**
     * @param string|null $sslCa      Caminho para o certificado CA (PEM) quando o provedor
     *                                de MySQL exige TLS (ex.: Aiven). Null = sem TLS.
     * @param bool        $sslVerify  Verificação do certificado do servidor (desligar apenas
     *                                para servidores com certificado autoassinado).
     */
    public function __construct(
        private readonly string $host,
        private readonly string $database,
        private readonly string $username,
        private readonly string $password,
        private readonly string $port = '3306',
        private readonly ?string $sslCa = null,
        private readonly bool $sslVerify = true,
    ) {
    }

    public function create(): PDO
    {
        if ($this->connection instanceof PDO) {
            return $this->connection;
        }

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        if ($this->sslCa !== null && $this->sslCa !== '') {
            $options[PDO::MYSQL_ATTR_SSL_CA] = $this->sslCa;
            $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = $this->sslVerify;
        }

        $this->connection = new PDO(
            sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $this->host, $this->port, $this->database),
            $this->username,
            $this->password,
            $options
        );

        return $this->connection;
    }
}
