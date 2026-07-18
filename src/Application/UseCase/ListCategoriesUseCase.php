<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Domain\Repository\CategoryRepositoryInterface;

/**
 * Caso de uso: listar as categorias para os filtros do catálogo, no
 * vocabulário da view (id/name/icon/total). Fonte data-driven — o conjunto
 * vem do banco, não de um mapa fixo em código.
 */
class ListCategoriesUseCase
{
    public function __construct(private readonly CategoryRepositoryInterface $categoryRepository)
    {
    }

    /**
     * @param  bool $onlyWithRecipes Descarta categorias sem nenhuma receita.
     * @return list<array{id: int, name: string, icon: string|null, total: int}>
     */
    public function execute(bool $onlyWithRecipes = false): array
    {
        $categories = [];
        foreach ($this->categoryRepository->findAllWithCounts() as $category) {
            if ($onlyWithRecipes && $category['total'] === 0) {
                continue;
            }
            $categories[] = [
                'id' => $category['idCategoria'],
                'name' => $category['nomeCategoria'],
                'icon' => $category['icone'],
                'total' => $category['total'],
            ];
        }

        return $categories;
    }
}
