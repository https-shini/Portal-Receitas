<?php

declare(strict_types=1);

namespace App\Application\Mapper;

/**
 * Traduz as linhas do banco (colunas em português do modelo herdado) para o
 * vocabulário das views. Fonte única do mapeamento — reutilizado pela
 * listagem do catálogo e pela página individual da receita.
 *
 * O nome da categoria vem do JOIN (`nomeCategoria`); não há mais rótulos
 * fixados em código.
 */
final class RecipeViewMapper
{
    private const SENTINELA_INGREDIENTE = 'Não há mais ingredientes';

    /** Projeção de card (resumo). */
    public static function summary(array $recipe): array
    {
        return [
            'id' => (int) $recipe['idReceita'],
            'name' => (string) $recipe['nomeReceita'],
            'time' => (string) $recipe['tempoReceita'],
            'difficulty' => self::texto($recipe, 'dificuldade'),
            'category' => self::categoria($recipe),
            'image' => (string) $recipe['imagem'],
            'rating' => self::rating($recipe),
        ];
    }

    /**
     * Projeção de detalhe (página/modal).
     *
     * Ingredientes: as 15 colunas ingrediente_N viram lista posicional; vagas
     * vazias recebem a sentinela legada (a view decide exibi-las ou não).
     *
     * Modo de preparo: o texto é quebrado em passos por ponto final —
     * comportamento herdado do site original. Limitação conhecida: pontos em
     * abreviações/números também quebram; aceito para manter paridade com o
     * conteúdo do seed, escrito para esse formato.
     */
    public static function detail(array $recipe): array
    {
        $ingredients = [];
        for ($index = 1; $index <= 15; $index++) {
            $value = trim((string) ($recipe['ingrediente_' . $index] ?? ''));
            $ingredients[] = $value !== '' ? $value : self::SENTINELA_INGREDIENTE;
        }

        $preparation = [];
        foreach (explode('.', (string) $recipe['modoPreparo']) as $index => $step) {
            $step = trim($step);
            if ($step === '') {
                continue;
            }
            $preparation[] = sprintf('%d. %s', $index, $step);
        }

        return [
            'id' => (int) $recipe['idReceita'],
            'name' => (string) $recipe['nomeReceita'],
            'video' => (string) $recipe['link'],
            'category' => self::categoria($recipe),
            'categoryId' => (int) ($recipe['idcategoriaFK'] ?? 0),
            'time' => (string) $recipe['tempoReceita'],
            'cookTime' => self::texto($recipe, 'tempoCozimento'),
            'difficulty' => self::texto($recipe, 'dificuldade'),
            'servings' => (int) $recipe['porcoes'],
            'calories' => (float) $recipe['qtdCalorias'],
            'image' => (string) ($recipe['imagem'] ?? ''),
            'tips' => self::texto($recipe, 'dicas'),
            'rating' => self::rating($recipe),
            'ingredients' => $ingredients,
            'preparation' => $preparation,
        ];
    }

    /**
     * Agregado de avaliações: média (1 casa) e contagem. average é null
     * quando ninguém avaliou (a view mostra "Sem avaliações ainda").
     *
     * @return array{average: float|null, count: int}
     */
    private static function rating(array $recipe): array
    {
        $count = (int) ($recipe['notaTotal'] ?? 0);

        return [
            'average' => $count > 0 && isset($recipe['notaMedia']) ? round((float) $recipe['notaMedia'], 1) : null,
            'count' => $count,
        ];
    }

    private static function categoria(array $recipe): string
    {
        $nome = trim((string) ($recipe['nomeCategoria'] ?? ''));

        return $nome !== '' ? $nome : 'Sem categoria';
    }

    /** Campo textual opcional: retorna null quando ausente/vazio. */
    private static function texto(array $recipe, string $coluna): ?string
    {
        $valor = trim((string) ($recipe[$coluna] ?? ''));

        return $valor !== '' ? $valor : null;
    }
}
