<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Application\UseCase\ListFavoritesUseCase;
use App\Application\UseCase\ToggleFavoriteUseCase;
use App\Tests\Support\InMemoryFavoriteRepository;
use PHPUnit\Framework\TestCase;

/**
 * Alternância de favoritos: primeiro toque adiciona, segundo remove; a
 * listagem reflete o estado no formato de card.
 */
class ToggleFavoriteUseCaseTest extends TestCase
{
    public function testToggleAddsThenRemoves(): void
    {
        $repo = new InMemoryFavoriteRepository();
        $useCase = new ToggleFavoriteUseCase($repo);

        $this->assertTrue($useCase->execute(1, 42), 'primeiro toque favorita');
        $this->assertTrue($repo->exists(1, 42));

        $this->assertFalse($useCase->execute(1, 42), 'segundo toque desfavorita');
        $this->assertFalse($repo->exists(1, 42));
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
