<?php

declare(strict_types=1);

/**
 * Sitemap dinâmico (servido em /sitemap.xml via .htaccess). Lista as páginas
 * estáticas públicas e uma URL por receita (/receita/{id}/{slug}). Banco fora
 * → apenas as páginas estáticas, ainda 200.
 */

use App\Application\Support\Slug;

$services = require __DIR__ . '/../config/bootstrap.php';

header('Content-Type: application/xml; charset=utf-8');

$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
$base = ($https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');

$urls = [
    ['loc' => '/index.php', 'freq' => 'daily', 'pri' => '1.0'],
    ['loc' => '/privacidade.php', 'freq' => 'yearly', 'pri' => '0.3'],
    ['loc' => '/termos.php', 'freq' => 'yearly', 'pri' => '0.3'],
    ['loc' => '/login.php', 'freq' => 'yearly', 'pri' => '0.4'],
    ['loc' => '/register.php', 'freq' => 'yearly', 'pri' => '0.4'],
];

try {
    foreach ($services['recipeController']->sitemapEntries() as $recipe) {
        $urls[] = [
            'loc' => sprintf('/receita/%d/%s', $recipe['id'], Slug::make($recipe['name'])),
            'freq' => 'monthly',
            'pri' => '0.8',
        ];
    }
} catch (PDOException) {
    // Sem banco: mantém apenas as páginas estáticas.
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as $url) {
    echo "    <url>\n";
    echo '        <loc>' . htmlspecialchars($base . $url['loc']) . "</loc>\n";
    echo '        <changefreq>' . $url['freq'] . "</changefreq>\n";
    echo '        <priority>' . $url['pri'] . "</priority>\n";
    echo "    </url>\n";
}
echo '</urlset>' . "\n";
