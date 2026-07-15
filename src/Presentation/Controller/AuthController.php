<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Application\UseCase\AuthenticateUserUseCase;
use App\Application\UseCase\RegisterUserUseCase;
use App\Domain\Exception\AuthenticationException;
use App\Domain\Exception\ValidationException;
use App\Presentation\Http\SessionManager;

/**
 * Controller da API de autenticação (padrão de referência: AuthService).
 * Cada método devolve ['status' => int, 'body' => array] — o entrypoint
 * serializa como JSON. Erros seguem o formato {"detail": "..."}.
 */
class AuthController
{
    public function __construct(
        private readonly RegisterUserUseCase $registerUserUseCase,
        private readonly AuthenticateUserUseCase $authenticateUserUseCase,
        private readonly SessionManager $sessionManager,
    ) {
    }

    /** POST /api/register.php */
    public function register(array $input): array
    {
        $email = (string) ($input['email'] ?? '');
        $this->log(sprintf('Tentativa de cadastro para o email: %s', $this->sanitizeLog($email)));

        try {
            $this->registerUserUseCase->execute(
                (string) ($input['nome'] ?? ''),
                $email,
                (string) ($input['senha'] ?? ''),
                isset($input['categoria']) && $input['categoria'] !== '' ? (int) $input['categoria'] : null,
            );
        } catch (ValidationException $exception) {
            $this->log(sprintf('Falha no cadastro: %s', $this->sanitizeLog($exception->getMessage())));

            return ['status' => 400, 'body' => ['detail' => $exception->getMessage()]];
        }

        $this->log('Usuário cadastrado com sucesso.');

        return ['status' => 201, 'body' => ['detail' => 'Cadastro realizado com sucesso!', 'email' => trim($email)]];
    }

    /** POST /api/login.php */
    public function login(array $input): array
    {
        $email = (string) ($input['email'] ?? '');
        $this->log(sprintf('Tentativa de login para o email: %s', $this->sanitizeLog($email)));

        try {
            $user = $this->authenticateUserUseCase->execute($email, (string) ($input['senha'] ?? ''));
        } catch (ValidationException|AuthenticationException $exception) {
            $this->log(sprintf('Falha no login: %s', $this->sanitizeLog($exception->getMessage())));

            return ['status' => 401, 'body' => ['detail' => 'Senha ou e-mail incorretos.']];
        }

        $this->sessionManager->start();
        $this->sessionManager->regenerate();
        $this->sessionManager->set('emailSessao', $user['emailUsuario']);
        $this->sessionManager->set('nomeSessao', $user['nomeUsuario']);
        $this->sessionManager->set('idUsuario', (int) $user['idUsuario']);
        $this->sessionManager->set('logado', true);

        $this->log('Login bem-sucedido.');

        return ['status' => 200, 'body' => ['detail' => 'Autenticado com sucesso.', 'nome' => $user['nomeUsuario']]];
    }

    /** GET /api/me.php — rota protegida */
    public function me(): array
    {
        $this->sessionManager->start();

        if (empty($this->sessionManager->get('logado'))) {
            return ['status' => 401, 'body' => ['detail' => 'Não autenticado.']];
        }

        return [
            'status' => 200,
            'body' => [
                'id' => (int) $this->sessionManager->get('idUsuario', 0),
                'nome' => (string) $this->sessionManager->get('nomeSessao', ''),
                'email' => (string) $this->sessionManager->get('emailSessao', ''),
            ],
        ];
    }

    /** POST /api/logout.php */
    public function logoutApi(): array
    {
        $this->sessionManager->start();
        $this->sessionManager->destroy();

        return ['status' => 200, 'body' => ['detail' => 'Sessão encerrada.']];
    }

    /** Logout tradicional (link do menu) — devolve a URL de redirecionamento. */
    public function logout(): string
    {
        $this->sessionManager->start();
        $this->sessionManager->destroy();

        return '../../login.php';
    }

    /** Remove quebras de linha para prevenir log injection (referência: AuthService). */
    private function sanitizeLog(string $value): string
    {
        return str_replace(["\r", "\n"], '_', $value);
    }

    private function log(string $message): void
    {
        error_log('[auth] ' . $message);
    }
}
