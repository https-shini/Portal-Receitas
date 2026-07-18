<?php

declare(strict_types=1);

namespace App\Application\Support;

/**
 * Gera slugs amigáveis (SEO) para as URLs de receita: /receita/{id}/{slug}.
 * O slug é cosmético — a busca usa apenas o id —, então acentos são
 * transliterados sem depender da extensão intl.
 */
final class Slug
{
    private const ACENTOS = [
        'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a', 'ä' => 'a',
        'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
        'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
        'ó' => 'o', 'ò' => 'o', 'õ' => 'o', 'ô' => 'o', 'ö' => 'o',
        'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
        'ç' => 'c', 'ñ' => 'n',
    ];

    public static function make(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');
        $text = strtr($text, self::ACENTOS);
        $text = (string) preg_replace('/[^a-z0-9]+/', '-', $text);
        $text = trim($text, '-');

        return $text !== '' ? $text : 'receita';
    }
}
