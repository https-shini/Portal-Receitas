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
            'category' => self::categoria($recipe),
            'image' => (string) $recipe['imagem'],
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
            'servings' => (int) $recipe['porcoes'],
            'calories' => (float) $recipe['qtdCalorias'],
            'image' => (string) ($recipe['imagem'] ?? ''),
            'ingredients' => $ingredients,
            'preparation' => $preparation,
        ];
    }

    private static function categoria(array $recipe): string
    {
        $nome = trim((string) ($recipe['nomeCategoria'] ?? ''));

        return $nome !== '' ? $nome : 'Sem categoria';
    }
}
