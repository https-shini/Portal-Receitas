<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Mapper\RecipeViewMapper;
use App\Domain\Exception\ValidationException;
use App\Domain\Repository\RecipeRepositoryInterface;

/**
 * Caso de uso: montar o catálogo de receitas da home, com ou sem filtros.
 *
 * Produz duas projeções da mesma consulta:
 *  - 'cards'   → resumo exibido na grade de resultados;
 *  - 'details' → detalhe completo, com ingredientes e preparo normalizados.
 *
 * Os rótulos de categoria vêm do banco (JOIN em RecipeRepository) — não há
 * mais mapa fixo de categorias aqui.
 */
class FindRecipesUseCase
{
    public function __construct(private readonly RecipeRepositoryInterface $recipeRepository)
    {
    }

    /**
     * Busca receitas aplicando os filtros informados (null = sem filtro).
     *
     * @return array{cards: list<array<string, mixed>>, details: list<array<string, mixed>>}
     */
    public function execute(?string $search, ?int $categoryId): array
    {
        $search = $search !== null ? trim($search) : null;
        if ($search === '') {
            $search = null;
        }

        if ($categoryId !== null && $categoryId < 1) {
            $categoryId = null;
        }

        $summaries = $this->recipeRepository->findSummaries($search, $categoryId);
        $details = $this->recipeRepository->findDetails($search, $categoryId);

        return [
            'cards' => array_map(RecipeViewMapper::summary(...), $summaries),
            'details' => array_map(RecipeViewMapper::detail(...), $details),
        ];
    }

    /**
     * Regra de negócio da busca explícita: o usuário precisa informar um
     * termo OU selecionar uma categoria — submissão vazia é orientada, não
     * silenciosamente ignorada.
     *
     * @throws ValidationException Mensagem de orientação exibida na home.
     */
    public function validateSearchRequest(?string $search, ?int $categoryId): void
    {
        if (($search === null || trim($search) === '') && $categoryId === null) {
            throw new ValidationException('Tente escrever algo na barra de pesquisa ou selecionar uma categoria');
        }
    }
}
