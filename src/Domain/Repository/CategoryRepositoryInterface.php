<?php

declare(strict_types=1);

namespace App\Domain\Repository;

/**
 * Contrato de consulta às categorias (somente leitura). Fonte única de
 * verdade das categorias exibidas nos filtros — os rótulos e ícones vêm do
 * banco, permitindo adicionar categorias sem alterar o código da interface.
 */
interface CategoryRepositoryInterface
{
    /**
     * Todas as categorias com o total de receitas de cada uma, ordenadas por
     * quantidade (desc) e depois por nome.
     *
     * @return list<array{idCategoria: int, nomeCategoria: string, icone: string|null, total: int}>
     */
    public function findAllWithCounts(): array;
}
