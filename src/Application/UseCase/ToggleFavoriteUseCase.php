<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Domain\Exception\NotFoundException;
use App\Domain\Repository\FavoriteRepositoryInterface;
use App\Domain\Repository\RecipeRepositoryInterface;

/**
 * Caso de uso: alternar o estado de favorito de uma receita para um usuário.
 * Retorna o novo estado (true = agora favoritada).
 */
class ToggleFavoriteUseCase
{
    public function __construct(
        private readonly FavoriteRepositoryInterface $favoriteRepository,
        private readonly RecipeRepositoryInterface $recipeRepository,
    ) {
    }

    /**
     * @throws NotFoundException Receita inexistente. Sem esta guarda o
     *                           INSERT IGNORE do repositório engoliria a
     *                           violação de chave estrangeira e o caso de uso
     *                           responderia "favoritada" sem ter gravado nada.
     */
    public function execute(int $userId, int $recipeId): bool
    {
        if (!$this->recipeRepository->exists($recipeId)) {
            throw new NotFoundException('Receita não encontrada.');
        }

        if ($this->favoriteRepository->exists($userId, $recipeId)) {
            $this->favoriteRepository->remove($userId, $recipeId);

            return false;
        }

        $this->favoriteRepository->add($userId, $recipeId);

        return true;
    }
}
