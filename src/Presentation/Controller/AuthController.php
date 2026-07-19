<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Application\Security\RateLimiterInterface;
use App\Application\UseCase\AuthenticateUserUseCase;
use App\Application\UseCase\DeleteUserAccountUseCase;
use App\Application\UseCase\RegisterUserUseCase;
use App\Domain\Exception\AuthenticationException;
use App\Domain\Exception\ValidationException;
use App\Presentation\Http\SessionManager;

/**
 * Controller da API JSON de autenticação e conta (endpoints em public/api/).
 *
 * Contrato de resposta: cada método devolve ['status' => int, 'body' => array]
 * e o entrypoint serializa como JSON. Erros seguem o formato {"detail": ...}
 * (padrão herdado do AuthService, referência desta implementação).
 *
 * Proteção contra força bruta: login, cadastro e exclusão de conta passam
 * por limitação de taxa (RateLimiterInterface); estouro responde 429.
 */
class AuthController
{
    /** Política anti força bruta do login: 5 falhas por IP+e-mail em 60s. */
    private const LOGIN_MAX_ATTEMPTS = 5;
    private const LOGIN_WINDOW_SECONDS = 60;

    /** Política anti-flood do cadastro: 10 tentativas por IP em 60s. */
    private const REGISTER_MAX_ATTEMPTS = 10;
    private const REGISTER_WINDOW_SECONDS = 60;

    /** Versão vigente dos Termos de Uso/Política de Privacidade (NC-01). */
    public const LEGAL_VERSION = '1.0';

    public function __construct(
        private readonly RegisterUserUseCase $registerUserUseCase,
        private readonly AuthenticateUserUseCase $authenticateUserUseCase,
        private readonly DeleteUserAccountUseCase $deleteUserAccountUseCase,
        private readonly SessionManager $sessionManager,
        private readonly RateLimiterInterface $rateLimiter,
    ) {
    }

    /**
     * POST /api/register.php — cadastra um usuário.
     *
     * Regras além do caso de uso: ciência dos Termos/Política é obrigatória
     * (campo 'aceite' — transparência LGPD) e o IP é limitado a 10
     * cadastros/min (anti-flood).
     *
     * Respostas: 201 sucesso · 400 validação · 429 limite excedido.
     */
    public function register(array $input): array
    {
        $email = (string) ($input['email'] ?? '');
        $this->log(sprintf('Tentativa de cadastro para o email: %s', $this->sanitizeLog($email)));

        $rateKey = 'register|' . $this->clientIp();
        if ($this->rateLimiter->tooManyAttempts($rateKey, self::REGISTER_MAX_ATTEMPTS, self::REGISTER_WINDOW_SECONDS)) {
            $this->log('Cadastro bloqueado por rate limit.');

            return ['status' => 429, 'body' => ['detail' => 'Muitas tentativas. Aguarde um instante e tente novamente.']];
        }
        $this->rateLimiter->hit($rateKey, self::REGISTER_WINDOW_SECONDS);

        if (empty($input['aceite'])) {
            return ['status' => 400, 'body' => ['detail' => 'É necessário aceitar os Termos de Uso e a Política de Privacidade.']];
        }

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

        $this->log(sprintf('Usuário cadastrado com sucesso (aceite dos termos v%s).', self::LEGAL_VERSION));

        return ['status' => 201, 'body' => ['detail' => 'Cadastro realizado com sucesso!', 'email' => trim($email)]];
    }

