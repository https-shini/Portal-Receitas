<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Application\Query\RecipeQuery;
use App\Application\UseCase\FindRecipesUseCase;
use App\Application\UseCase\ListCategoriesUseCase;
use App\Application\UseCase\ShowRecipeUseCase;
use App\Presentation\Http\SessionManager;

/**
 * Controller do catálogo de receitas: listagem (home) com busca e filtro e
 * detalhe da receita (página dedicada).
 *
 * A home é pública (paridade com o site original); a sessão é iniciada
 * apenas para preservar o estado de login na navegação. As categorias dos
 * filtros vêm do banco (data-driven) via ListCategoriesUseCase.
 */
class RecipeController
{
    public function __construct(
        private readonly FindRecipesUseCase $findRecipesUseCase,
        private readonly ListCategoriesUseCase $listCategoriesUseCase,
        private readonly ShowRecipeUseCase $showRecipeUseCase,
        private readonly SessionManager $sessionManager,
    ) {
    }

    /**
     * Detalhe de uma receita para a página dedicada (/receita/{id}/{slug}).
     *
     * @return array{recipe: array<string, mixed>, related: list<array<string, mixed>>}|null
     *         null quando a receita não existe (o entrypoint responde 404).
     */
    public function show(int $id): ?array
    {
        $this->sessionManager->start();

        return $this->showRecipeUseCase->execute($id);
    }

    /**
     * Monta os dados do catálogo a partir da query string (busca facetada).
     *
     * Parâmetros reconhecidos: 'pesquisa' (termo), 'categoriaReceita' (id ou
     * lista de ids), 'ordenar' (relevancia|nome|tempo) e 'pagina'. Sem
     * filtros, lista o catálogo completo paginado — comportamento de
     * marketplace, sem submit explícito.
     *
     * @return array{
     *     cards: list<array<string, mixed>>,
     *     categories: list<array<string, mixed>>,
     *     filters: array{search: string|null, categoryIds: list<int>, difficulties: list<string>, sort: string},
     *     pagination: array{page: int, perPage: int, total: int, totalPages: int, hasMore: bool},
     *     errorMessage: string|null
     * }
     */
    public function list(array $query): array
    {
        $this->sessionManager->start();

        $recipeQuery = RecipeQuery::fromArray($query);
        $result = $this->findRecipesUseCase->execute($recipeQuery);

        $errorMessage = null;
        if ($result['cards'] === []) {
            $errorMessage = $recipeQuery->hasFilters()
                ? 'Nenhuma receita encontrada com esses filtros.'
                : 'Não foi possível encontrar receitas :(';
        }

        return [
            'cards' => $result['cards'],
            'categories' => $this->listCategoriesUseCase->execute(),
            'filters' => [
                'search' => $recipeQuery->search,
                'categoryIds' => $recipeQuery->categoryIds,
                'difficulties' => $recipeQuery->difficulties,
                'sort' => $recipeQuery->sort,
            ],
            'pagination' => [
                'page' => $result['page'],
                'perPage' => $result['perPage'],
                'total' => $result['total'],
                'totalPages' => $result['totalPages'],
                'hasMore' => $result['hasMore'],
            ],
            'errorMessage' => $errorMessage,
        ];
    }
}
