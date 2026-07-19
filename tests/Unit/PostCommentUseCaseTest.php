<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Application\UseCase\PostCommentUseCase;
use App\Domain\Exception\ValidationException;
use App\Tests\Support\InMemoryCommentRepository;
use PHPUnit\Framework\TestCase;

/**
 * Publicação de comentário: guarda o texto após validar (não vazio, ≤ 500),
 * aparando espaços; rejeita texto vazio e longo demais.
 */
class PostCommentUseCaseTest extends TestCase
{
    private InMemoryCommentRepository $repo;

    private PostCommentUseCase $useCase;

    protected function setUp(): void
    {
        $this->repo = new InMemoryCommentRepository();
        $this->useCase = new PostCommentUseCase($this->repo);
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
}
