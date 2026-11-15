<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Application\Support\PublicOrigin;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Origem pública usada no canonical, no Open Graph e no sitemap.
 */
class PublicOriginTest extends TestCase
{
    public function testAppUrlPrevaleceSobreOHostDaRequisicao(): void
    {
        $server = ['HTTP_HOST' => 'portal-receitas.onrender.com', 'HTTP_X_FORWARDED_PROTO' => 'https'];

        $this->assertSame(
            'https://receitas.gcruz.dev.br',
            PublicOrigin::resolve($server, 'https://receitas.gcruz.dev.br/')
        );
    }

    public function testSemAppUrlUsaOHostEOProtocoloAtrasDoProxy(): void
    {
        $server = ['HTTP_HOST' => 'portal-receitas.onrender.com', 'HTTP_X_FORWARDED_PROTO' => 'https'];

        $this->assertSame('https://portal-receitas.onrender.com', PublicOrigin::resolve($server, null));
    }

    public function testSemAppUrlEmHttpLocal(): void
    {
        $this->assertSame('http://localhost:8080', PublicOrigin::resolve(['HTTP_HOST' => 'localhost:8080'], ''));
    }

    public function testHostComCaracteresInvalidosEhLimpo(): void
    {
        $server = ['HTTP_HOST' => 'evil.com/"><script>'];

        $this->assertSame('http://evil.comscript', PublicOrigin::resolve($server, null));
    }

    public function testSemHostVoltaParaLocalhost(): void
    {
        $this->assertSame('http://localhost', PublicOrigin::resolve([], null));
    }

    public function testHostAntigoRedirecionaParaAppUrlComCaminhoEQuery(): void
    {
        $server = ['HTTP_HOST' => 'portal-receitas.onrender.com', 'REQUEST_URI' => '/receita/7/bolo?x=1'];

        $this->assertSame(
            'https://receitas.gcruz.dev.br/receita/7/bolo?x=1',
            PublicOrigin::redirectFromOldHost($server, 'https://receitas.gcruz.dev.br', 'portal-receitas.onrender.com')
        );
    }

    public function testHostAntigoComPortaETamanhoDiferenteTambemRedireciona(): void
    {
        $server = ['HTTP_HOST' => 'Portal-Receitas.onrender.com:443', 'REQUEST_URI' => '/'];

        $this->assertSame(
            'https://receitas.gcruz.dev.br/',
            PublicOrigin::redirectFromOldHost($server, 'https://receitas.gcruz.dev.br/', 'portal-receitas.onrender.com')
        );
    }

    public function testDominioProprioNaoRedireciona(): void
    {
        $server = ['HTTP_HOST' => 'receitas.gcruz.dev.br', 'REQUEST_URI' => '/'];

        $this->assertNull(
            PublicOrigin::redirectFromOldHost($server, 'https://receitas.gcruz.dev.br', 'portal-receitas.onrender.com')
        );
    }

    public function testSemAppUrlOuSemHostAntigoNaoRedireciona(): void
    {
        $server = ['HTTP_HOST' => 'portal-receitas.onrender.com', 'REQUEST_URI' => '/'];

        $this->assertNull(PublicOrigin::redirectFromOldHost($server, null, 'portal-receitas.onrender.com'));
        $this->assertNull(PublicOrigin::redirectFromOldHost($server, 'https://receitas.gcruz.dev.br', null));
        $this->assertNull(PublicOrigin::redirectFromOldHost($server, 'receitas.gcruz.dev.br', 'portal-receitas.onrender.com'));
    }

    public function testCaminhoComDuasBarrasNaoViraOutroHost(): void
    {
        $server = ['HTTP_HOST' => 'portal-receitas.onrender.com', 'REQUEST_URI' => '//evil.example/x'];

        $this->assertSame(
            'https://receitas.gcruz.dev.br/evil.example/x',
            PublicOrigin::redirectFromOldHost($server, 'https://receitas.gcruz.dev.br', 'portal-receitas.onrender.com')
        );
    }

    #[DataProvider('appUrlsInvalidas')]
    public function testAppUrlInvalidaEhIgnorada(string $appUrl): void
    {
        $server = ['HTTP_HOST' => 'localhost:8080'];

        $this->assertSame('http://localhost:8080', PublicOrigin::resolve($server, $appUrl));
    }

    public static function appUrlsInvalidas(): array
    {
        return [
            'sem esquema' => ['receitas.gcruz.dev.br'],
            'esquema estranho' => ['ftp://receitas.gcruz.dev.br'],
            'com caminho' => ['https://receitas.gcruz.dev.br/portal'],
            'com query' => ['https://receitas.gcruz.dev.br/?a=1'],
            'host inválido' => ['https://rece<itas>.dev'],
        ];
    }

    public function testAppUrlComPortaEMaiusculas(): void
    {
        $this->assertSame('http://localhost:8080', PublicOrigin::resolve([], 'HTTP://LocalHost:8080'));
    }
}
