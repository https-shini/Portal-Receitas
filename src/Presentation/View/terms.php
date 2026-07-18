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
<?php
$pageTitle = 'Termos de Uso · HomeMadeGourmet';
$pageDescription = 'Condições de uso do portal de receitas HomeMadeGourmet.';
$pageCss = ['pages/legal.css'];
require __DIR__ . '/partials/head.php';
?>
</head>
<body>
<?php require __DIR__ . '/partials/header.php'; ?>

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
                <p>Dúvidas sobre estes Termos: <a href="mailto:receitasdelicia498@gmail.com">receitasdelicia498@gmail.com</a>.</p>
            </section>
        </article>
    </main>

<?php require __DIR__ . '/partials/footer.php'; ?>

    <script src="./assets/js/theme.js" defer></script>
</body>
</html>
