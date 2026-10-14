<?php
/**
 * Partial de <head> compartilhado por todas as páginas (DRY).
 *
 * @var string      $pageTitle       Título da aba.
 * @var string      $pageDescription Meta description.
 * @var list<string> $pageCss        CSS específicos da página (nomes relativos a assets/css/).
 * @var bool        $robotsNoindex   true em páginas privadas (perfil, login, cadastro).
 * @var string      $extraHead       HTML adicional opcional (OG, JSON-LD).
 */
/**
 * URL de asset com "cache busting" pela data de modificação do arquivo.
 *
 * Os CSS/JS são servidos com `Cache-Control: max-age=3600` e nomes fixos
 * (docker/security-headers.conf). Sem um identificador de versão na URL, o
 * navegador reaproveita a cópia antiga por até uma hora depois de um deploy
 * — o usuário recebe o HTML novo com o CSS velho. O sufixo `?v=<mtime>` muda
 * sozinho quando o arquivo muda, invalidando o cache só quando necessário.
 */
if (!function_exists('hmgAsset')) {
    function hmgAsset(string $relativo): string
    {
        $relativo = ltrim($relativo, './');
        $absoluto = __DIR__ . '/../../../../public/' . $relativo;
        $versao = is_file($absoluto) ? (string) filemtime($absoluto) : '0';

        return './' . $relativo . '?v=' . $versao;
    }
}

$pageTitle = $pageTitle ?? 'HomeMadeGourmet';
$pageDescription = $pageDescription ?? 'Portal de receitas caseiras.';
$pageCss = $pageCss ?? [];
$robotsNoindex = $robotsNoindex ?? false;
$extraHead = $extraHead ?? '';
?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Base na raiz: mantém os caminhos relativos (./assets, index.php, ...)
         resolvendo a partir do docroot mesmo nas URLs amigáveis de receita
         (/receita/{id}/{slug}), que são servidas dois níveis abaixo. -->
    <base href="/">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <meta name="description" content="<?= htmlspecialchars($pageDescription) ?>">
    <?php if ($robotsNoindex): ?><meta name="robots" content="noindex"><?php endif; ?>
    <meta name="theme-color" content="#C2410C">
    <link rel="icon" href="./assets/img/logoIcon.png">
    <script>
        (function () {
            var t;
            try { t = localStorage.getItem("hmg_theme"); } catch (e) {}
            if (!t) t = window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
            document.documentElement.setAttribute("data-theme", t);
        })();
    </script>
    <link rel="stylesheet" href="<?= hmgAsset('assets/css/fonts.css') ?>">
    <link rel="stylesheet" href="./assets/vendor/line-awesome/css/line-awesome.min.css">
    <link rel="stylesheet" href="<?= hmgAsset('assets/css/tokens.css') ?>">
    <link rel="stylesheet" href="<?= hmgAsset('assets/css/base.css') ?>">
    <link rel="stylesheet" href="<?= hmgAsset('assets/css/components.css') ?>">
    <link rel="stylesheet" href="<?= hmgAsset('assets/css/layout.css') ?>">
    <?php foreach ($pageCss as $css): ?>
    <link rel="stylesheet" href="<?= htmlspecialchars(hmgAsset('assets/css/' . $css)) ?>">
    <?php endforeach; ?>
    <?= $extraHead ?>
