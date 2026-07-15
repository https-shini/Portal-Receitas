<?php
include_once __DIR__ . "/assets/php/conexao.php";
include_once __DIR__ . "/assets/php/usuarioController.php";

session_start();

$user = new usuarioController();
$erroLogin = !empty($_GET['erro']);
$erroCredenciais = false;

if (isset($_POST['enviar'])) {
    $email = trim($_POST['email'] ?? '');
    $senha = trim($_POST['pswrd'] ?? '');
    $retorno = $user->login($email, $senha);

    if (!empty($retorno)) {
        $_SESSION['emailSessao'] = $retorno[1];
        $_SESSION['nomeSessao'] = $retorno[3];
        $_SESSION['idUsuario'] = $retorno[4];
        $_SESSION['logado'] = true;

        header('Location: index.php');
        exit;
    } else {
        $erroCredenciais = true;
    }
}
?>


<!DOCTYPE html>
<html lang="pt-br">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Login</title>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@48,400,0,0" />
        <link rel="stylesheet" href="./assets/css/login.css">
    </head>
    <body>

        <?php if ($erroLogin): ?>
            <script>alert("Você precisa logar para entrar no site")</script>
        <?php endif; ?>

        <?php if ($erroCredenciais): ?>
            <script>alert("Senha ou email incorretos!")</script>
        <?php endif; ?>

        <div class="container">
            <header>
                <h1>Login</h1>
            </header>
            <form action="" method="POST">
                <div class="input-field">
                    <input type="text" name="email" required>
                    <label>Email</label>
                </div>
                <div class="input-field">
                    <input class="pswrd" name="pswrd" type="password" required>
                     <span class="material-symbols-rounded show" id="show">visibility</span>
                    <label>Senha</label>
                </div>
                <div class="button">
                    <input value="CONECTAR" name="enviar" type="submit">
                </div>
                <div class="signup">
                    Não tem conta? <a href="register.php">Inscreva-se</a>
                </div>

            </form>
        </div>
        <script src="./assets/js/script-login.js"></script>
    </body>
</html>
