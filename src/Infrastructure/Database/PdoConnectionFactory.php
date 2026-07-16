<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

use PDO;

/**
 * Fábrica da conexão PDO com o MySQL/MariaDB.
 *
 * Uma única conexão por request (lazy singleton): criada no primeiro
 * create() e reutilizada nas chamadas seguintes — modelo adequado ao ciclo
 * share-nothing do PHP.
 *
 * Decisões de configuração:
 *  - ERRMODE_EXCEPTION: falhas viram PDOException (capturadas nos entrypoints
 *    e convertidas em resposta 503 amigável);
 *  - EMULATE_PREPARES=false: prepared statements nativos do servidor —
 *    parâmetros nunca são interpolados na string SQL;
 *  - charset utf8mb4 no DSN, alinhado ao schema.
 */
class PdoConnectionFactory
{
    private ?PDO $connection = null;

    /**
     * @param string|null $sslCa     Caminho do certificado CA (PEM) quando o
     *                               provedor exige TLS (ex.: MySQL gerenciado);
     *                               null desliga o TLS.
     * @param bool        $sslVerify Verificação do certificado do servidor;
     *                               desligar apenas para certificado
     *                               autoassinado.
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

    /**
     * Devolve a conexão ativa, criando-a na primeira chamada.
     *
     * @throws \PDOException Quando o servidor está inacessível ou as
     *                       credenciais são inválidas.
     */
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
