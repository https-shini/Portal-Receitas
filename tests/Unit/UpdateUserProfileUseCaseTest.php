<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Application\UseCase\UpdateUserProfileUseCase;
use App\Domain\Exception\ValidationException;
use App\Tests\Support\InMemoryUserRepository;
use PHPUnit\Framework\TestCase;

class UpdateUserProfileUseCaseTest extends TestCase
{
    private InMemoryUserRepository $repository;

    private UpdateUserProfileUseCase $useCase;

    private int $userId;

    protected function setUp(): void
    {
        $this->repository = new InMemoryUserRepository();
        $this->userId = $this->repository->seed('Demo', 'demo@example.com', password_hash('123456', PASSWORD_DEFAULT), 2);
        $this->useCase = new UpdateUserProfileUseCase($this->repository);
    }

    public function testUpdatesNameEmailAndPasswordWithHash(): void
    {
        $updated = $this->useCase->execute($this->userId, 'Novo Nome', 'novo@example.com', 'novaSenha!');

        $this->assertTrue($updated);

        $user = $this->repository->findByEmail('novo@example.com');
        $this->assertNotNull($user);
        $this->assertSame('Novo Nome', $user['nomeUsuario']);
        $this->assertNotSame('novaSenha!', $user['senhaUsuario']);
        $this->assertTrue(password_verify('novaSenha!', $user['senhaUsuario']));
    }

    public function testKeepsPasswordWhenNewPasswordIsBlank(): void
    {
        $this->useCase->execute($this->userId, 'Novo Nome', null, '');

        $user = $this->repository->findByEmail('demo@example.com');
        $this->assertNotNull($user);
        $this->assertTrue(password_verify('123456', $user['senhaUsuario']));
    }

    public function testRejectsInvalidUserId(): void
    {
        $this->expectException(ValidationException::class);
        $this->useCase->execute(0, 'Nome', null, null);
    }
}
