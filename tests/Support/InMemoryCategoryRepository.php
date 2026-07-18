<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Domain\Repository\CategoryRepositoryInterface;

/** Fake em memória do repositório de categorias. */
class InMemoryCategoryRepository implements CategoryRepositoryInterface
{
    /** @var list<array{idCategoria:int,nomeCategoria:string,icone:?string,total:int}> */
    private array $categories = [];

    public function add(int $id, string $name, ?string $icon, int $total): void
    {
        $this->categories[] = [
            'idCategoria' => $id,
            'nomeCategoria' => $name,
            'icone' => $icon,
            'total' => $total,
        ];
    }

    public function findAllWithCounts(): array
    {
        return $this->categories;
    }
}
