<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Application\Security\RateLimiterInterface;
use App\Application\UseCase\PostCommentUseCase;
use App\Domain\Exception\ValidationException;
use App\Domain\Repository\CommentRepositoryInterface;
use App\Presentation\Http\SessionManager;

/**
 * Controller de comentários: lista os comentários de uma receita (para a
 * página) e trata a criação/remoção via API (POST autenticado + CSRF + rate
 * limit). O usuário só remove os próprios comentários.
 */
class CommentController
{
    private const MAX = 10;
    private const WINDOW_SECONDS = 60;

    public function __construct(
        private readonly PostCommentUseCase $postCommentUseCase,
        private readonly CommentRepositoryInterface $commentRepository,
        private readonly SessionManager $sessionManager,
        private readonly RateLimiterInterface $rateLimiter,
    ) {
    }

    /**
     * Comentários da receita no formato da view, marcando os do próprio
     * usuário (para exibir o botão de remover).
     *
     * @return list<array{id:int, autor:string, texto:string, data:string, mine:bool}>
     */
    public function forRecipe(int $recipeId): array
    {
        $this->sessionManager->start();
        $currentUser = (int) $this->sessionManager->get('idUsuario');

        return array_map(
            fn (array $c): array => [
                'id' => $c['idComentario'],
                'autor' => $c['autor'],
                'texto' => $c['texto'],
                'data' => $this->formatDate($c['criadoEm']),
                'mine' => $currentUser > 0 && $c['idUsuario'] === $currentUser,
            ],
            $this->commentRepository->listByRecipe($recipeId),
        );
    }

    /**
     * POST /api/comments.php — publica um comentário; devolve o comentário
     * criado para o frontend inseri-lo sem recarregar.
     *
     * @return array{status:int, body:array<string,mixed>}
     */
    public function post(array $input): array
    {
        [$userId, $error] = $this->authorize($input);
        if ($error !== null) {
            return $error;
        }

        $recipeId = (int) ($input['idReceita'] ?? 0);
        if ($recipeId < 1) {
            return ['status' => 400, 'body' => ['detail' => 'Receita inválida.']];
        }

        try {
            $id = $this->postCommentUseCase->execute($userId, $recipeId, (string) ($input['texto'] ?? ''));
        } catch (ValidationException $exception) {
            return ['status' => 400, 'body' => ['detail' => $exception->getMessage()]];
        }

        return ['status' => 201, 'body' => [
            'id' => $id,
            'autor' => (string) $this->sessionManager->get('nomeSessao'),
            'texto' => trim((string) $input['texto']),
            'data' => $this->formatDate(date('Y-m-d H:i:s')),
            'mine' => true,
        ]];
    }

    /**
     * POST /api/comments.php (com acao=excluir) — remove um comentário do
     * próprio usuário.
     *
     * @return array{status:int, body:array<string,mixed>}
     */
    public function delete(array $input): array
    {
        [$userId, $error] = $this->authorize($input);
        if ($error !== null) {
            return $error;
        }

        $commentId = (int) ($input['idComentario'] ?? 0);
        if ($commentId < 1) {
            return ['status' => 400, 'body' => ['detail' => 'Comentário inválido.']];
        }

        if (!$this->commentRepository->delete($commentId, $userId)) {
            return ['status' => 404, 'body' => ['detail' => 'Comentário não encontrado.']];
        }

        return ['status' => 200, 'body' => ['deleted' => true]];
    }

    /**
     * Sessão + CSRF + rate limit comuns às ações de escrita.
     *
     * @return array{0:int, 1:array{status:int, body:array<string,mixed>}|null}
     */
    private function authorize(array $input): array
    {
        $this->sessionManager->start();
        $userId = (int) $this->sessionManager->get('idUsuario');

        if (empty($this->sessionManager->get('logado')) || $userId < 1) {
            return [0, ['status' => 401, 'body' => ['detail' => 'Você precisa entrar para comentar.']]];
        }

        $csrf = isset($input['_csrf']) ? (string) $input['_csrf'] : null;
        if (!$this->sessionManager->validateCsrf($csrf)) {
            return [0, ['status' => 403, 'body' => ['detail' => 'Requisição inválida. Recarregue a página e tente novamente.']]];
        }

        $rateKey = 'comment|' . $userId;
        if ($this->rateLimiter->tooManyAttempts($rateKey, self::MAX, self::WINDOW_SECONDS)) {
            return [0, ['status' => 429, 'body' => ['detail' => 'Muitos comentários em pouco tempo. Aguarde um instante.']]];
        }
        $this->rateLimiter->hit($rateKey, self::WINDOW_SECONDS);

        return [$userId, null];
    }

    private function formatDate(string $sqlDate): string
    {
        $ts = strtotime($sqlDate);

        return $ts !== false ? date('d/m/Y \à\s H:i', $ts) : $sqlDate;
    }
}
