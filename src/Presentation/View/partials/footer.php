<?php
/**
 * Rodapé global. Presente em todas as páginas.
 * Estilo em assets/css/layout.css (carregado globalmente em head.php).
 * Inclui assets/js/header.js — comportamento do header/menu do usuário,
 * ativo em toda página por estar num partial global.
 */
$anoAtual = date('Y');
$emailContato = 'receitasdelicia498@gmail.com';
?>
<footer class="site-footer" role="contentinfo">
    <div class="container site-footer__top">
        <div class="site-footer__brand">
            <a class="brand" href="index.php" aria-label="HomeMadeGourmet — início">
                <img src="./assets/img/logoIcon.png" alt="" width="32" height="32">
                <span class="brand__name">HomeMadeGourmet</span>
            </a>
            <p class="site-footer__tagline">Receitas caseiras de família — busque por ingrediente, filtre por categoria e cozinhe com vídeo passo a passo.</p>
        </div>

        <div class="site-footer__col">
            <h2 class="site-footer__col-title">Navegação</h2>
            <nav class="site-footer__nav-list" aria-label="Links do rodapé">
                <a href="index.php"><i class="las la-utensils" aria-hidden="true"></i> Receitas</a>
                <a href="privacidade.php"><i class="las la-shield-alt" aria-hidden="true"></i> Política de Privacidade</a>
                <a href="termos.php"><i class="las la-file-alt" aria-hidden="true"></i> Termos de Uso</a>
            </nav>
        </div>

        <div class="site-footer__col">
            <h2 class="site-footer__col-title">Contato</h2>
            <address class="site-footer__address">
                <a class="btn btn--soft site-footer__contact-cta" href="mailto:<?= htmlspecialchars($emailContato) ?>">
                    <i class="las la-envelope" aria-hidden="true"></i> Fale conosco
                </a>
                <a class="site-footer__contact-email" href="mailto:<?= htmlspecialchars($emailContato) ?>">
                    <?= htmlspecialchars($emailContato) ?>
                </a>
                <span class="site-footer__contact-location">
                    <i class="las la-map-marker-alt" aria-hidden="true"></i> ETEC de Vila Formosa — São Paulo, SP
                </span>
            </address>
        </div>
    </div>

    <div class="site-footer__divider" role="separator"></div>

    <div class="container site-footer__bottom">
        <p class="site-footer__copyright">
            &copy; <time datetime="<?= htmlspecialchars($anoAtual) ?>"><?= htmlspecialchars($anoAtual) ?></time> HomeMadeGourmet — TCC ETEC de Vila Formosa.
        </p>
        <p class="site-footer__made">Feito com <i class="las la-heart site-footer__heart" aria-hidden="true"></i> e receitas de família.</p>
    </div>
</footer>

<script src="./assets/js/header.js" defer></script>
