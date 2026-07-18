<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Application\Mapper\RecipeViewMapper;
use PHPUnit\Framework\TestCase;

/**
 * Mapeamento das linhas do banco para o vocabulário das views: rótulo de
 * categoria vindo do JOIN, filtragem da sentinela de ingrediente, quebra do
 * modo de preparo e campos opcionais (dificuldade/cozimento/dicas).
 */
class RecipeViewMapperTest extends TestCase
{
    public function testSummaryUsesCategoryNameFromJoin(): void
    {
        $card = RecipeViewMapper::summary([
            'idReceita' => 7,
            'nomeReceita' => 'Lasanha',
            'tempoReceita' => '50 min',
            'dificuldade' => 'Difícil',
            'idcategoriaFK' => 2,
            'imagem' => 'lasanha.png',
            'nomeCategoria' => 'Massas',
        ]);

        $this->assertSame(7, $card['id']);
        $this->assertSame('Massas', $card['category']);
        $this->assertSame('Difícil', $card['difficulty']);
    }

    public function testSummaryFallsBackWhenCategoryMissing(): void
    {
        $card = RecipeViewMapper::summary([
            'idReceita' => 1,
            'nomeReceita' => 'Sem cat',
            'tempoReceita' => '10 min',
            'dificuldade' => null,
            'idcategoriaFK' => null,
            'imagem' => 'x.png',
            'nomeCategoria' => null,
        ]);

        $this->assertSame('Sem categoria', $card['category']);
        $this->assertNull($card['difficulty']);
    }

    public function testDetailFiltersSentinelAndSplitsPreparation(): void
    {
        $row = [
            'idReceita' => 3,
            'nomeReceita' => 'Teste',
            'link' => '<iframe></iframe>',
            'idcategoriaFK' => 5,
            'nomeCategoria' => 'Doces',
            'tempoReceita' => '25 min',
            'tempoCozimento' => '12 min',
            'dificuldade' => 'Fácil',
            'porcoes' => 6,
            'qtdCalorias' => 200.0,
            'imagem' => 'x.png',
            'dicas' => 'Sirva gelado.',
            'modoPreparo' => 'Bata tudo. Leve à geladeira.',
        ];
        for ($i = 1; $i <= 15; $i++) {
            $row['ingrediente_' . $i] = $i <= 2 ? "item {$i}" : null;
        }

        $detail = RecipeViewMapper::detail($row);

        $this->assertSame(['item 1', 'item 2', ...array_fill(0, 13, 'Não há mais ingredientes')], $detail['ingredients']);
        $this->assertCount(2, $detail['preparation']);
        $this->assertSame('Fácil', $detail['difficulty']);
        $this->assertSame('12 min', $detail['cookTime']);
        $this->assertSame('Sirva gelado.', $detail['tips']);
        $this->assertSame(5, $detail['categoryId']);
    }
}
