<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Application\UseCase\ShowRecipeUseCase;
use App\Tests\Support\InMemoryRecipeRepository;
use PHPUnit\Framework\TestCase;

/**
 * Página de receita: detalhe por id + relacionadas da mesma categoria; id
 * inexistente devolve null (o entrypoint responde 404).
 */
class ShowRecipeUseCaseTest extends TestCase
{
    private ShowRecipeUseCase $useCase;

    protected function setUp(): void
    {
        $repo = new InMemoryRecipeRepository();
        $repo->seed(1, 'Carbonara', 2, 'Massas');
        $repo->seed(2, 'Lasanha', 2, 'Massas');
        $repo->seed(3, 'Nhoque', 2, 'Massas');
        $repo->seed(4, 'Brigadeiro', 5, 'Doces');
        $this->useCase = new ShowRecipeUseCase($repo);
    }

    public function testReturnsRecipeWithRelatedFromSameCategory(): void
    {
        $result = $this->useCase->execute(1);

        $this->assertNotNull($result);
        $this->assertSame('Carbonara', $result['recipe']['name']);
        $this->assertNotEmpty($result['related']);
        foreach ($result['related'] as $card) {
            $this->assertSame('Massas', $card['category']);
            $this->assertNotSame(1, $card['id'], 'a própria receita não aparece nas relacionadas');
        }
    }

    public function testReturnsNullForUnknownId(): void
    {
        $this->assertNull($this->useCase->execute(999));
    }

    public function testGalleryPrependsMainImageWithoutDuplicating(): void
    {
        $repo = new InMemoryRecipeRepository();
        $repo->seed(1, 'Carbonara', 2, 'Massas', ['imagem' => 'carbonara.png']);
        $repo->withImages(1, ['carbonara.png', 'food.jpg', 'exemplo.png']);
        $result = (new ShowRecipeUseCase($repo))->execute(1);

        // imagem principal primeiro; sem duplicar a que também está na galeria
        $this->assertSame(['carbonara.png', 'food.jpg', 'exemplo.png'], $result['recipe']['gallery']);
    }
}
