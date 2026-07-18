<?php

declare(strict_types=1);

namespace App\Domain\Repository;

/**
 * Contrato de consulta ao catálogo de receitas (somente leitura — o portal
 * não possui escrita de receitas pela aplicação).
 *
 * Os métodos de listagem aceitam os mesmos filtros opcionais e combináveis:
 * termo de busca por ingrediente e/ou id de categoria; null desliga o filtro.
 * Todas as projeções trazem o nome da categoria por JOIN (`nomeCategoria`) —
 * o rótulo não é mais fixado em código.
 */
interface RecipeRepositoryInterface
{
    /**
     * Dados resumidos para os cards (id, nome, tempo, categoria, imagem).
     *
     * @return list<array<string, mixed>>
     */
    public function findSummaries(?string $search, ?int $categoryId): array;

    /**
     * Dados completos (vídeo, 15 ingredientes, modo de preparo, porções,
     * calorias) — usado hoje pela projeção de detalhe do catálogo.
     *
     * @return list<array<string, mixed>>
     */
    public function findDetails(?string $search, ?int $categoryId): array;

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
}
