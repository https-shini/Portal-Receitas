<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Application\UseCase\DeleteUserAccountUseCase;
use App\Domain\Exception\ValidationException;
use App\Tests\Support\InMemoryUserRepository;
use PHPUnit\Framework\TestCase;

/**
 * Eliminação de conta por anonimização (LGPD art. 18, VI): após executar,
 * nome/e-mail/credencial originais deixam de existir e a conta não autentica.
 */
class DeleteUserAccountUseCaseTest extends TestCase
{
    private InMemoryUserRepository $repository;

    private DeleteUserAccountUseCase $useCase;

    private int $userId;

    protected function setUp(): void
    {
        $this->repository = new InMemoryUserRepository();
        $this->userId = $this->repository->seed('Titular', 'titular@example.com', password_hash('senha123', PASSWORD_DEFAULT), 3);
        $this->useCase = new DeleteUserAccountUseCase($this->repository);
    }

    public function testAnonymizesAllPersonalData(): void
    {
        $this->assertTrue($this->useCase->execute($this->userId));

        $this->assertNull($this->repository->findByEmail('titular@example.com'), 'O e-mail original não pode mais localizar a conta.');

        $anonimo = $this->repository->findByEmail(sprintf('anonimo-%d@anonimizado.invalid', $this->userId));
        $this->assertNotNull($anonimo);
        $this->assertSame('Usuário removido', $anonimo['nomeUsuario']);
        $this->assertFalse(password_verify('senha123', $anonimo['senhaUsuario']), 'A senha antiga não pode continuar autenticando.');
    }

    public function testRejectsInvalidUserId(): void
    {
        $this->expectException(ValidationException::class);
        $this->useCase->execute(0);
    }
}
