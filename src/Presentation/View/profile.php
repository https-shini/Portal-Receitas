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
<?php
$pageTitle = 'Meu perfil · HomeMadeGourmet';
$pageDescription = 'Gerencie seus dados no HomeMadeGourmet: nome, e-mail e senha.';
$pageCss = ['pages/profile.css'];
$robotsNoindex = true;
require __DIR__ . '/partials/head.php';
?>
</head>
<body>
<?php $isLogged = true; require __DIR__ . '/partials/header.php'; ?>

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

<?php require __DIR__ . '/partials/footer.php'; ?>

    <script src="./assets/js/theme.js" defer></script>
    <script src="./assets/js/profile.js" defer></script>
</body>
</html>
