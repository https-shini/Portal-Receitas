<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Application\UseCase\ListFavoritesUseCase;
use App\Application\UseCase\ToggleFavoriteUseCase;
use App\Domain\Exception\NotFoundException;
use App\Tests\Support\InMemoryFavoriteRepository;
use App\Tests\Support\InMemoryRecipeRepository;
use PHPUnit\Framework\TestCase;

/**
 * Alternância de favoritos: primeiro toque adiciona, segundo remove; a
 * listagem reflete o estado no formato de card. Receita inexistente é
 * rejeitada antes de tocar o repositório.
 */
class ToggleFavoriteUseCaseTest extends TestCase
{
    /** Catálogo com a receita 42 disponível para favoritar. */
    private static function recipes(): InMemoryRecipeRepository
    {
        $recipes = new InMemoryRecipeRepository();
        $recipes->seed(42, 'Carbonara', 2, 'Massas');

        return $recipes;
    }

    public function testToggleAddsThenRemoves(): void
    {
        $repo = new InMemoryFavoriteRepository();
        $useCase = new ToggleFavoriteUseCase($repo, self::recipes());

        $this->assertTrue($useCase->execute(1, 42), 'primeiro toque favorita');
        $this->assertTrue($repo->exists(1, 42));

        $this->assertFalse($useCase->execute(1, 42), 'segundo toque desfavorita');
        $this->assertFalse($repo->exists(1, 42));
    }

    /**
     * Regressão: o INSERT IGNORE do repositório engole a violação de chave
     * estrangeira, então sem a guarda o caso de uso respondia "favoritada"
     * (true) sem gravar nada — e todo toque seguinte repetia a mesma mentira.
     */
    public function testRejectsUnknownRecipe(): void
    {
        $repo = new InMemoryFavoriteRepository();
        $useCase = new ToggleFavoriteUseCase($repo, self::recipes());

        $this->expectException(NotFoundException::class);

        try {
            $useCase->execute(1, 999);
        } finally {
            $this->assertFalse($repo->exists(1, 999), 'nada é gravado para receita inexistente');
        }
    }

    public function testListReturnsFavoritedCards(): void
    {
        $repo = new InMemoryFavoriteRepository();
        $repo->withRecipeRow(42, [
            'idReceita' => 42,
            'nomeReceita' => 'Carbonara',
            'tempoReceita' => '30 min',
            'dificuldade' => 'Médio',
            'idcategoriaFK' => 2,
            'imagem' => 'carbonara.png',
            'nomeCategoria' => 'Massas',
        ]);
        $repo->add(1, 42);

        $cards = (new ListFavoritesUseCase($repo))->execute(1);

        $this->assertCount(1, $cards);
        $this->assertSame('Carbonara', $cards[0]['name']);
        $this->assertSame('Massas', $cards[0]['category']);
    }
}
