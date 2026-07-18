<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Domain\Repository\FavoriteRepositoryInterface;

/**
 * Fake em memória do repositório de favoritos. add() é idempotente (conjunto),
 * espelhando o INSERT IGNORE contra a PK composta da implementação PDO.
 */
class InMemoryFavoriteRepository implements FavoriteRepositoryInterface
{
    /** @var array<string, array{idUsuario:int, idReceita:int}> */
    private array $favoritos = [];

    /** @var array<int, array<string, mixed>> Linhas de receita para listByUser. */
    private array $recipeRows = [];

    private function key(int $userId, int $recipeId): string
    {
        return $userId . ':' . $recipeId;
    }

    public function withRecipeRow(int $recipeId, array $row): void
    {
        $this->recipeRows[$recipeId] = $row;
    }

    public function add(int $userId, int $recipeId): void
    {
        $this->favoritos[$this->key($userId, $recipeId)] = ['idUsuario' => $userId, 'idReceita' => $recipeId];
    }

    public function remove(int $userId, int $recipeId): void
    {
        unset($this->favoritos[$this->key($userId, $recipeId)]);
    }

    public function exists(int $userId, int $recipeId): bool
    {
        return isset($this->favoritos[$this->key($userId, $recipeId)]);
    }

    public function listByUser(int $userId): array
    {
        $out = [];
        foreach ($this->favoritos as $fav) {
            if ($fav['idUsuario'] === $userId && isset($this->recipeRows[$fav['idReceita']])) {
                $out[] = $this->recipeRows[$fav['idReceita']];
            }
        }

        return $out;
    }
}
