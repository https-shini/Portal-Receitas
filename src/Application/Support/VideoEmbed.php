<?php

declare(strict_types=1);

namespace App\Application\Support;

/**
 * Prepara o embed de vídeo vindo do banco: força o domínio de privacidade
 * reforçada (youtube-nocookie.com — cobre bancos semeados por versões antigas
 * do seed) e adiciona lazy loading, sem alterar o restante do HTML.
 *
 * O iframe só é inserido no DOM após consentimento do usuário (LGPD); ver
 * assets/js/recipe.js.
 */
final class VideoEmbed
{
    public static function prepare(string $html): string
    {
        $html = str_ireplace(
            ['www.youtube.com/embed', 'youtube.com/embed'],
            ['www.youtube-nocookie.com/embed', 'youtube-nocookie.com/embed'],
            $html,
        );

        return str_ireplace('<iframe ', '<iframe loading="lazy" ', $html);
    }
}
