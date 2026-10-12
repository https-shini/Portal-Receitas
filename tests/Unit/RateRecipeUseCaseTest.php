<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Application\UseCase\RateRecipeUseCase;
use App\Domain\Exception\NotFoundException;
use App\Domain\Exception\ValidationException;
use App\Tests\Support\InMemoryRatingRepository;
use App\Tests\Support\InMemoryRecipeRepository;
use PHPUnit\Framework\TestCase;

/**
 * Avaliação de receita: revoto atualiza a média, nota 0 remove, notas fora do
 * intervalo são rejeitadas e receita inexistente responde "não encontrada".
 */
class RateRecipeUseCaseTest extends TestCase
{
    private RateRecipeUseCase $useCase;

    private InMemoryRatingRepository $repo;

    protected function setUp(): void
    {
        $this->repo = new InMemoryRatingRepository();

        $recipes = new InMemoryRecipeRepository();
        $recipes->seed(10, 'Carbonara', 2, 'Massas');

        $this->useCase = new RateRecipeUseCase($this->repo, $recipes);
    }

    public function testAveragesMultipleUsers(): void
    {
        $this->useCase->execute(1, 10, 5);
        $result = $this->useCase->execute(2, 10, 4);

        $this->assertSame(4.5, $result['average']);
        $this->assertSame(2, $result['count']);
        $this->assertSame(4, $result['userScore']);
    }

    public function testRevoteReplacesInsteadOfDuplicating(): void
    {
        $this->useCase->execute(1, 10, 2);
        $result = $this->useCase->execute(1, 10, 5);

        $this->assertSame(1, $result['count'], 'o revoto não cria uma segunda linha');
        $this->assertSame(5.0, $result['average']);
        $this->assertSame(5, $result['userScore']);
    }

    public function testScoreZeroRemovesRating(): void
    {
        $this->useCase->execute(1, 10, 3);
        $result = $this->useCase->execute(1, 10, 0);

        $this->assertSame(0, $result['count']);
        $this->assertNull($result['average']);
        $this->assertNull($result['userScore']);
    }

    public function testRejectsOutOfRangeScore(): void
    {
        $this->expectException(ValidationException::class);
        $this->useCase->execute(1, 10, 9);
    }

    /**
     * Regressão: a violação de chave estrangeira virava PDOException e o
     * endpoint respondia 503 ("serviço indisponível") a um erro do cliente.
     */
    public function testRejectsUnknownRecipe(): void
    {
        $this->expectException(NotFoundException::class);
        $this->useCase->execute(1, 999, 5);
    }
}
