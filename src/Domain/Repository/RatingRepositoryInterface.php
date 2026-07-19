<?php

declare(strict_types=1);

namespace App\Domain\Repository;

/**
 * Contrato de persistência das avaliações (1–5) de receitas por usuário.
 * Um voto por par usuário/receita — revotar atualiza a nota existente.
 */
interface RatingRepositoryInterface
{
    /** Registra ou atualiza a nota do usuário para a receita (upsert). */
    public function rate(int $userId, int $recipeId, int $score): void;

    /** Remove a avaliação do usuário para a receita. */
    public function remove(int $userId, int $recipeId): void;

    /** Nota atual do usuário para a receita, ou null se ainda não avaliou. */
    public function userScore(int $userId, int $recipeId): ?int;

    /**
     * Agregado da receita: média e total de avaliações.
     *
     * @return array{average: float|null, count: int}
     */
    public function aggregate(int $recipeId): array;
}
