<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Application\UseCase\UpdateUserProfileUseCase;
use App\Domain\Exception\ValidationException;
use App\Presentation\Http\SessionManager;

class ProfileController
{
    public function __construct(
        private readonly UpdateUserProfileUseCase $updateUserProfileUseCase,
        private readonly SessionManager $sessionManager,
    ) {
    }

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
        ];

        if (!isset($post['salvar'])) {
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
