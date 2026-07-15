<?php
include_once __DIR__ . "/conexao.php";

class usuarioController {

    public static function favCategoriaUser($idUsuario) {
        $conn = new Conexao();
        $conn = $conn->conexao();
        $stmt = $conn->prepare("SELECT idCategoriaFK FROM usuario WHERE idUsuario = :id");
        $stmt->execute(['id' => $idUsuario]);
        $categoriaUsuario = $stmt->fetch();
        $stmt = null;

        if (!$categoriaUsuario) {
            return [];
        }

        $stmt = $conn->prepare("SELECT * FROM receita WHERE idcategoriaFK = :categoria");
        $stmt->execute(['categoria' => $categoriaUsuario['idCategoriaFK']]);
        $receitas = $stmt->fetchAll();
        $stmt = null;
        return $receitas;
    }

    public function cadastrar($dados) {
        $conn = new Conexao();
        $conn = $conn->conexao();
        $senhaHash = password_hash($dados['password'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO `usuario` (`idUsuario`, `nomeUsuario`, `emailUsuario`, `senhaUsuario`, `idCategoriaFK`) VALUES (NULL, :eusername, :eemail, :esenha, :ecategoria)");
        $stmt->bindParam(':eusername', $dados['nameUser']);
        $stmt->bindParam(':eemail', $dados['email']);
        $stmt->bindParam(':esenha', $senhaHash);
        $stmt->bindParam(':ecategoria', $dados['categoria']);
        $result = $stmt->execute();
        return $result;
    }

    public function login($email, $senha) {
        $conexao = new Conexao();
        $conexao = $conexao->conexao();
        $stmt = $conexao->prepare("SELECT nomeUsuario, emailUsuario, senhaUsuario, idUsuario FROM usuario WHERE emailUsuario = :email");
        $stmt->execute(['email' => $email]);

        if ($stmt->rowCount() > 0) {
            $result = $stmt->fetch();
            if (password_verify($senha, $result['senhaUsuario'])) {
                return [true, $result['emailUsuario'], $result['senhaUsuario'], $result['nomeUsuario'], $result['idUsuario']];
            }
        }
        return false;
    }

    public function logout() {
        session_destroy();
    }

    public function isLoggedIn() {
        return !empty($_SESSION['logado']);
    }

    public function updateInformationsUser($nomeForm, $emailForm, $senhaForm, $idUsuario) {
        $campos = [];
        $params = ['id' => $idUsuario];

        if (!empty($nomeForm)) {
            $campos[] = "nomeUsuario = :nome";
            $params['nome'] = $nomeForm;
        }
        if (!empty($emailForm)) {
            $campos[] = "emailUsuario = :email";
            $params['email'] = $emailForm;
        }
        if (!empty($senhaForm)) {
            $campos[] = "senhaUsuario = :senha";
            $params['senha'] = password_hash($senhaForm, PASSWORD_DEFAULT);
        }

        if (empty($campos)) {
            return false;
        }

        $sql = "UPDATE usuario SET " . implode(', ', $campos) . " WHERE idUsuario = :id";

        $conexao = new Conexao();
        $conexao = $conexao->conexao();
        $stmt = $conexao->prepare($sql);

        if ($stmt->execute($params)) {
            return true;
        } else {
            return false;
        }
    }

}
