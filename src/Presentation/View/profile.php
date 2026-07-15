<?php
/** @var array $viewData */
$nome = $viewData['nome'] ?? '';
$email = $viewData['email'] ?? '';
$erroAtualizacao = $viewData['erroAtualizacao'] ?? false;
?>
<!DOCTYPE html>
<html lang="pt-br">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Perfil</title>
        <link rel="stylesheet" href="./assets/css/profile.css">
    </head>
    <body>
        <?php if ($erroAtualizacao): ?>
            <script>alert("Ocorreu um erro ao tentar atualizar")</script>
        <?php endif; ?>
        <div class="container">
            <header>
                <div class="logoTitulo">
                    <svg width="52" height="52" viewBox="0 0 52 52" fill="none" xmlns="http://www.w3.org/2000/svg"></svg>
                    <h1>HomeMadeGourmet</h1>
                </div>
                <div class="usuarioInicio">
                    <a href="index.php" style="text-decoration: none;"><h2>Início</h2></a>
                    <svg alt="Icone de foto do usuário" width="52" height="52" viewBox="0 0 52 52" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M35.75 19.5C35.75 22.0859 34.7228 24.5658 32.8943 26.3943C31.0658 28.2228 28.5859 29.25 26 29.25C23.4141 29.25 20.9342 28.2228 19.1057 26.3943C17.2772 24.5658 16.25 22.0859 16.25 19.5C16.25 16.9141 17.2772 14.4342 19.1057 12.6057C20.9342 10.7772 23.4141 9.75 26 9.75C28.5859 9.75 31.0658 10.7772 32.8943 12.6057C34.7228 14.4342 35.75 16.9141 35.75 19.5Z" fill="#EEAB6E"/>
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M0 26C0 19.1044 2.73928 12.4912 7.61522 7.61522C12.4912 2.73928 19.1044 0 26 0C32.8956 0 39.5088 2.73928 44.3848 7.61522C49.2607 12.4912 52 19.1044 52 26C52 32.8956 49.2607 39.5088 44.3848 44.3848C39.5088 49.2607 32.8956 52 26 52C19.1044 52 12.4912 49.2607 7.61522 44.3848C2.73928 39.5088 0 32.8956 0 26ZM26 3.25C21.7158 3.25023 17.5187 4.46018 13.8918 6.74059C10.2649 9.021 7.35567 12.2792 5.49888 16.1401C3.6421 20.0011 2.91326 24.3079 3.39624 28.5648C3.87923 32.8217 5.55442 36.8557 8.229 40.2025C10.5365 36.4845 15.6163 32.5 26 32.5C36.3838 32.5 41.4603 36.4812 43.771 40.2025C46.4456 36.8557 48.1208 32.8217 48.6038 28.5648C49.0867 24.3079 48.3579 20.0011 46.5011 16.1401C44.6443 12.2792 41.7351 9.021 38.1082 6.74059C34.4813 4.46018 30.2842 3.25023 26 3.25Z" fill="#EEAB6E"/>
                    </svg>
                </div>

            </header>
            <section>
                <form action="profile.php" method="POST">
                    <div class="col-left">
                        <svg width="268" height="268" viewBox="0 0 268 268" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M184.25 100.5C184.25 113.827 178.956 126.608 169.532 136.032C160.108 145.456 147.327 150.75 134 150.75C120.673 150.75 107.892 145.456 98.4679 136.032C89.0442 126.608 83.75 113.827 83.75 100.5C83.75 87.1729 89.0442 74.3916 98.4679 64.9679C107.892 55.5442 120.673 50.25 134 50.25C147.327 50.25 160.108 55.5442 169.532 64.9679C178.956 74.3916 184.25 87.1729 184.25 100.5Z" fill="#EEAB6E"/>
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M0 134C0 98.461 14.1178 64.3776 39.2477 39.2477C64.3776 14.1178 98.461 0 134 0C169.539 0 203.622 14.1178 228.752 39.2477C253.882 64.3776 268 98.461 268 134C268 169.539 253.882 203.622 228.752 228.752C203.622 253.882 169.539 268 134 268C98.461 268 64.3776 253.882 39.2477 228.752C14.1178 203.622 0 169.539 0 134ZM134 16.75C111.92 16.7512 90.2886 22.9871 71.5962 34.74C52.9039 46.4929 37.91 63.285 28.3404 83.1837C18.7708 103.082 15.0145 125.279 17.5037 147.218C19.993 169.158 28.6266 189.949 42.411 207.197C54.3035 188.035 80.4837 167.5 134 167.5C187.516 167.5 213.68 188.019 225.589 207.197C239.373 189.949 248.007 169.158 250.496 147.218C252.986 125.279 249.229 103.082 239.66 83.1837C230.09 63.285 215.096 46.4929 196.404 34.74C177.711 22.9871 156.08 16.7512 134 16.75Z" fill="#EEAB6E"/>
                        </svg>
                    </div>

                    <div class="col-right">
                        <div class="nome">
                            <label for="">Nome</label>
                            <input disabled type='text' name='nome' id='nome' value='<?= htmlspecialchars((string) $nome) ?>'>
                        </div>
                        <div class="email">
                            <label for="">E-mail</label>
                            <input disabled type='email' name='email' id='email' value='<?= htmlspecialchars((string) $email) ?>'>
                        </div>
                        <div class="senha">
                            <label for="">Nova senha</label>
                            <input disabled type="password" name="senha" id="senha" placeholder="Deixe em branco para não alterar">
                        </div>
                        <div class="buttons">
                            <button type="submit" name="salvar">SALVAR</button>
                            <button id="alterDataButton" type="button">EDITAR DADOS</button>
                        </div>
                    </div>
                </form>
            </section>
        </div>
        <script src="./assets/js/script-profile.js"></script>
    </body>
</html>
