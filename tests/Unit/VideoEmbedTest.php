<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Application\Support\VideoEmbed;
use PHPUnit\Framework\TestCase;

/**
 * Preparação do embed de vídeo: domínio de privacidade reforçada e lazy load.
 */
class VideoEmbedTest extends TestCase
{
    public function testForcesNocookieDomain(): void
    {
        $html = VideoEmbed::prepare('<iframe src="https://www.youtube.com/embed/abc"></iframe>');

        $this->assertStringContainsString('youtube-nocookie.com/embed/abc', $html);
        $this->assertStringNotContainsString('www.youtube.com/embed', $html);
    }

    public function testAddsLazyLoading(): void
    {
        $html = VideoEmbed::prepare('<iframe src="https://www.youtube-nocookie.com/embed/abc"></iframe>');

        $this->assertStringContainsString('<iframe loading="lazy" ', $html);
    }

    public function testKeepsAlreadyNocookieUntouchedExceptLazy(): void
    {
        $html = VideoEmbed::prepare('<iframe src="https://youtube-nocookie.com/embed/xyz"></iframe>');

        $this->assertStringContainsString('youtube-nocookie.com/embed/xyz', $html);
    }
}
