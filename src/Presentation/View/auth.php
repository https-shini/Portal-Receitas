<?php
/**
 * Tela de acesso — Login e Cadastro são páginas INDEPENDENTES (sem card duplo
 * sobreposto). $initialPanel decide qual formulário esta página renderiza;
 * a alternância é feita por navegação real (login.php ⇄ register.php).
 *
 * @var string $initialPanel  'login' | 'register'
 * @var bool   $erroLogin     Aviso de acesso restrito (redirecionado do guard)
 * @var bool   $cadastroOk    Sucesso de cadastro recém-realizado
 */
$initialPanel = $initialPanel ?? 'login';
$erroLogin = $erroLogin ?? false;
$cadastroOk = $cadastroOk ?? false;
$isRegister = $initialPanel === 'register';

$categorias = [
    1 => ['sushiIcone.png', 'Frutos do Mar'],
    2 => ['massaIcone.png', 'Massas'],
    3 => ['veganoIcone.png', 'Veganas'],
    4 => ['croassaIcone.png', 'Salgados'],
    5 => ['boloIcone.png', 'Doces'],
    6 => ['carneIcone.png', 'Carnes'],
];

$pageTitle = ($isRegister ? 'Cadastro' : 'Login') . ' · HomeMadeGourmet';
$pageDescription = 'Acesse o HomeMadeGourmet para gerenciar seu perfil e recursos personalizados. As receitas são públicas.';
$pageCss = ['pages/home.css', 'pages/auth.css'];
$robotsNoindex = true;
$isLogged = false;
$showLoginCta = false;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<?php require __DIR__ . '/partials/head.php'; ?>
</head>
<body>
<?php require __DIR__ . '/partials/header.php'; ?>

    <main id="conteudo" class="auth-main container" role="main">
        <section class="glass glass--strong auth-card" aria-labelledby="authTitulo">
            <div class="auth-card__head">
                <h1 id="authTitulo"><?= $isRegister ? 'Criar conta' : 'Entrar' ?></h1>
                <p><?= $isRegister
                        ? 'Crie sua conta para salvar favoritos e personalizar seu perfil.'
                        : 'Bem-vindo de volta! As receitas são públicas — entre para acessar seu perfil.' ?></p>
            </div>

            <?php if ($isRegister): ?>
                <!-- ══════════════ CADASTRO ══════════════ -->
                <form class="auth-form" onsubmit="return false;" novalidate>
                    <div class="field">
                        <label class="field__label" for="reg-nome">Nome de usuário</label>
                        <div class="field__control">
                            <i class="las la-user" aria-hidden="true"></i>
                            <input class="field__input" type="text" id="reg-nome" placeholder="Como quer ser chamado?" autocomplete="name">
                        </div>
                    </div>

                    <div class="field">
                        <label class="field__label" for="reg-email">Email</label>
                        <div class="field__control">
                            <i class="las la-envelope" aria-hidden="true"></i>
                            <input class="field__input" type="email" id="reg-email" placeholder="voce@exemplo.com" autocomplete="email">
                        </div>
                    </div>

                    <div class="field">
                        <label class="field__label" for="reg-senha">Senha</label>
                        <div class="field__control">
                            <i class="las la-lock" aria-hidden="true"></i>
                            <input class="field__input" type="password" id="reg-senha" placeholder="Mín. 8 caracteres, letra e número"
                                   autocomplete="new-password" aria-describedby="dicaSenha">
                            <button type="button" class="field__eye" onclick="toggleEye('reg-senha', this)" aria-label="Mostrar/ocultar senha">
                                <i class="las la-eye" aria-hidden="true"></i>
                            </button>
                        </div>
                        <div class="strength-wrap" aria-live="polite" id="dicaSenha">
                            <div class="strength-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-label="Força da senha">
                                <div class="strength-fill" id="strength-fill"></div>
                            </div>
                            <span class="strength-label" id="strength-label"></span>
                        </div>
                    </div>

                    <div class="field">
                        <label class="field__label" for="reg-senha2">Confirme sua senha</label>
                        <div class="field__control">
                            <i class="las la-lock" aria-hidden="true"></i>
                            <input class="field__input" type="password" id="reg-senha2" placeholder="Repita a senha" autocomplete="new-password">
                            <button type="button" class="field__eye" onclick="toggleEye('reg-senha2', this)" aria-label="Mostrar/ocultar senha">
                                <i class="las la-eye" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>

                    <fieldset class="cat-grid" aria-describedby="tituloCategoria">
                        <legend class="cat-title" id="tituloCategoria">Qual é a sua categoria favorita?</legend>
                        <?php foreach ($categorias as $id => $categoria): ?>
                            <label class="chip">
                                <input type="radio" name="categoria" value="<?= $id ?>">
                                <span class="chip__body">
                                    <img src="./assets/img/<?= htmlspecialchars($categoria[0]) ?>" alt="" width="28" height="28">
                                    <?= htmlspecialchars($categoria[1]) ?>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </fieldset>

                    <label class="legal-accept">
                        <input type="checkbox" id="reg-aceite">
                        <span>Li e aceito os <a href="termos.php" target="_blank" rel="noopener">Termos de Uso</a> e a <a href="privacidade.php" target="_blank" rel="noopener">Política de Privacidade</a>.</span>
                    </label>
                    <?php if (getenv('APP_DEMO_MODE')): ?>
                        <p class="auth-form__note">Ambiente de demonstração: os dados podem ser reiniciados periodicamente.</p>
                    <?php endif; ?>

                    <div class="alert" id="reg-alert" role="alert" aria-live="polite"></div>

                    <button type="button" class="btn btn--primary btn--block" id="btn-reg" onclick="registerUser()">CADASTRAR</button>

                    <p class="auth-switch">Já tem conta? <a href="login.php">Faça login</a></p>
                </form>
            <?php else: ?>
                <!-- ══════════════ LOGIN ══════════════ -->
                <form class="auth-form" onsubmit="return false;" novalidate>
                    <?php if ($cadastroOk): ?>
                        <div class="alert show alert--success" role="status">Cadastro realizado com sucesso! Faça login para continuar.</div>
                    <?php endif; ?>

                    <div class="field">
                        <label class="field__label" for="log-email">Email</label>
                        <div class="field__control">
                            <i class="las la-envelope" aria-hidden="true"></i>
                            <input class="field__input" type="email" id="log-email" placeholder="voce@exemplo.com" autocomplete="email">
                        </div>
                    </div>

                    <div class="field">
                        <label class="field__label" for="log-senha">Senha</label>
                        <div class="field__control">
                            <i class="las la-lock" aria-hidden="true"></i>
                            <input class="field__input" type="password" id="log-senha" placeholder="Sua senha" autocomplete="current-password">
                            <button type="button" class="field__eye" onclick="toggleEye('log-senha', this)" aria-label="Mostrar/ocultar senha">
                                <i class="las la-eye" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>

                    <div class="alert<?= $erroLogin ? ' show alert--error' : '' ?>" id="log-alert" role="alert" aria-live="polite"><?= $erroLogin ? 'Você precisa entrar para acessar essa área.' : '' ?></div>

                    <button type="button" class="btn btn--primary btn--block" id="btn-log" onclick="loginUser()">ENTRAR</button>

                    <p class="auth-switch">Não tem conta? <a href="register.php">Inscreva-se</a></p>
                    <p class="auth-switch"><a href="index.php">← Voltar às receitas</a></p>
                </form>
            <?php endif; ?>
        </section>
    </main>

<?php require __DIR__ . '/partials/footer.php'; ?>

    <script src="./assets/js/theme.js" defer></script>
    <script src="./assets/js/script-auth.js" defer></script>
</body>
</html>
