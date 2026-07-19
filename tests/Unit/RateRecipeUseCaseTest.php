<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Application\UseCase\RateRecipeUseCase;
use App\Domain\Exception\ValidationException;
use App\Tests\Support\InMemoryRatingRepository;
use PHPUnit\Framework\TestCase;

/**
 * Avaliação de receita: revoto atualiza a média, nota 0 remove, notas fora do
 * intervalo são rejeitadas.
 */
class RateRecipeUseCaseTest extends TestCase
{
    private RateRecipeUseCase $useCase;

    private InMemoryRatingRepository $repo;

    protected function setUp(): void
    {
        $this->repo = new InMemoryRatingRepository();
        $this->useCase = new RateRecipeUseCase($this->repo);
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
}
