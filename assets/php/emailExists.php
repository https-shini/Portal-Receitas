<?php

    include_once __DIR__ . "/addusuario.php";

    if (isset($_POST['email'])) {

        $email = trim($_POST['email']);

        $conexao = new Conexao();
        $conexao = $conexao->conexao();
        $stmt = $conexao->prepare('SELECT * FROM usuario WHERE emailUsuario = :email');
        $stmt->execute(['email' => $email]);

        $count = $stmt->rowCount();

        if ($count > 0) {
            echo "
                <META HTTP-EQUIV=REFRESH CONTENT = '0;URL=../../register.php'>
                <script type=\"text/javascript\">
                    alert(\"Email já existente, por favor digite outro!\");
                </script>
                ";
        } else {
            addusuario($_POST);
        }
    }
