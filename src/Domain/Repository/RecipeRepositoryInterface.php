<?php

declare(strict_types=1);

namespace App\Domain\Repository;

interface RecipeRepositoryInterface
{
    public function findSummaries(?string $search, ?int $categoryId): array;

    public function findDetails(?string $search, ?int $categoryId): array;
}
