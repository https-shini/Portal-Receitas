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
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= $initialPanel === 'register' ? 'Registro' : 'Login' ?></title>
        <link rel="stylesheet" href="https://maxst.icons8.com/vue-static/landings/line-awesome/line-awesome/1.3.0/css/line-awesome.min.css">
        <link rel="stylesheet" href="./assets/css/auth.css">
    </head>
    <body>
        <main class="main-auth">
            <div class="auth-container<?= $initialPanel === 'register' ? ' active' : '' ?>" id="auth-container">

                <!-- ── Formulário de Cadastro ── -->
                <div class="form-container sign-up" aria-label="Formulário de cadastro">
                    <form class="auth-form" onsubmit="return false;" novalidate>
                        <h1>Cadastro</h1>

                        <div class="input-box">
                            <i class="las la-user ib-icon" aria-hidden="true"></i>
                            <input type="text" id="reg-nome" placeholder="Nome de usuário" autocomplete="name" aria-label="Nome de usuário">
                        </div>

                        <div class="input-box">
                            <i class="las la-envelope ib-icon" aria-hidden="true"></i>
                            <input type="email" id="reg-email" placeholder="Email" autocomplete="email" aria-label="Email">
                        </div>

                        <div class="input-box">
                            <i class="las la-lock ib-icon" aria-hidden="true"></i>
                            <input type="password" id="reg-senha" placeholder="Senha (mín. 8, letra e número)" autocomplete="new-password" aria-label="Senha">
                            <button type="button" class="eye-btn" onclick="toggleEye('reg-senha', this)" aria-label="Mostrar/ocultar senha">
                                <i class="las la-eye" aria-hidden="true"></i>
                            </button>
                        </div>

                        <!-- Força da senha (5 níveis) -->
                        <div class="strength-wrap" aria-live="polite" aria-label="Força da senha">
                            <div class="strength-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100">
                                <div class="strength-fill" id="strength-fill"></div>
                            </div>
                            <span class="strength-label" id="strength-label"></span>
                        </div>

                        <div class="input-box">
                            <i class="las la-lock ib-icon" aria-hidden="true"></i>
                            <input type="password" id="reg-senha2" placeholder="Confirme sua senha" autocomplete="new-password" aria-label="Confirme sua senha">
                            <button type="button" class="eye-btn" onclick="toggleEye('reg-senha2', this)" aria-label="Mostrar/ocultar senha">
                                <i class="las la-eye" aria-hidden="true"></i>
                            </button>
                        </div>

                        <p class="cat-title">Qual é a sua categoria favorita?</p>
                        <div class="cat-grid" role="radiogroup" aria-label="Categoria favorita">
                            <?php foreach ($categorias as $id => $categoria): ?>
                                <label class="cat-chip">
                                    <input type="radio" name="categoria" value="<?= $id ?>">
                                    <span class="cat-chip-body">
                                        <img src="./assets/img/<?= htmlspecialchars($categoria[0]) ?>" alt="">
                                        <span><?= htmlspecialchars($categoria[1]) ?></span>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>

                        <div class="alert" id="reg-alert" role="alert" aria-live="polite"></div>

                        <button type="button" class="btn-main" id="btn-reg" onclick="registerUser()">CADASTRAR</button>

                        <p class="mob-switch">Já tem conta? <a href="#" class="text-link" id="btnL-mob">Faça Login</a></p>
                    </form>
                </div>

                <!-- ── Formulário de Login ── -->
                <div class="form-container sign-in" aria-label="Formulário de login">
                    <form class="auth-form" onsubmit="return false;" novalidate>
                        <h1>Login</h1>

                        <div class="input-box">
                            <i class="las la-envelope ib-icon" aria-hidden="true"></i>
                            <input type="email" id="log-email" placeholder="Email" autocomplete="email" aria-label="Email">
                        </div>

                        <div class="input-box">
                            <i class="las la-lock ib-icon" aria-hidden="true"></i>
                            <input type="password" id="log-senha" placeholder="Senha" autocomplete="current-password" aria-label="Senha">
                            <button type="button" class="eye-btn" onclick="toggleEye('log-senha', this)" aria-label="Mostrar/ocultar senha">
                                <i class="las la-eye" aria-hidden="true"></i>
                            </button>
                        </div>

                        <div class="alert<?= $erroLogin ? ' show error' : '' ?>" id="log-alert" role="alert" aria-live="polite"><?= $erroLogin ? 'Você precisa logar para entrar no site' : '' ?></div>

                        <button type="button" class="btn-main" id="btn-log" onclick="loginUser()">CONECTAR</button>

                        <p class="mob-switch">Não tem conta? <a href="#" class="text-link" id="btnR-mob">Inscreva-se</a></p>
                    </form>
                </div>

                <!-- ── Painel deslizante (desktop) ── -->
                <div class="toggle-container" aria-hidden="true">
                    <div class="toggle-panel-wrap">
                        <div class="toggle-panel toggle-left">
                            <h2>Já tem conta?</h2>
                            <p>Faça login para continuar sua jornada culinária no HomeMadeGourmet.</p>
                            <button class="btn-toggle" id="btn-show-login">FAÇA LOGIN</button>
                        </div>
                        <div class="toggle-panel toggle-right">
                            <h2>Novo por aqui?</h2>
                            <p>Inscreva-se para descobrir receitas feitas para o seu gosto.</p>
                            <button class="btn-toggle" id="btn-show-register">INSCREVA-SE</button>
                        </div>
                    </div>
                </div>

            </div>
        </main>

        <script src="./assets/js/script-auth.js"></script>
    </body>
</html>
