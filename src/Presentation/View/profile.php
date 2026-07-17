<?php
/** @var array $viewData */
$nome = $viewData['nome'] ?? '';
$email = $viewData['email'] ?? '';
$erroAtualizacao = $viewData['erroAtualizacao'] ?? false;
$csrf = $viewData['csrf'] ?? '';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil · HomeMadeGourmet</title>
    <meta name="description" content="Gerencie seus dados no HomeMadeGourmet: nome, e-mail e senha.">
    <meta name="robots" content="noindex">
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
    <link rel="stylesheet" href="./assets/css/pages/profile.css">
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

    <main id="conteudo" class="profile-main container" role="main">
        <section class="glass glass--strong profile-card" aria-labelledby="tituloPerfil">
            <div class="profile-card__head">
                <span class="profile-card__avatar" aria-hidden="true"><i class="las la-user"></i></span>
                <h1 id="tituloPerfil">Meu perfil</h1>
                <p>Clique em <strong>Editar dados</strong> para alterar nome, e-mail ou senha. Após salvar, você fará login novamente.</p>
            </div>

            <div class="alert<?= $erroAtualizacao ? ' show alert--error' : '' ?>" id="alertaPerfil" role="alert" aria-live="polite"><?= $erroAtualizacao ? 'Ocorreu um erro ao tentar atualizar. Confira os dados (senha nova: mín. 8 caracteres com letra e número).' : '' ?></div>

            <form action="profile.php" method="POST" id="formPerfil">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string) $csrf) ?>">
                <div class="field" style="margin-bottom: var(--space-3);">
                    <label class="field__label" for="nome">Nome</label>
                    <div class="field__control">
                        <i class="las la-user" aria-hidden="true"></i>
                        <input class="field__input" disabled type="text" name="nome" id="nome"
                               value="<?= htmlspecialchars((string) $nome) ?>" autocomplete="name">
                    </div>
                </div>

                <div class="field" style="margin-bottom: var(--space-3);">
                    <label class="field__label" for="email">E-mail</label>
                    <div class="field__control">
                        <i class="las la-envelope" aria-hidden="true"></i>
                        <input class="field__input" disabled type="email" name="email" id="email"
                               value="<?= htmlspecialchars((string) $email) ?>" autocomplete="email">
                    </div>
                </div>

                <div class="field">
                    <label class="field__label" for="senha">Nova senha</label>
                    <div class="field__control">
                        <i class="las la-lock" aria-hidden="true"></i>
                        <input class="field__input" disabled type="password" name="senha" id="senha"
                               placeholder="Deixe em branco para não alterar" autocomplete="new-password"
                               aria-describedby="dicaSenhaPerfil">
                    </div>
                    <p class="field__hint" id="dicaSenhaPerfil">Mínimo de 8 caracteres, com pelo menos uma letra e um número.</p>
                </div>

                <div class="profile-card__actions">
                    <button class="btn btn--ghost" id="alterDataButton" type="button">
                        <i class="las la-pen" aria-hidden="true"></i> EDITAR DADOS
                    </button>
                    <button class="btn btn--primary" type="submit" name="salvar" id="btnSalvar" disabled>
                        <i class="las la-save" aria-hidden="true"></i> SALVAR
                    </button>
                </div>
            </form>

            <!-- ── Exclusão de conta (LGPD art. 18, VI) ── -->
            <details class="danger-zone">
                <summary><i class="las la-user-slash" aria-hidden="true"></i> Excluir minha conta</summary>
                <div class="danger-zone__body">
                    <p>A exclusão é <strong>permanente</strong>: seus dados são anonimizados de forma irreversível e você não poderá mais entrar com esta conta. Para confirmar, digite sua senha atual.</p>
                    <div class="field">
                        <label class="field__label" for="senhaExclusao">Senha atual</label>
                        <div class="field__control">
                            <i class="las la-lock" aria-hidden="true"></i>
                            <input class="field__input" type="password" id="senhaExclusao" autocomplete="current-password">
                        </div>
                    </div>
                    <div class="alert" id="alertaExclusao" role="alert" aria-live="polite"></div>
                    <button type="button" class="btn btn--danger" id="btnExcluirConta">
                        <i class="las la-trash" aria-hidden="true"></i> EXCLUIR CONTA DEFINITIVAMENTE
                    </button>
                </div>
            </details>
        </section>
    </main>

    <footer class="site-footer" role="contentinfo">
        <div class="container">
            <p style="margin-inline:auto;">HomeMadeGourmet — TCC ETEC de Vila Formosa.<br>
            <a href="privacidade.php">Política de Privacidade</a> · <a href="termos.php">Termos de Uso</a></p>
        </div>
    </footer>

    <script src="./assets/js/theme.js" defer></script>
    <script src="./assets/js/profile.js" defer></script>
</body>
</html>
