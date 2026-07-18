<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Application\UseCase\FindRecipesUseCase;
use App\Application\UseCase\ListCategoriesUseCase;
use App\Domain\Exception\ValidationException;
use App\Presentation\Http\SessionManager;

/**
 * Controller da home: catálogo de receitas com busca e filtro.
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
        private readonly SessionManager $sessionManager,
    ) {
    }

    /**
     * Monta os dados da home a partir da query string.
     *
     * Parâmetros reconhecidos: 'pesquisa' (termo), 'categoriaReceita' (id) e
     * 'buscar' (presente somente quando o formulário foi submetido).
     *
     * Fluxo: sem 'buscar', lista o catálogo completo — os filtros só valem em
     * busca explícita. Busca vazia gera mensagem de orientação; busca sem
     * resultados gera a mensagem legada "Não foi possível encontrar receitas".
     *
     * @return array{cards: list<array<string, mixed>>, details: list<array<string, mixed>>, categories: list<array<string, mixed>>, errorMessage: string|null}
     */
    public function list(array $query): array
    {
        $this->sessionManager->start();

        $search = isset($query['pesquisa']) ? trim((string) $query['pesquisa']) : null;
        $categoryId = isset($query['categoriaReceita']) && $query['categoriaReceita'] !== '' ? (int) $query['categoriaReceita'] : null;
        $errorMessage = null;

        if (isset($query['buscar'])) {
            try {
                $this->findRecipesUseCase->validateSearchRequest($search, $categoryId);
            } catch (ValidationException $exception) {
                $errorMessage = $exception->getMessage();
            }
        }

        $recipes = $this->findRecipesUseCase->execute(
            isset($query['buscar']) ? $search : null,
            isset($query['buscar']) ? $categoryId : null,
        );

        if (isset($query['buscar']) && $errorMessage === null && $recipes['cards'] === []) {
            $errorMessage = 'Não foi possível encontrar receitas :(';
        }

        return [
            'cards' => $recipes['cards'],
            'details' => $recipes['details'],
            'categories' => $this->listCategoriesUseCase->execute(),
            'errorMessage' => $errorMessage,
        ];
    }
}
