<?php

declare(strict_types=1);

namespace App\Domain\Repository;

/**
 * Contrato de persistência das receitas favoritas de um usuário autenticado.
 * A aplicação escreve apenas nesta tabela (menor privilégio).
 */
interface FavoriteRepositoryInterface
{
    /** Marca a receita como favorita (idempotente — repetir não duplica). */
    public function add(int $userId, int $recipeId): void;

    /** Remove a receita dos favoritos (idempotente). */
    public function remove(int $userId, int $recipeId): void;

    /** A receita está entre as favoritas do usuário? */
    public function exists(int $userId, int $recipeId): bool;

    /**
     * Favoritas do usuário no formato de resumo (card), mais recentes primeiro.
     *
     * @return list<array<string, mixed>>
     */
    public function listByUser(int $userId): array;
}
