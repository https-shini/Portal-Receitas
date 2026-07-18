<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Domain\Repository\FavoriteRepositoryInterface;

/**
 * Caso de uso: alternar o estado de favorito de uma receita para um usuário.
 * Retorna o novo estado (true = agora favoritada).
 */
class ToggleFavoriteUseCase
{
    public function __construct(private readonly FavoriteRepositoryInterface $favoriteRepository)
    {
    }

    public function execute(int $userId, int $recipeId): bool
    {
        if ($this->favoriteRepository->exists($userId, $recipeId)) {
            $this->favoriteRepository->remove($userId, $recipeId);

            return false;
        }

        $this->favoriteRepository->add($userId, $recipeId);

        return true;
    }
}
