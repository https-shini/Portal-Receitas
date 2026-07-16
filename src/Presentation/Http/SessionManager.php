<?php

declare(strict_types=1);

namespace App\Presentation\Http;

/**
 * Mecanismo único de sessão da aplicação — nenhum outro código chama
 * session_* diretamente.
 *
 * Chaves usadas: 'logado' (flag de autenticação), 'idUsuario', 'nomeSessao'
 * e 'emailSessao'.
 */
class SessionManager
{
    /**
     * Inicia a sessão com cookie endurecido (idempotente).
     *
     * HttpOnly nega acesso do JavaScript ao cookie (anti-XSS); SameSite=Lax
     * bloqueia envio em POSTs cross-site (anti-CSRF); Secure é ligado quando
     * a requisição chega por HTTPS.
     */
    public function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_set_cookie_params([
            'httponly' => true,
            'samesite' => 'Lax',
            'secure' => $this->isHttps(),
            'path' => '/',
        ]);

        session_start();
    }

    /**
     * Troca o id da sessão descartando o anterior — chamado imediatamente
     * após autenticar, para impedir session fixation.
     */
    public function regenerate(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    /** Encerra a sessão e limpa o estado em memória (logout). */
    public function destroy(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        $_SESSION = [];
    }

    /**
     * Detecta HTTPS mesmo atrás de proxy reverso: na Render o TLS termina
     * antes do Apache, e a origem segura chega em X-Forwarded-Proto.
     */
    private function isHttps(): bool
    {
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            return true;
        }

        return ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    }
}
