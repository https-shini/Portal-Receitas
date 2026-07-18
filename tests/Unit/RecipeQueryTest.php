<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Application\Query\RecipeQuery;
use PHPUnit\Framework\TestCase;

/**
 * Saneamento do critério de busca a partir da query string: categorias e
 * dificuldades múltiplas, whitelist de ordenação, página mínima e detecção
 * de filtros ativos.
 */
class RecipeQueryTest extends TestCase
{
    public function testParsesMultipleCategoriesAndDifficulties(): void
    {
        $q = RecipeQuery::fromArray([
            'pesquisa' => '  queijo ',
            'categoriaReceita' => ['2', '5', '2', '0'],
            'dificuldade' => ['Fácil', 'Inexistente', 'Difícil'],
            'ordenar' => 'nome',
            'pagina' => '3',
        ]);

        $this->assertSame('queijo', $q->search);
        $this->assertSame([2, 5], $q->categoryIds, 'ids duplicados/invalidos são descartados');
        $this->assertSame(['Fácil', 'Difícil'], $q->difficulties, 'níveis fora do ENUM são descartados');
        $this->assertSame('nome', $q->sort);
        $this->assertSame(3, $q->page);
        $this->assertTrue($q->hasFilters());
    }

    public function testRejectsUnknownSortAndNormalizesPage(): void
    {
        $q = RecipeQuery::fromArray(['ordenar' => 'preco', 'pagina' => '-4']);

        $this->assertSame('relevancia', $q->sort);
        $this->assertSame(1, $q->page);
        $this->assertFalse($q->hasFilters());
    }

    public function testAcceptsScalarCategory(): void
    {
        $q = RecipeQuery::fromArray(['categoriaReceita' => '4']);

        $this->assertSame([4], $q->categoryIds);
        $this->assertTrue($q->hasFilters());
    }
}
