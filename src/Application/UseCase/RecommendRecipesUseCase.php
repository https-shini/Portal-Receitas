<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Mapper\RecipeViewMapper;
use App\Domain\Repository\FavoriteRepositoryInterface;
use App\Domain\Repository\RecipeRepositoryInterface;

/**
 * Recomendações da home. Para o usuário autenticado com favoritos, sugere as
 * mais bem avaliadas nas categorias que ele curte (excluindo as que já
 * favoritou) — "Para você". Sem esse contexto, cai para as "Mais bem
 * avaliadas" do acervo.
 */
class RecommendRecipesUseCase
{
    public function __construct(
        private readonly RecipeRepositoryInterface $recipeRepository,
        private readonly FavoriteRepositoryInterface $favoriteRepository,
    ) {
    }

    /**
     * @return array{title: string, cards: list<array<string, mixed>>}
     */
    public function execute(?int $userId, int $limit = 4): array
    {
        $excludeIds = [];
        $categoryIds = [];

        if ($userId !== null && $userId > 0) {
            foreach ($this->favoriteRepository->listByUser($userId) as $fav) {
                $excludeIds[] = (int) $fav['idReceita'];
                $categoria = (int) ($fav['idcategoriaFK'] ?? 0);
                if ($categoria > 0 && !in_array($categoria, $categoryIds, true)) {
                    $categoryIds[] = $categoria;
                }
            }
        }

        $personalized = $categoryIds !== [];
        $rows = $this->recipeRepository->recommend(
            $personalized ? $categoryIds : null,
            $excludeIds,
            $limit,
        );

        return [
            'title' => $personalized ? 'Para você' : 'Mais bem avaliadas',
            'cards' => array_map(RecipeViewMapper::summary(...), $rows),
        ];
    }
}
