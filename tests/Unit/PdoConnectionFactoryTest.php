<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Infrastructure\Database\PdoConnectionFactory;
use PDO;
use PDOException;
use PHPUnit\Framework\TestCase;

class PdoConnectionFactoryTest extends TestCase
{
    public function testThrowsPdoExceptionForUnreachableHost(): void
    {
        $factory = new PdoConnectionFactory('127.0.0.1', 'banco_inexistente', 'user', 'pass');

        $this->expectException(PDOException::class);
        $factory->create();
    }

    public function testConnectsAndReusesConnectionWhenDatabaseIsAvailable(): void
    {
        $host = getenv('TEST_DB_HOST');

        if ($host === false || $host === '') {
            $this->markTestSkipped('Defina TEST_DB_HOST (e opcionalmente TEST_DB_NAME/USER/PASS) para o teste de integração.');
        }

        $factory = new PdoConnectionFactory(
            $host,
            getenv('TEST_DB_NAME') ?: 'tcc_receitas',
            getenv('TEST_DB_USER') ?: 'root',
            getenv('TEST_DB_PASS') !== false ? (string) getenv('TEST_DB_PASS') : '',
            getenv('TEST_DB_PORT') ?: '3306'
        );

        $connection = $factory->create();

        $this->assertInstanceOf(PDO::class, $connection);
        $this->assertSame(1, (int) $connection->query('SELECT 1')->fetchColumn());
        $this->assertSame($connection, $factory->create(), 'A factory deve reutilizar a mesma conexão (singleton).');
    }
}
