<?php

declare(strict_types=1);

namespace App\Domain\Repository;

/**
 * Contrato de consulta ao catálogo de receitas (somente leitura — o portal
 * não possui escrita de receitas pela aplicação).
 *
 * Os dois métodos aceitam os mesmos filtros opcionais e combináveis: termo de
 * busca por ingrediente e/ou id de categoria; null desliga o filtro.
 */
interface RecipeRepositoryInterface
{
    /**
     * Dados resumidos para os cards da home (id, nome, tempo, categoria, imagem).
     *
     * @return list<array<string, mixed>>
     */
    public function findSummaries(?string $search, ?int $categoryId): array;

    /**
     * Dados completos para o detalhe da receita (vídeo, 15 ingredientes,
     * modo de preparo, porções, calorias).
     *
     * @return list<array<string, mixed>>
     */
    public function findDetails(?string $search, ?int $categoryId): array;
}
