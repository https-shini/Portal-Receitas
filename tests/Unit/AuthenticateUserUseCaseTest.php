<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Application\UseCase\AuthenticateUserUseCase;
use App\Domain\Exception\AuthenticationException;
use App\Domain\Exception\ValidationException;
use App\Tests\Support\InMemoryUserRepository;
use PHPUnit\Framework\TestCase;

/**
 * Regras de autenticação: verificação bcrypt, erro idêntico para usuário
 * inexistente e senha errada (anti-enumeração) e validação de entrada.
 */
class AuthenticateUserUseCaseTest extends TestCase
{
    private InMemoryUserRepository $repository;

    private AuthenticateUserUseCase $useCase;

    protected function setUp(): void
    {
        $this->repository = new InMemoryUserRepository();
        $this->repository->seed('Demo', 'demo@example.com', password_hash('123456', PASSWORD_DEFAULT), 2);
        $this->useCase = new AuthenticateUserUseCase($this->repository);
    }

    public function testAuthenticatesWithCorrectCredentials(): void
    {
        $user = $this->useCase->execute('demo@example.com', '123456');

        $this->assertSame('Demo', $user['nomeUsuario']);
        $this->assertSame('demo@example.com', $user['emailUsuario']);
    }

    /**
     * Compatibilidade com o seed oficial: o hash abaixo é o mesmo gravado em
     * DB_Receitas.sql para kk.123@gmail.com (senha em claro: 123456) — se o
     * seed mudar de formato, este teste acusa.
     */
    public function testAuthenticatesSeedDemoUserHash(): void
    {
        $this->repository->seed(
            'Nome descente',
            'kk.123@gmail.com',
            '$2y$10$bFuehjBZFt7sbgDjS4dDU.VLMmqrNH/D0Y5qG3uxYYeXF6p4eXUjW',
            2
        );

        $user = $this->useCase->execute('kk.123@gmail.com', '123456');

        $this->assertSame('kk.123@gmail.com', $user['emailUsuario']);
    }

    public function testRejectsWrongPassword(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->useCase->execute('demo@example.com', 'senha-errada');
    }

    public function testRejectsUnknownEmail(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->useCase->execute('ninguem@example.com', '123456');
    }

    public function testRejectsEmptyCredentials(): void
    {
        $this->expectException(ValidationException::class);
        $this->useCase->execute('', '');
    }
}
