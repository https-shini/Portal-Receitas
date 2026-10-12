<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Application\UseCase\PostCommentUseCase;
use App\Domain\Exception\NotFoundException;
use App\Domain\Exception\ValidationException;
use App\Tests\Support\InMemoryCommentRepository;
use App\Tests\Support\InMemoryRecipeRepository;
use PHPUnit\Framework\TestCase;

/**
 * Publicação de comentário: guarda o texto após validar (não vazio, ≤ 500),
 * aparando espaços; rejeita texto vazio, longo demais e receita inexistente.
 */
class PostCommentUseCaseTest extends TestCase
{
    private InMemoryCommentRepository $repo;

    private PostCommentUseCase $useCase;

    protected function setUp(): void
    {
        $this->repo = new InMemoryCommentRepository();

        $recipes = new InMemoryRecipeRepository();
        $recipes->seed(10, 'Carbonara', 2, 'Massas');

        $this->useCase = new PostCommentUseCase($this->repo, $recipes);
    }

    public function testStoresTrimmedComment(): void
    {
        $id = $this->useCase->execute(1, 10, '  Ficou ótimo!  ');

        $this->assertSame(1, $id);
        $this->assertSame('Ficou ótimo!', $this->repo->listByRecipe(10)[0]['texto']);
    }

    public function testRejectsEmptyComment(): void
    {
        $this->expectException(ValidationException::class);
        $this->useCase->execute(1, 10, '   ');
    }

    public function testRejectsTooLongComment(): void
    {
        $this->expectException(ValidationException::class);
        $this->useCase->execute(1, 10, str_repeat('a', 501));
    }

    /**
     * Regressão: a violação de chave estrangeira virava PDOException e o
     * endpoint respondia 503 ("serviço indisponível") a um erro do cliente.
     */
    public function testRejectsUnknownRecipe(): void
    {
        $this->expectException(NotFoundException::class);
        $this->useCase->execute(1, 999, 'Comentário em receita inexistente');
    }
}
