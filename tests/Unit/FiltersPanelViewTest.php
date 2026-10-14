<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Regressão do painel de filtros do catálogo.
 *
 * Contexto: o painel já era entregue com `hidden`, mas ficava permanentemente
 * visível porque `.filters-panel { display: grid }` vence a folha do navegador
 * para `[hidden]` — e o JS ainda o abria sozinho quando havia filtro aplicado.
 * Estes testes travam as duas pontas: o servidor sempre entrega o painel
 * oculto, e o CSS mantém a regra que faz `hidden` valer.
 */
class FiltersPanelViewTest extends TestCase
{
    /** @var list<array{id:int,name:string,icon:?string,total:int}> */
    private const CATEGORIAS = [
        ['id' => 2, 'name' => 'Massas', 'icon' => 'la-utensils', 'total' => 6],
        ['id' => 7, 'name' => 'Aves', 'icon' => 'la-feather-alt', 'total' => 0],
    ];

    /**
     * Renderiza o partial isolado, como a view do catálogo o inclui.
     *
     * @param array<string, mixed> $filters
     */
    private function render(array $filters): string
    {
        $categories = self::CATEGORIAS;

        ob_start();
        require __DIR__ . '/../../src/Presentation/View/partials/filters.php';

        return (string) ob_get_clean();
    }

    /** @return array<string, array{0: array<string, mixed>}> */
    public static function estadosDeFiltro(): array
    {
        return [
            'sem filtro algum' => [['search' => null, 'categoryIds' => [], 'difficulties' => [], 'sort' => 'relevancia']],
            'com categoria aplicada' => [['search' => null, 'categoryIds' => [2], 'difficulties' => [], 'sort' => 'relevancia']],
            'com dificuldade aplicada' => [['search' => null, 'categoryIds' => [], 'difficulties' => ['Fácil'], 'sort' => 'relevancia']],
            'com busca e tudo combinado' => [['search' => 'bacon', 'categoryIds' => [2], 'difficulties' => ['Fácil'], 'sort' => 'nome']],
        ];
    }

    /**
     * O estado inicial é fechado em QUALQUER combinação de filtros — era
     * exatamente aqui que o painel reaparecia sem o usuário pedir.
     *
     * @param array<string, mixed> $filters
     */
    #[DataProvider('estadosDeFiltro')]
    public function testPainelSempreChegaOcultoDoServidor(array $filters): void
    {
        $html = $this->render($filters);

        $this->assertMatchesRegularExpression(
            '/<div class="filters-panel"[^>]*\shidden\s*>/',
            $html,
            'O painel de filtros precisa ser entregue com o atributo hidden, independentemente dos filtros ativos.'
        );
    }

    /** O botão continua anunciando o estado fechado para leitores de tela. */
    public function testCategoriaSemReceitasVemDesabilitadaERecolhida(): void
    {
        $html = $this->render(['search' => null, 'categoryIds' => [], 'difficulties' => [], 'sort' => 'relevancia']);

        $this->assertStringContainsString('filter-chip filter-chip--empty', $html);
        $this->assertMatchesRegularExpression('/value="7"[^>]*disabled/', $html, 'Categoria com 0 receitas deve vir desabilitada.');
        $this->assertDoesNotMatchRegularExpression('/value="2"[^>]*disabled/', $html, 'Categoria com receitas não pode vir desabilitada.');
    }

    /** A seleção vinda da query string precisa sobreviver à renderização. */
    public function testCategoriaSelecionadaPermaneceMarcada(): void
    {
        $html = $this->render(['search' => null, 'categoryIds' => [2], 'difficulties' => [], 'sort' => 'relevancia']);

        $this->assertMatchesRegularExpression('/value="2"[^>]*checked/', $html);
    }

    /**
     * Guarda da causa raiz: sem esta regra, qualquer `display` de autor
     * volta a sobrepor o `hidden` e o painel reaparece na página.
     */
    public function testCssGaranteQueOAtributoHiddenPrevaleca(): void
    {
        $base = (string) file_get_contents(__DIR__ . '/../../public/assets/css/base.css');
        $this->assertMatchesRegularExpression(
            '/\[hidden\]\s*\{\s*display:\s*none\s*!important;\s*\}/',
            $base,
            'base.css precisa manter a regra global que faz o atributo hidden prevalecer.'
        );

        $catalog = (string) file_get_contents(__DIR__ . '/../../public/assets/css/pages/catalog.css');
        $this->assertStringContainsString(
            '.filters-panel[hidden]',
            $catalog,
            'catalog.css precisa manter a regra explícita de ocultação do painel.'
        );
    }

    /** O JS não pode voltar a inferir "aberto" a partir dos filtros marcados. */
    public function testJavaScriptNaoAbreOPainelSozinho(): void
    {
        $js = (string) file_get_contents(__DIR__ . '/../../public/assets/js/catalog.js');

        $this->assertStringContainsString('setOpen(false);', $js, 'O painel deve iniciar fechado.');
        $this->assertDoesNotMatchRegularExpression(
            '/setOpen\(\s*!!/',
            $js,
            'O estado inicial do painel não pode ser derivado dos checkboxes marcados.'
        );
    }
}
