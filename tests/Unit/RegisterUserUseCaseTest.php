<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Application\UseCase\RegisterUserUseCase;
use App\Domain\Exception\ValidationException;
use App\Tests\Support\InMemoryUserRepository;
use PHPUnit\Framework\TestCase;

/**
 * Regras de negócio do cadastro: hash obrigatório, unicidade de e-mail,
 * formato de e-mail, categoria obrigatória e política de senha.
 */
class RegisterUserUseCaseTest extends TestCase
{
    private InMemoryUserRepository $repository;

    private RegisterUserUseCase $useCase;

    protected function setUp(): void
    {
        $this->repository = new InMemoryUserRepository();
        $this->useCase = new RegisterUserUseCase($this->repository);
    }

    /** Garantia central de segurança: a senha persiste como bcrypt, nunca em claro. */
    public function testRegistersUserWithBcryptCompatibleHash(): void
    {
        $created = $this->useCase->execute('Maria', 'maria@example.com', 'segredo123', 5);

        $this->assertTrue($created);

        $user = $this->repository->findByEmail('maria@example.com');
        $this->assertNotNull($user);
        $this->assertNotSame('segredo123', $user['senhaUsuario'], 'A senha nunca pode ser persistida em texto puro.');
        $this->assertTrue(password_verify('segredo123', $user['senhaUsuario']));
    }

    public function testRejectsDuplicateEmail(): void
    {
        $this->repository->seed('Já Existe', 'maria@example.com', password_hash('x', PASSWORD_DEFAULT), 1);

        $this->expectException(ValidationException::class);
        $this->useCase->execute('Maria', 'maria@example.com', 'segredo123', 5);
    }

    public function testRejectsInvalidEmailFormat(): void
    {
        $this->expectException(ValidationException::class);
        $this->useCase->execute('Maria', 'nao-e-email', 'segredo123', 5);
    }

    public function testRejectsMissingCategory(): void
    {
        $this->expectException(ValidationException::class);
        $this->useCase->execute('Maria', 'maria@example.com', 'segredo123', null);
    }

    public function testRejectsWeakPassword(): void
    {
        $this->expectException(ValidationException::class);
        $this->useCase->execute('Maria', 'maria@example.com', 'fraca', 5);
    }
}
