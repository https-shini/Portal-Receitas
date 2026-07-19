<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Application\Support\Slug;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Geração de slugs das URLs de receita: minúsculas, sem acentos, hifenizado.
 */
class SlugTest extends TestCase
{
    #[DataProvider('casos')]
    public function testMake(string $entrada, string $esperado): void
    {
        $this->assertSame($esperado, Slug::make($entrada));
    }

    public static function casos(): array
    {
        return [
            'acentos e maiúsculas' => ['Macarrão à Carbonara', 'macarrao-a-carbonara'],
            'cedilha' => ['Feijão com Linguiça', 'feijao-com-linguica'],
            'pontuação e espaços' => ['  Torta   de Limão! ', 'torta-de-limao'],
            'só símbolos vira fallback' => ['!!!', 'receita'],
            'string vazia vira fallback' => ['', 'receita'],
        ];
    }
}
