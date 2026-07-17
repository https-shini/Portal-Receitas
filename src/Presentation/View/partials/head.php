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
$pageTitle = $pageTitle ?? 'HomeMadeGourmet';
$pageDescription = $pageDescription ?? 'Portal de receitas caseiras.';
$pageCss = $pageCss ?? [];
$robotsNoindex = $robotsNoindex ?? false;
$extraHead = $extraHead ?? '';
?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
    <link rel="stylesheet" href="./assets/css/fonts.css">
    <link rel="stylesheet" href="./assets/vendor/line-awesome/css/line-awesome.min.css">
    <link rel="stylesheet" href="./assets/css/tokens.css">
    <link rel="stylesheet" href="./assets/css/base.css">
    <link rel="stylesheet" href="./assets/css/components.css">
    <?php foreach ($pageCss as $css): ?>
    <link rel="stylesheet" href="./assets/css/<?= htmlspecialchars($css) ?>">
    <?php endforeach; ?>
    <?= $extraHead ?>
