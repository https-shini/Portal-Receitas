<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Mapper\RecipeViewMapper;
use App\Domain\Repository\RecipeRepositoryInterface;

/**
 * Caso de uso da página individual de receita: carrega o detalhe por id e
 * as receitas relacionadas (mesma categoria). Retorna null quando a receita
 * não existe, para o entrypoint responder 404.
 */
class ShowRecipeUseCase
{
    private const RELATED_LIMIT = 4;

    public function __construct(private readonly RecipeRepositoryInterface $recipeRepository)
    {
    }

    /**
     * @return array{recipe: array<string, mixed>, related: list<array<string, mixed>>}|null
     */
    public function execute(int $id): ?array
    {
        $row = $this->recipeRepository->findById($id);
        if ($row === null) {
            return null;
        }

        $categoryId = (int) ($row['idcategoriaFK'] ?? 0);
        $related = [];
        if ($categoryId > 0) {
            $related = array_map(
                RecipeViewMapper::summary(...),
                $this->recipeRepository->findRelated($categoryId, $id, self::RELATED_LIMIT),
            );
        }

        return [
            'recipe' => RecipeViewMapper::detail($row),
            'related' => $related,
        ];
    }
}
