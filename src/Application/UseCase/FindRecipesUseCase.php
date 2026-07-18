<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Mapper\RecipeViewMapper;
use App\Application\Query\RecipeQuery;
use App\Domain\Repository\RecipeRepositoryInterface;

/**
 * Caso de uso: montar o catálogo de receitas (grade de cards) a partir de um
 * critério facetado e paginado. Devolve os cards da página pedida e os
 * metadados de paginação. Os rótulos de categoria vêm do banco (JOIN).
 */
class FindRecipesUseCase
{
    public function __construct(private readonly RecipeRepositoryInterface $recipeRepository)
    {
    }

    /**
     * @return array{
     *     cards: list<array<string, mixed>>,
     *     total: int,
     *     page: int,
     *     perPage: int,
     *     totalPages: int,
     *     hasMore: bool
     * }
     */
    public function execute(RecipeQuery $query): array
    {
        $total = $this->recipeRepository->count($query);
        $totalPages = $total > 0 ? (int) ceil($total / $query->perPage) : 1;
        $rows = $this->recipeRepository->search($query);

        return [
            'cards' => array_map(RecipeViewMapper::summary(...), $rows),
            'total' => $total,
            'page' => $query->page,
            'perPage' => $query->perPage,
            'totalPages' => $totalPages,
            'hasMore' => $query->page < $totalPages,
        ];
    }
}