    /**
     * POST /api/login.php — autentica e abre a sessão.
     *
     * Anti força bruta: 5 falhas por IP+e-mail/60s → 429; o sucesso zera o
     * contador (não pune o titular legítimo). Efeitos no sucesso: regenera o
     * id da sessão (anti-fixation) e grava id/nome/e-mail + flag 'logado'.
     * Regra de segurança: validação e credencial erradas retornam o MESMO
     * 401 genérico — o cliente não descobre qual campo falhou.
     */
    public function login(array $input): array
    {
        $email = (string) ($input['email'] ?? '');
        $this->log(sprintf('Tentativa de login para o email: %s', $this->sanitizeLog($email)));

        $rateKey = 'login|' . $this->clientIp() . '|' . mb_strtolower(trim($email));
        if ($this->rateLimiter->tooManyAttempts($rateKey, self::LOGIN_MAX_ATTEMPTS, self::LOGIN_WINDOW_SECONDS)) {
            $this->log('Login bloqueado por rate limit.');

            return ['status' => 429, 'body' => ['detail' => 'Muitas tentativas de login. Aguarde 1 minuto e tente novamente.']];
        }

        try {
            $user = $this->authenticateUserUseCase->execute($email, (string) ($input['senha'] ?? ''));
        } catch (ValidationException|AuthenticationException $exception) {
            $this->rateLimiter->hit($rateKey, self::LOGIN_WINDOW_SECONDS);
            $this->log(sprintf('Falha no login: %s', $this->sanitizeLog($exception->getMessage())));

            return ['status' => 401, 'body' => ['detail' => 'Senha ou e-mail incorretos.']];
        }

        $this->rateLimiter->clear($rateKey);

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
     * Respostas: 200 com id/nome/e-mail · 401 sem sessão. Nunca expõe senha.
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

    /**
     * POST /api/delete-account.php — eliminação da conta pelo titular
     * (LGPD art. 18, VI), implementada por anonimização irreversível.
     *
     * Fluxo de segurança: exige sessão ativa + REAUTENTICAÇÃO com a senha
     * atual (confirmação explícita, prova de posse e neutralização de CSRF).
     * Falhas de senha contam no rate limit do login. No sucesso: anonimiza,
     * encerra a sessão e confirma ao titular.
     */
    public function deleteAccount(array $input): array
    {
        $this->sessionManager->start();

        if (empty($this->sessionManager->get('logado'))) {
            return ['status' => 401, 'body' => ['detail' => 'Não autenticado.']];
        }

        $email = (string) $this->sessionManager->get('emailSessao', '');
        $userId = (int) $this->sessionManager->get('idUsuario', 0);

        $rateKey = 'login|' . $this->clientIp() . '|' . mb_strtolower($email);
        if ($this->rateLimiter->tooManyAttempts($rateKey, self::LOGIN_MAX_ATTEMPTS, self::LOGIN_WINDOW_SECONDS)) {
            return ['status' => 429, 'body' => ['detail' => 'Muitas tentativas. Aguarde 1 minuto e tente novamente.']];
        }

        try {
            $this->authenticateUserUseCase->execute($email, (string) ($input['senha'] ?? ''));
        } catch (ValidationException|AuthenticationException) {
            $this->rateLimiter->hit($rateKey, self::LOGIN_WINDOW_SECONDS);
            $this->log(sprintf('Exclusão de conta negada (senha incorreta) para: %s', $this->sanitizeLog($email)));

            return ['status' => 401, 'body' => ['detail' => 'Senha incorreta. A conta não foi excluída.']];
        }

        try {
            $this->deleteUserAccountUseCase->execute($userId);
        } catch (ValidationException $exception) {
            return ['status' => 400, 'body' => ['detail' => $exception->getMessage()]];
        }

        $this->log(sprintf('Conta anonimizada a pedido do titular (id %d).', $userId));

        $this->sessionManager->destroy();

        return ['status' => 200, 'body' => ['detail' => 'Sua conta foi excluída. Sentiremos sua falta!']];
    }

    /** POST /api/logout.php — encerra a sessão (sempre 200, idempotente). */
    public function logoutApi(): array
    {
        $this->sessionManager->start();
        $this->sessionManager->destroy();

        return ['status' => 200, 'body' => ['detail' => 'Sessão encerrada.']];
    }

    /**
     * Logout tradicional do link do menu (entrypoint public/logout.php).
     * Encerra a sessão e devolve a URL de redirecionamento (o catálogo é
     * público, então volta para a home já como visitante).
     *
     * @return string URL de redirecionamento relativa ao docroot.
     */
    public function logout(): string
    {
        $this->sessionManager->start();
        $this->sessionManager->destroy();

        return 'index.php';
    }

    /**
     * IP do cliente considerando proxy reverso (a Render envia o IP de
     * origem no primeiro valor de X-Forwarded-For). Uso restrito ao rate
     * limiting — não é persistido.
     */
    private function clientIp(): string
    {
        $forwarded = (string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? '');
        if ($forwarded !== '') {
            return trim(explode(',', $forwarded)[0]);
        }

        return (string) ($_SERVER['REMOTE_ADDR'] ?? 'desconhecido');
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
