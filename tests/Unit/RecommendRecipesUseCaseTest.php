<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Application\UseCase\RecommendRecipesUseCase;
use App\Tests\Support\InMemoryFavoriteRepository;
use App\Tests\Support\InMemoryRecipeRepository;
use PHPUnit\Framework\TestCase;

/**
 * Recomendações: anônimo recebe "Mais bem avaliadas"; quem tem favoritos
 * recebe "Para você" (mesma categoria, sem as já favoritadas).
 */
class RecommendRecipesUseCaseTest extends TestCase
{
    private InMemoryRecipeRepository $recipes;

    private InMemoryFavoriteRepository $favorites;

    private RecommendRecipesUseCase $useCase;

    protected function setUp(): void
    {
        $this->recipes = new InMemoryRecipeRepository();
        $this->recipes->seed(1, 'Carbonara', 2, 'Massas', ['notaMedia' => 4.9]);
        $this->recipes->seed(2, 'Lasanha', 2, 'Massas', ['notaMedia' => 4.2]);
        $this->recipes->seed(3, 'Nhoque', 2, 'Massas', ['notaMedia' => 3.5]);
        $this->recipes->seed(4, 'Brigadeiro', 5, 'Doces', ['notaMedia' => 5.0]);
        $this->favorites = new InMemoryFavoriteRepository();
        $this->useCase = new RecommendRecipesUseCase($this->recipes, $this->favorites);
    }

    public function testAnonymousGetsTopRated(): void
    {
        $result = $this->useCase->execute(null, 2);

        $this->assertSame('Mais bem avaliadas', $result['title']);
        $this->assertSame('Brigadeiro', $result['cards'][0]['name'], 'maior nota primeiro');
    }

    public function testPersonalizedFromFavoriteCategoryExcludingFavorited(): void
    {
        // Usuário favoritou a Carbonara (Massas) → recomenda Massas, menos a favoritada.
        $this->favorites->withRecipeRow(1, ['idReceita' => 1, 'idcategoriaFK' => 2]);
        $this->favorites->add(7, 1);

        $result = $this->useCase->execute(7, 5);

        $this->assertSame('Para você', $result['title']);
        $nomes = array_column($result['cards'], 'name');
        $this->assertContains('Lasanha', $nomes);
        $this->assertNotContains('Carbonara', $nomes, 'não recomenda a já favoritada');
        $this->assertNotContains('Brigadeiro', $nomes, 'fora da categoria curtida');
    }
}
