<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Application\UseCase\UpdateUserProfileUseCase;
use App\Domain\Exception\ValidationException;
use App\Presentation\Http\SessionManager;

/**
 * Controller da página de perfil (rota protegida por sessão).
 *
 * Devolve à view um array com 'redirect' (quando a navegação deve mudar) ou
 * os dados de exibição do formulário.
 */
class ProfileController
{
    public function __construct(
        private readonly UpdateUserProfileUseCase $updateUserProfileUseCase,
        private readonly SessionManager $sessionManager,
    ) {
    }

    /**
     * GET exibe o formulário; POST com 'salvar' aplica a atualização.
     *
     * Guard de autenticação: sem sessão 'logado', destrói qualquer resíduo de
     * sessão e redireciona para login.php?erro=true (a tela de login exibe o
     * aviso de acesso restrito).
     *
     * Regra de negócio pós-atualização: dados mudaram → a sessão é encerrada
     * e o usuário faz login novamente com as credenciais novas.
     */
    public function handle(array $post): array
    {
        $this->sessionManager->start();

        if (empty($this->sessionManager->get('logado'))) {
            $this->sessionManager->destroy();

            return ['redirect' => 'login.php?erro=true'];
        }

        $viewData = [
            'nome' => (string) $this->sessionManager->get('nomeSessao', ''),
            'email' => (string) $this->sessionManager->get('emailSessao', ''),
            'erroAtualizacao' => false,
            'csrf' => $this->sessionManager->csrfToken(),
        ];

        if (!isset($post['salvar'])) {
            return $viewData;
        }

        // Anti-CSRF: o POST precisa devolver o token emitido para esta sessão
        // (defesa adicional ao SameSite=Lax do cookie).
        if (!$this->sessionManager->validateCsrf($post['_csrf'] ?? null)) {
            $viewData['erroAtualizacao'] = true;

            return $viewData;
        }

        try {
            $updated = $this->updateUserProfileUseCase->execute(
                (int) $this->sessionManager->get('idUsuario', 0),
                $post['nome'] ?? null,
                $post['email'] ?? null,
                $post['senha'] ?? null,
            );

            if ($updated) {
                $this->sessionManager->destroy();
                return ['redirect' => 'login.php'];
            }

            $viewData['erroAtualizacao'] = true;
            return $viewData;
        } catch (ValidationException) {
            $viewData['erroAtualizacao'] = true;
            return $viewData;
        }
    }
}
