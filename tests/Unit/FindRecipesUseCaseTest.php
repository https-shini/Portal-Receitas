<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Application\Query\RecipeQuery;
use App\Application\UseCase\FindRecipesUseCase;
use App\Tests\Support\InMemoryRecipeRepository;
use PHPUnit\Framework\TestCase;

/**
 * Catálogo paginado e facetado: metadados de paginação corretos e filtro por
 * categoria aplicado.
 */
class FindRecipesUseCaseTest extends TestCase
{
    private FindRecipesUseCase $useCase;

    protected function setUp(): void
    {
        $repo = new InMemoryRecipeRepository();
        for ($i = 1; $i <= 15; $i++) {
            $repo->seed($i, "Receita {$i}", $i <= 10 ? 2 : 5, $i <= 10 ? 'Massas' : 'Doces');
        }
        $this->useCase = new FindRecipesUseCase($repo);
    }

    public function testPaginationMetadata(): void
    {
        $result = $this->useCase->execute(new RecipeQuery(perPage: 12, page: 1));

        $this->assertSame(15, $result['total']);
        $this->assertSame(2, $result['totalPages']);
        $this->assertCount(12, $result['cards']);
        $this->assertTrue($result['hasMore']);
    }

    public function testSecondPageHasRemainder(): void
    {
        $result = $this->useCase->execute(new RecipeQuery(perPage: 12, page: 2));

        $this->assertCount(3, $result['cards']);
        $this->assertFalse($result['hasMore']);
    }

    public function testCategoryFilterReducesTotal(): void
    {
        $result = $this->useCase->execute(new RecipeQuery(categoryIds: [5]));

        $this->assertSame(5, $result['total']);
        $this->assertSame('Doces', $result['cards'][0]['category']);
    }
}
