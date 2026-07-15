<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Application\UseCase\AuthenticateUserUseCase;
use App\Application\UseCase\RegisterUserUseCase;
use App\Domain\Exception\AuthenticationException;
use App\Domain\Exception\ValidationException;
use App\Presentation\Http\SessionManager;

class AuthController
{
    public function __construct(
        private readonly RegisterUserUseCase $registerUserUseCase,
        private readonly AuthenticateUserUseCase $authenticateUserUseCase,
        private readonly SessionManager $sessionManager,
    ) {
    }

    public function login(array $post): array
    {
        $this->sessionManager->start();

        if (!isset($post['enviar'])) {
            return [
                'erroLogin' => !empty($_GET['erro']),
                'erroCredenciais' => false,
            ];
        }

        try {
            $user = $this->authenticateUserUseCase->execute((string) ($post['email'] ?? ''), (string) ($post['pswrd'] ?? ''));

            $this->sessionManager->set('emailSessao', $user['emailUsuario']);
            $this->sessionManager->set('nomeSessao', $user['nomeUsuario']);
            $this->sessionManager->set('idUsuario', (int) $user['idUsuario']);
            $this->sessionManager->set('logado', true);

            return ['redirect' => 'index.php'];
        } catch (ValidationException|AuthenticationException) {
            return [
                'erroLogin' => !empty($_GET['erro']),
                'erroCredenciais' => true,
            ];
        }
    }

    public function register(array $post): array
    {
        if (!isset($post['email'])) {
            return ['redirect' => '../../register.php'];
        }

        try {
            $created = $this->registerUserUseCase->execute(
                (string) ($post['nameUser'] ?? ''),
                (string) ($post['email'] ?? ''),
                (string) ($post['password'] ?? ''),
                isset($post['categoria']) ? (int) $post['categoria'] : null,
            );

            if ($created) {
                return [
                    'redirect' => '../../login.php',
                    'alert' => 'Cadastro realizado com sucesso!',
                ];
            }

            return ['redirect' => '../../register.php', 'alert' => 'Erro ao cadastrar'];
        } catch (ValidationException $exception) {
            return ['redirect' => '../../register.php', 'alert' => $exception->getMessage()];
        }
    }

    public function logout(): string
    {
        $this->sessionManager->start();
        $this->sessionManager->destroy();

        return '../../login.php';
    }
}
