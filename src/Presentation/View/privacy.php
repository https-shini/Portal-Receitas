<?php
/**
 * Política de Privacidade (LGPD) — documento versionado exibido em
 * /privacidade.php. Ao alterar o conteúdo, incremente a versão em
 * AuthController::LEGAL_VERSION e a data abaixo.
 */
$versaoDocumento = '1.0';
$atualizadoEm = '15/07/2026';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Política de Privacidade · HomeMadeGourmet</title>
    <meta name="description" content="Como o HomeMadeGourmet coleta, usa, protege e elimina seus dados pessoais, em conformidade com a LGPD.">
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
            <h1>Política de Privacidade</h1>
            <p class="legal-meta">Versão <?= htmlspecialchars($versaoDocumento) ?> · Última atualização: <?= htmlspecialchars($atualizadoEm) ?></p>

            <section aria-labelledby="pp-quem">
                <h2 id="pp-quem">1. Quem somos e como falar conosco</h2>
                <p>O <strong>HomeMadeGourmet</strong> é um portal de receitas de caráter acadêmico (TCC — ETEC de Vila Formosa). O responsável pelo tratamento de dados e canal de contato do titular é: <strong>Guilherme Cruz</strong> — <a href="mailto:guilhermedesouzacruz80@gmail.com">guilhermedesouzacruz80@gmail.com</a>. Solicitações de titulares são respondidas em até 15 dias.</p>
            </section>

            <section aria-labelledby="pp-dados">
                <h2 id="pp-dados">2. Quais dados coletamos e por quê</h2>
                <table>
                    <thead><tr><th>Dado</th><th>Quando</th><th>Finalidade</th><th>Base legal (LGPD)</th></tr></thead>
                    <tbody>
                        <tr><td>Nome de usuário</td><td>No cadastro</td><td>Identificar você no portal</td><td>Execução de contrato (art. 7º, V)</td></tr>
                        <tr><td>E-mail</td><td>No cadastro</td><td>Autenticação (login)</td><td>Execução de contrato (art. 7º, V)</td></tr>
                        <tr><td>Senha</td><td>No cadastro</td><td>Proteger sua conta — armazenada <strong>somente como hash bcrypt</strong>, nunca em texto legível</td><td>Execução de contrato (art. 7º, V)</td></tr>
                        <tr><td>Categoria favorita</td><td>No cadastro</td><td>Personalização do seu perfil</td><td>Execução de contrato (art. 7º, V)</td></tr>
                        <tr><td>Registros de autenticação e de alterações de conta</td><td>Durante o uso</td><td>Segurança e prevenção a fraudes</td><td>Legítimo interesse (art. 7º, IX)</td></tr>
                    </tbody>
                </table>
                <p>Praticamos <strong>minimização</strong>: nenhum outro dado pessoal é coletado. Não tratamos dados sensíveis nem realizamos decisões automatizadas ou perfilamento.</p>
            </section>

            <section aria-labelledby="pp-cookies">
                <h2 id="pp-cookies">3. Cookies e armazenamento local</h2>
                <p>Usamos apenas o estritamente necessário: um <strong>cookie de sessão</strong> (<code>PHPSESSID</code>, essencial, expira ao sair) para manter você conectado, e uma preferência de <strong>tema claro/escuro</strong> gravada no seu próprio navegador (localStorage), que nunca é enviada a servidores. Não usamos cookies de publicidade, análise ou rastreamento.</p>
            </section>

            <section aria-labelledby="pp-terceiros">
                <h2 id="pp-terceiros">4. Serviços de terceiros</h2>
                <p>Fontes e ícones são hospedados <strong>nos nossos próprios servidores</strong> — nenhuma requisição sua vai a provedores de fontes ou CDNs externos.</p>
                <p>Os vídeos das receitas são do <strong>YouTube</strong>. Eles <strong>não carregam automaticamente</strong>: o player (domínio de privacidade reforçada <code>youtube-nocookie.com</code>) só é carregado se você clicar em "Carregar vídeo" — nesse momento, seu endereço IP e dados de navegação são compartilhados com o Google, conforme a <a href="https://policies.google.com/privacy" rel="noopener noreferrer" target="_blank">política de privacidade do Google</a>.</p>
            </section>

            <section aria-labelledby="pp-retencao">
                <h2 id="pp-retencao">5. Por quanto tempo guardamos</h2>
                <p>Os dados da sua conta permanecem enquanto ela existir. Registros de segurança (auditoria de alterações de conta) são mantidos por até <strong>12 meses</strong> e então eliminados. Logs técnicos de autenticação são mantidos por até <strong>90 dias</strong>. Em ambiente de demonstração, os dados podem ser reiniciados periodicamente.</p>
            </section>

            <section aria-labelledby="pp-direitos">
                <h2 id="pp-direitos">6. Seus direitos (art. 18 da LGPD)</h2>
                <ul>
                    <li><strong>Acessar e corrigir</strong>: veja e edite nome, e-mail e senha na página <a href="profile.php">Meu perfil</a>;</li>
                    <li><strong>Excluir</strong>: elimine sua conta a qualquer momento em <a href="profile.php">Meu perfil → Excluir conta</a> — seus dados são anonimizados de forma irreversível e deixam de ser dados pessoais;</li>
                    <li><strong>Portabilidade, informação sobre compartilhamento e demais direitos</strong>: solicite pelo canal de contato da seção 1.</li>
                </ul>
            </section>

            <section aria-labelledby="pp-seguranca">
                <h2 id="pp-seguranca">7. Como protegemos seus dados</h2>
                <p>Senhas com hash bcrypt; comunicação por HTTPS; consultas 100% parametrizadas (anti-injeção); cookie de sessão inacessível a scripts (HttpOnly) com proteção anti-CSRF; limitação de tentativas de login (anti força bruta); acesso ao banco sob menor privilégio; e trilha de auditoria de todas as alterações de conta.</p>
            </section>

            <section aria-labelledby="pp-alteracoes">
                <h2 id="pp-alteracoes">8. Alterações desta política</h2>
                <p>Alterações relevantes serão publicadas nesta página com nova versão e data. O histórico do documento é mantido no repositório do projeto.</p>
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
