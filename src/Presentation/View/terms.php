<?php
/**
 * Termos de Uso — documento versionado exibido em /termos.php.
 * Ao alterar o conteúdo, incremente a versão em AuthController::LEGAL_VERSION
 * e a data abaixo.
 */
$versaoDocumento = '1.0';
$atualizadoEm = '15/07/2026';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Termos de Uso · HomeMadeGourmet</title>
    <meta name="description" content="Condições de uso do portal de receitas HomeMadeGourmet.">
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
    <link rel="stylesheet" href="./assets/css/pages/home.css">
    <link rel="stylesheet" href="./assets/css/pages/legal.css">
</head>
<body>
    <a class="skip-link" href="#conteudo">Pular para o conteúdo</a>

    <header class="site-header" role="banner">
        <div class="container site-header__row">
            <a class="brand" href="index.php" aria-label="HomeMadeGourmet — início">
                <img src="./assets/img/logoIcon.png" alt="" width="36" height="36">
                <span class="brand__name">HomeMadeGourmet</span>
            </a>
            <a class="btn btn--ghost" href="index.php" style="margin-left:auto;">
                <i class="las la-home" aria-hidden="true"></i> Início
            </a>
            <button type="button" class="btn btn--ghost btn--icon js-theme-toggle" aria-label="Alternar tema claro/escuro" aria-pressed="false">
                <i class="las la-moon" aria-hidden="true"></i>
            </button>
        </div>
    </header>

    <main id="conteudo" class="legal-main container" role="main">
        <article class="glass glass--strong legal-card">
            <h1>Termos de Uso</h1>
            <p class="legal-meta">Versão <?= htmlspecialchars($versaoDocumento) ?> · Última atualização: <?= htmlspecialchars($atualizadoEm) ?></p>

            <section aria-labelledby="tu-servico">
                <h2 id="tu-servico">1. O serviço</h2>
                <p>O HomeMadeGourmet é um portal gratuito de receitas culinárias, de caráter acadêmico e demonstrativo (TCC — ETEC de Vila Formosa). O catálogo é aberto; a criação de conta habilita a personalização do perfil.</p>
            </section>

            <section aria-labelledby="tu-conta">
                <h2 id="tu-conta">2. Sua conta</h2>
                <p>Ao criar uma conta você declara fornecer informações verdadeiras e manter a confidencialidade da sua senha. Você pode editar seus dados ou <strong>excluir sua conta</strong> a qualquer momento na página Meu perfil. Em ambiente de demonstração, os dados podem ser reiniciados periodicamente.</p>
            </section>

            <section aria-labelledby="tu-uso">
                <h2 id="tu-uso">3. Uso aceitável</h2>
                <p>É vedado usar o portal para fins ilícitos, tentar acessar contas de terceiros, burlar mecanismos de segurança ou sobrecarregar o serviço. Tentativas nesse sentido podem ser limitadas automaticamente (bloqueio temporário) e registradas.</p>
            </section>

            <section aria-labelledby="tu-conteudo">
                <h2 id="tu-conteudo">4. Conteúdo e propriedade</h2>
                <p>As receitas e vídeos referenciados pertencem aos seus autores originais (os vídeos são exibidos via YouTube, mediante seu clique). O código do portal é distribuído sob licença MIT.</p>
            </section>

            <section aria-labelledby="tu-privacidade">
                <h2 id="tu-privacidade">5. Privacidade</h2>
                <p>O tratamento dos seus dados pessoais é descrito na <a href="privacidade.php">Política de Privacidade</a>, que integra estes Termos.</p>
            </section>

            <section aria-labelledby="tu-resp">
                <h2 id="tu-resp">6. Isenções e alterações</h2>
                <p>Por seu caráter acadêmico, o serviço é fornecido "como está", sem garantias de disponibilidade contínua. Estes Termos podem ser atualizados; alterações relevantes serão publicadas nesta página com nova versão e data.</p>
            </section>

            <section aria-labelledby="tu-contato">
                <h2 id="tu-contato">7. Contato</h2>
                <p>Dúvidas sobre estes Termos: <a href="mailto:guilhermedesouzacruz80@gmail.com">guilhermedesouzacruz80@gmail.com</a>.</p>
            </section>
        </article>
    </main>

    <footer class="site-footer" role="contentinfo">
        <div class="container">
            <p style="margin-inline:auto;">HomeMadeGourmet — TCC ETEC de Vila Formosa · <a href="privacidade.php">Privacidade</a> · <a href="termos.php">Termos de Uso</a></p>
        </div>
    </footer>

    <script src="./assets/js/theme.js" defer></script>
</body>
</html>
