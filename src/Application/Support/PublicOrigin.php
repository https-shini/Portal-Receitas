<?php

declare(strict_types=1);

namespace App\Application\Support;

/**
 * Origem pública do site (esquema + host [+ porta]), usada onde a página
 * precisa de URLs absolutas: canonical, Open Graph, JSON-LD e sitemap.
 *
 * Com APP_URL definida (ex.: https://receitas.gcruz.dev.br), todas as URLs
 * absolutas apontam para esse endereço, qualquer que seja o host pelo qual a
 * requisição chegou (o domínio próprio ou o *.onrender.com). Assim os
 * buscadores veem um único endereço oficial e um cabeçalho Host forjado não
 * entra no sitemap.
 *
 * Sem APP_URL (ou com valor inválido), a origem é derivada da requisição,
 * como antes — útil em desenvolvimento local.
 */
final class PublicOrigin
{
    /**
     * @param array<string, mixed> $server     Normalmente $_SERVER.
     * @param string|null          $configured Valor de APP_URL, se houver.
     */
    public static function resolve(array $server, ?string $configured): string
    {
        $fromConfig = self::fromConfigured($configured);
        if ($fromConfig !== null) {
            return $fromConfig;
        }

        $https = (!empty($server['HTTPS']) && $server['HTTPS'] !== 'off')
            || (($server['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
        $host = preg_replace('/[^\w.:-]/', '', (string) ($server['HTTP_HOST'] ?? ''));

        return ($https ? 'https' : 'http') . '://' . ($host !== '' ? $host : 'localhost');
    }

    /**
     * Destino do redirecionamento quando a requisição chega pelo endereço
     * antigo (o host do Render, em RENDER_EXTERNAL_HOSTNAME): APP_URL mais o
     * mesmo caminho e query. Devolve null quando não há o que redirecionar —
     * APP_URL ausente ou inválida, host antigo não informado ou requisição
     * vinda por outro host (o domínio próprio, o health check interno, o
     * ambiente local).
     *
     * @param array<string, mixed> $server     Normalmente $_SERVER.
     * @param string|null          $configured Valor de APP_URL, se houver.
     * @param string|null          $oldHost    Valor de RENDER_EXTERNAL_HOSTNAME.
     */
    public static function redirectFromOldHost(array $server, ?string $configured, ?string $oldHost): ?string
    {
        $origin = self::fromConfigured($configured);
        $oldHost = strtolower(trim((string) $oldHost));
        if ($origin === null || $oldHost === '') {
            return null;
        }

        $host = strtolower((string) preg_replace('/:\d+$/', '', (string) ($server['HTTP_HOST'] ?? '')));
        if ($host !== $oldHost) {
            return null;
        }

        // Só caminhos locais: "//outro-host" viraria uma URL de outro domínio.
        $uri = (string) ($server['REQUEST_URI'] ?? '/');
        $uri = '/' . ltrim($uri, '/');

        return $origin . $uri;
    }

    private static function fromConfigured(?string $configured): ?string
    {
        $configured = trim((string) $configured);
        if ($configured === '') {
            return null;
        }

        $parts = parse_url($configured);
        if (
            $parts === false
            || !in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)
            || !isset($parts['host'])
            || !preg_match('/^[a-z0-9.-]+$/i', $parts['host'])
            || isset($parts['user']) || isset($parts['pass'])
            || isset($parts['query']) || isset($parts['fragment'])
            || !in_array($parts['path'] ?? '', ['', '/'], true)
        ) {
            return null;
        }

        $port = isset($parts['port']) ? ':' . $parts['port'] : '';

        return strtolower($parts['scheme']) . '://' . strtolower($parts['host']) . $port;
    }
}
