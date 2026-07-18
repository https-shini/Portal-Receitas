<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Application\UseCase\ListCategoriesUseCase;
use App\Tests\Support\InMemoryCategoryRepository;
use PHPUnit\Framework\TestCase;

/**
 * Categorias data-driven para os filtros: vocabulário da view e opção de
 * descartar categorias sem receitas.
 */
class ListCategoriesUseCaseTest extends TestCase
{
    private InMemoryCategoryRepository $repo;

    protected function setUp(): void
    {
        $this->repo = new InMemoryCategoryRepository();
        $this->repo->add(2, 'Massas', 'la-utensils', 7);
        $this->repo->add(13, 'Bebidas', 'la-cocktail', 0);
    }

    public function testMapsToViewVocabulary(): void
    {
        $categories = (new ListCategoriesUseCase($this->repo))->execute();

        $this->assertSame(2, $categories[0]['id']);
        $this->assertSame('Massas', $categories[0]['name']);
        $this->assertSame('la-utensils', $categories[0]['icon']);
        $this->assertSame(7, $categories[0]['total']);
    }

    public function testOnlyWithRecipesDropsEmptyCategories(): void
    {
        $categories = (new ListCategoriesUseCase($this->repo))->execute(true);

        $this->assertCount(1, $categories);
        $this->assertSame('Massas', $categories[0]['name']);
    }
}
