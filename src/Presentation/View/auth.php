<?php
/** @var string $initialPanel 'login' | 'register' */
/** @var bool $erroLogin Exibe aviso de acesso restrito (redirecionado do guard) */
$initialPanel = $initialPanel ?? 'login';
$erroLogin = $erroLogin ?? false;
$categorias = [
    1 => ['sushiIcone.png', 'Frutos do Mar'],
    2 => ['massaIcone.png', 'Massas'],
    3 => ['veganoIcone.png', 'Veganas'],
    4 => ['croassaIcone.png', 'Salgados'],
    5 => ['boloIcone.png', 'Doces'],
    6 => ['carneIcone.png', 'Carnes'],
];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $initialPanel === 'register' ? 'Registro' : 'Login' ?> · HomeMadeGourmet</title>
    <meta name="description" content="Acesse o HomeMadeGourmet: faça login ou crie sua conta para explorar receitas caseiras por ingrediente e categoria.">
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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Sora:wght@600;700&display=swap">
    <link rel="stylesheet" href="https://maxst.icons8.com/vue-static/landings/line-awesome/line-awesome/1.3.0/css/line-awesome.min.css">
    <link rel="stylesheet" href="./assets/css/tokens.css">
    <link rel="stylesheet" href="./assets/css/base.css">
    <link rel="stylesheet" href="./assets/css/components.css">
    <link rel="stylesheet" href="./assets/css/pages/auth.css">
</head>
<body>
    <a class="skip-link" href="#acesso">Pular para o formulário</a>

    <button type="button" class="btn btn--ghost btn--icon js-theme-toggle auth-theme-toggle glass" aria-label="Alternar tema claro/escuro" aria-pressed="false">
        <i class="las la-moon" aria-hidden="true"></i>
    </button>

    <main class="main-auth" id="acesso" role="main">
        <div class="auth-container<?= $initialPanel === 'register' ? ' active' : '' ?>" id="auth-container">

            <!-- ── Cadastro ── -->
            <section class="form-container sign-up" aria-label="Formulário de cadastro">
                <form class="auth-form" onsubmit="return false;" novalidate>
                    <h1>Cadastro</h1>
                    <p class="auth-form__sub">Crie sua conta para descobrir novas receitas.</p>

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

                    <div class="alert" id="reg-alert" role="alert" aria-live="polite"></div>

                    <button type="button" class="btn btn--primary" id="btn-reg" onclick="registerUser()">CADASTRAR</button>

                    <p class="mob-switch">Já tem conta? <a href="#" id="btnL-mob">Faça Login</a></p>
                </form>
            </section>

            <!-- ── Login ── -->
            <section class="form-container sign-in" aria-label="Formulário de login">
                <form class="auth-form" onsubmit="return false;" novalidate>
                    <h1>Login</h1>
                    <p class="auth-form__sub">Bem-vindo de volta! Entre para continuar.</p>

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

                    <div class="alert<?= $erroLogin ? ' show alert--error' : '' ?>" id="log-alert" role="alert" aria-live="polite"><?= $erroLogin ? 'Você precisa logar para entrar no site' : '' ?></div>

                    <button type="button" class="btn btn--primary" id="btn-log" onclick="loginUser()">CONECTAR</button>

                    <p class="mob-switch">Não tem conta? <a href="#" id="btnR-mob">Inscreva-se</a></p>
                </form>
            </section>

            <!-- ── Painel deslizante (desktop) ── -->
            <div class="toggle-container" aria-hidden="true">
                <div class="toggle-panel-wrap">
                    <div class="toggle-panel toggle-left">
                        <h2>Já tem conta?</h2>
                        <p>Faça login para continuar sua jornada culinária no HomeMadeGourmet.</p>
                        <button class="btn btn-toggle" id="btn-show-login" tabindex="-1">FAÇA LOGIN</button>
                    </div>
                    <div class="toggle-panel toggle-right">
                        <h2>Novo por aqui?</h2>
                        <p>Inscreva-se para descobrir receitas feitas para o seu gosto.</p>
                        <button class="btn btn-toggle" id="btn-show-register" tabindex="-1">INSCREVA-SE</button>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <script src="./assets/js/theme.js" defer></script>
    <script src="./assets/js/script-auth.js" defer></script>
</body>
</html>
