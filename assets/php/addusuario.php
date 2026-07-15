<?php

    include_once __DIR__ . '/conexao.php';
    include_once __DIR__ . '/usuarioController.php';

    function addusuario($dados) {
        $usuario = new usuarioController();
        $result = $usuario->cadastrar($dados);

        if ($result) {
            echo "
                <META HTTP-EQUIV=REFRESH CONTENT = '0;URL=../../login.php'>
                <script type=\"text/javascript\">
                    alert(\"Cadastro realizado com sucesso!\");
                </script>
                ";
        } else {
            echo "Erro ao cadastrar";
        }
    }
