<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Mapper\RecipeViewMapper;
use App\Domain\Repository\FavoriteRepositoryInterface;

/**
 * Caso de uso: listar as receitas favoritas de um usuário no formato de card,
 * para a página /favoritas (reaproveita o partial de card do catálogo).
 */
class ListFavoritesUseCase
{
    public function __construct(private readonly FavoriteRepositoryInterface $favoriteRepository)
    {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function execute(int $userId): array
    {
        return array_map(
            RecipeViewMapper::summary(...),
            $this->favoriteRepository->listByUser($userId),
        );
    }
}
