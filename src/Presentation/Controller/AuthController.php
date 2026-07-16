<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Application\UseCase\AuthenticateUserUseCase;
use App\Application\UseCase\RegisterUserUseCase;
use App\Domain\Exception\AuthenticationException;
use App\Domain\Exception\ValidationException;
use App\Presentation\Http\SessionManager;

/**
 * Controller da API JSON de autenticação (endpoints em public/api/).
 *
 * Contrato de resposta: cada método devolve ['status' => int, 'body' => array]
 * e o entrypoint serializa como JSON. Erros seguem o formato {"detail": ...}
 * (padrão herdado do AuthService, referência desta implementação), o que
 * mantém o parse de erros do frontend uniforme.
 */
class AuthController
{
    public function __construct(
        private readonly RegisterUserUseCase $registerUserUseCase,
        private readonly AuthenticateUserUseCase $authenticateUserUseCase,
        private readonly SessionManager $sessionManager,
    ) {
    }

    /**
     * POST /api/register.php — cadastra um usuário.
     *
     * Respostas: 201 sucesso · 400 validação (mensagem do domínio no detail).
     * Toda tentativa é registrada em log com e-mail sanitizado.
     */
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

    /**
     * POST /api/login.php — autentica e abre a sessão.
     *
     * Efeitos colaterais no sucesso: regenera o id da sessão (anti-fixation)
     * e grava id/nome/e-mail + flag 'logado'.
     * Regra de segurança: erro de validação e de credencial retornam o MESMO
     * 401 genérico — o cliente não descobre qual campo falhou.
     */
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

    /**
     * GET /api/me.php — rota protegida: identifica o usuário da sessão.
     *
     * Respostas: 200 com id/nome/e-mail · 401 sem sessão autenticada.
     * Nunca expõe o hash de senha.
     */
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

    /** POST /api/logout.php — encerra a sessão (sempre 200, idempotente). */
    public function logoutApi(): array
    {
        $this->sessionManager->start();
        $this->sessionManager->destroy();

        return ['status' => 200, 'body' => ['detail' => 'Sessão encerrada.']];
    }

    /**
     * Logout tradicional do link do menu (assets/php/logout.php).
     *
     * @return string URL de redirecionamento relativa àquele entrypoint.
     */
    public function logout(): string
    {
        $this->sessionManager->start();
        $this->sessionManager->destroy();

        return '../../login.php';
    }

    /**
     * Neutraliza quebras de linha em valores vindos do usuário antes de
     * logar — impede forjar linhas falsas no log (log injection).
     */
    private function sanitizeLog(string $value): string
    {
        return str_replace(["\r", "\n"], '_', $value);
    }

    private function log(string $message): void
    {
        error_log('[auth] ' . $message);
    }
}
