<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Application\Query\RecipeQuery;

/**
 * Contrato de consulta ao catálogo de receitas (somente leitura — o portal
 * não possui escrita de receitas pela aplicação).
 *
 * A listagem é facetada e paginada via RecipeQuery; todas as projeções trazem
 * o nome da categoria por JOIN (`nomeCategoria`) — o rótulo não é fixado em
 * código.
 */
interface RecipeRepositoryInterface
{
    /**
     * Página de resumos (cards) que satisfazem o critério, já ordenada e
     * limitada.
     *
     * @return list<array<string, mixed>>
     */
    public function search(RecipeQuery $query): array;

    /**
     * Total de receitas que satisfazem o critério (ignora paginação), para o
     * cálculo de páginas.
     */
    public function count(RecipeQuery $query): int;

    /**
     * Detalhe completo de uma única receita pelo id, para a página dedicada.
     *
     * @return array<string, mixed>|null Linha da receita ou null se inexistente.
     */
    public function findById(int $id): ?array;

    /**
     * Receitas relacionadas (mesma categoria), excluindo a própria, no formato
     * de resumo (card). Ordem aleatória, limitada a $limit.
     *
     * @return list<array<string, mixed>>
     */
    public function findRelated(int $categoryId, int $excludeId, int $limit): array;

    /**
     * Nomes dos arquivos de imagens adicionais da receita (galeria), ordenados.
     *
     * @return list<string>
     */
    public function findImages(int $recipeId): array;

    /**
     * Recomendações: melhores avaliadas, opcionalmente restritas a categorias
     * e excluindo ids indesejados (ex.: já favoritadas). Formato de resumo.
     *
     * @param  list<int>|null $categoryIds Restringe às categorias (null = todas).
     * @param  list<int>      $excludeIds  Ids a não recomendar.
     * @return list<array<string, mixed>>
     */
    public function recommend(?array $categoryIds, array $excludeIds, int $limit): array;
}
