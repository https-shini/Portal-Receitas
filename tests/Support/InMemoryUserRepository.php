<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Domain\Repository\UserRepositoryInterface;

/**
 * Fake em memória do repositório de usuários para os testes unitários.
 *
 * Reproduz o contrato observável da implementação PDO (mesmas linhas
 * associativas, mesma semântica de atualização parcial), permitindo testar
 * os casos de uso sem banco — e comprovando a substituibilidade (LSP) da
 * abstração do Domain.
 */
class InMemoryUserRepository implements UserRepositoryInterface
{
    /** @var array<int, array<string, mixed>> */
    private array $users = [];

    private int $nextId = 1;

    /**
     * Pré-carrega um usuário (arrange dos testes) e devolve o id gerado —
     * equivalente ao AUTO_INCREMENT do banco.
     */
    public function seed(string $name, string $email, string $passwordHash, int $favoriteCategoryId): int
    {
        $id = $this->nextId++;
        $this->users[$id] = [
            'idUsuario' => $id,
            'nomeUsuario' => $name,
            'emailUsuario' => $email,
            'senhaUsuario' => $passwordHash,
            'idCategoriaFK' => $favoriteCategoryId,
        ];

        return $id;
    }

    public function findByEmail(string $email): ?array
    {
        foreach ($this->users as $user) {
            if ($user['emailUsuario'] === $email) {
                return $user;
            }
        }

        return null;
    }

    public function create(string $name, string $email, string $passwordHash, int $favoriteCategoryId): bool
    {
        $this->seed($name, $email, $passwordHash, $favoriteCategoryId);

        return true;
    }

    public function updateProfile(int $userId, ?string $name, ?string $email, ?string $passwordHash): bool
    {
        if (!isset($this->users[$userId])) {
            return false;
        }

        if ($name !== null && $name !== '') {
            $this->users[$userId]['nomeUsuario'] = $name;
        }

        if ($email !== null && $email !== '') {
            $this->users[$userId]['emailUsuario'] = $email;
        }

        if ($passwordHash !== null && $passwordHash !== '') {
            $this->users[$userId]['senhaUsuario'] = $passwordHash;
        }

        return true;
    }
}
