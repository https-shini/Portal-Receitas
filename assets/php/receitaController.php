<?php
include_once __DIR__ . "/conexao.php";

class ReceitaController {

    public static function favCategoriaUser($categoriaFavorita) {
        $conn = new Conexao();
        $conn = $conn->conexao();
        $stmt = $conn->prepare("SELECT * FROM receita WHERE idcategoriaFK = :categoria");
        $stmt->execute(['categoria' => $categoriaFavorita]);
        $receitas = $stmt->fetchAll();
        $stmt = null;
        return $receitas;
    }

    public static function allReceitas() {
        $conn = new Conexao();
        $conn = $conn->conexao();
        $stmt = $conn->prepare("SELECT `idReceita`,`nomeReceita`, `tempoReceita`, `idcategoriaFK`, `imagem` FROM receita");
        $stmt->execute();
        $receitas = $stmt->fetchAll();
        $stmt = null;
        return $receitas;
    }

    public static function allDetailsReceitas() {
        $conn = new Conexao();
        $conn = $conn->conexao();
        $stmt = $conn->prepare("SELECT
        `idReceita`,
        `qtdCalorias`,
        `nomeReceita`,
        `porcoes`,
        `tempoReceita`,
        `link`,
        `ingrediente_1`,
        `ingrediente_2`,
        `ingrediente_3`,
        `ingrediente_4`,
        `ingrediente_5`,
        `ingrediente_6`,
        `ingrediente_7`,
        `ingrediente_8`,
        `ingrediente_9`,
        `ingrediente_10`,
        `ingrediente_11`,
        `ingrediente_12`,
        `ingrediente_13`,
        `ingrediente_14`,
        `ingrediente_15`,
        `modoPreparo`,
        `idcategoriaFK` FROM `receita`");
        $stmt->execute();
        $receitas = $stmt->fetchAll();
        $stmt = null;
        return $receitas;
    }

    public static function getRecipesByCategory($categoriaSelect) {
        $conn = new Conexao();
        $conn = $conn->conexao();
        $stmt = $conn->prepare("SELECT `idReceita`,`nomeReceita`, `tempoReceita`, `idcategoriaFK`, `imagem` FROM `receita` WHERE idcategoriaFK = :categoria");
        $stmt->execute(['categoria' => $categoriaSelect]);
        $resultado = $stmt->fetchAll();
        $stmt = null;
        return $resultado;
    }

    public static function allDetailsReceitasByCategory($categoriaSelect) {
        $conn = new Conexao();
        $conn = $conn->conexao();
        $stmt = $conn->prepare("SELECT
        `idReceita`,
        `qtdCalorias`,
        `nomeReceita`,
        `porcoes`,
        `tempoReceita`,
        `link`,
        `ingrediente_1`,
        `ingrediente_2`,
        `ingrediente_3`,
        `ingrediente_4`,
        `ingrediente_5`,
        `ingrediente_6`,
        `ingrediente_7`,
        `ingrediente_8`,
        `ingrediente_9`,
        `ingrediente_10`,
        `ingrediente_11`,
        `ingrediente_12`,
        `ingrediente_13`,
        `ingrediente_14`,
        `ingrediente_15`,
        `modoPreparo`,
        `idcategoriaFK` FROM `receita` WHERE idcategoriaFK = :categoria");
        $stmt->execute(['categoria' => $categoriaSelect]);
        $receitas = $stmt->fetchAll();
        $stmt = null;
        return $receitas;
    }

    public static function getRecipesBySearch($search) {
        $conn = new Conexao();
        $conn = $conn->conexao();
        $like = "%{$search}%";
        $stmt = $conn->prepare("SELECT
        `idReceita`,
        `nomeReceita`,
        `tempoReceita`,
        `idcategoriaFK`,
        `imagem`
        FROM `receita`
        WHERE
        ingrediente_1   like :s1 or
        ingrediente_2   like :s2 or
        ingrediente_3   like :s3 or
        ingrediente_4   like :s4 or
        ingrediente_5   like :s5 or
        ingrediente_6   like :s6 or
        ingrediente_7   like :s7 or
        ingrediente_8   like :s8 or
        ingrediente_9   like :s9 or
        ingrediente_10  like :s10 or
        ingrediente_11  like :s11 or
        ingrediente_12  like :s12 or
        ingrediente_13  like :s13 or
        ingrediente_14  like :s14 or
        ingrediente_15  like :s15
        ");
        $stmt->execute([
            's1' => $like, 's2' => $like, 's3' => $like, 's4' => $like, 's5' => $like,
            's6' => $like, 's7' => $like, 's8' => $like, 's9' => $like, 's10' => $like,
            's11' => $like, 's12' => $like, 's13' => $like, 's14' => $like, 's15' => $like,
        ]);
        $resultado = $stmt->fetchAll();
        $stmt = null;
        return $resultado;
    }

    public static function allDetailsReceitasBySearch($search) {
        $conn = new Conexao();
        $conn = $conn->conexao();
        $like = "%{$search}%";
        $stmt = $conn->prepare("SELECT
        `idReceita`,
        `qtdCalorias`,
        `nomeReceita`,
        `porcoes`,
        `tempoReceita`,
        `link`,
        `ingrediente_1`,
        `ingrediente_2`,
        `ingrediente_3`,
        `ingrediente_4`,
        `ingrediente_5`,
        `ingrediente_6`,
        `ingrediente_7`,
        `ingrediente_8`,
        `ingrediente_9`,
        `ingrediente_10`,
        `ingrediente_11`,
        `ingrediente_12`,
        `ingrediente_13`,
        `ingrediente_14`,
        `ingrediente_15`,
        `modoPreparo`,
        `idcategoriaFK`
        FROM `receita`
        WHERE
        ingrediente_1   like :s1 or
        ingrediente_2   like :s2 or
        ingrediente_3   like :s3 or
        ingrediente_4   like :s4 or
        ingrediente_5   like :s5 or
        ingrediente_6   like :s6 or
        ingrediente_7   like :s7 or
        ingrediente_8   like :s8 or
        ingrediente_9   like :s9 or
        ingrediente_10  like :s10 or
        ingrediente_11  like :s11 or
        ingrediente_12  like :s12 or
        ingrediente_13  like :s13 or
        ingrediente_14  like :s14 or
        ingrediente_15  like :s15
        ");
        $stmt->execute([
            's1' => $like, 's2' => $like, 's3' => $like, 's4' => $like, 's5' => $like,
            's6' => $like, 's7' => $like, 's8' => $like, 's9' => $like, 's10' => $like,
            's11' => $like, 's12' => $like, 's13' => $like, 's14' => $like, 's15' => $like,
        ]);
        $resultado = $stmt->fetchAll();
        $stmt = null;
        return $resultado;
    }

    public static function getRecipesBySearchAndCategory($search, $category) {
        $conn = new Conexao();
        $conn = $conn->conexao();
        $like = "%{$search}%";
        $stmt = $conn->prepare("SELECT
            `idReceita`,
            `nomeReceita`,
            `tempoReceita`,
            `idcategoriaFK`,
            `imagem`
            FROM  `receita`
            WHERE idcategoriaFK = :categoria AND
            (ingrediente_1   like :s1 or
            ingrediente_2   like :s2 or
            ingrediente_3   like :s3 or
            ingrediente_4   like :s4 or
            ingrediente_5   like :s5 or
            ingrediente_6   like :s6 or
            ingrediente_7   like :s7 or
            ingrediente_8   like :s8 or
            ingrediente_9   like :s9 or
            ingrediente_10  like :s10 or
            ingrediente_11  like :s11 or
            ingrediente_12  like :s12 or
            ingrediente_13  like :s13 or
            ingrediente_14  like :s14 or
            ingrediente_15  like :s15)
        ");
        $stmt->execute([
            'categoria' => $category,
            's1' => $like, 's2' => $like, 's3' => $like, 's4' => $like, 's5' => $like,
            's6' => $like, 's7' => $like, 's8' => $like, 's9' => $like, 's10' => $like,
            's11' => $like, 's12' => $like, 's13' => $like, 's14' => $like, 's15' => $like,
        ]);
        $resultado = $stmt->fetchAll();
        $stmt = null;
        return $resultado;
    }

    public static function allDetailsReceitasBySearchAndCategory($search, $category) {
        $conn = new Conexao();
        $conn = $conn->conexao();
        $like = "%{$search}%";
        $stmt = $conn->prepare("SELECT
            `idReceita`,
            `qtdCalorias`,
            `nomeReceita`,
            `porcoes`,
            `tempoReceita`,
            `link`,
            `ingrediente_1`,
            `ingrediente_2`,
            `ingrediente_3`,
            `ingrediente_4`,
            `ingrediente_5`,
            `ingrediente_6`,
            `ingrediente_7`,
            `ingrediente_8`,
            `ingrediente_9`,
            `ingrediente_10`,
            `ingrediente_11`,
            `ingrediente_12`,
            `ingrediente_13`,
            `ingrediente_14`,
            `ingrediente_15`,
            `modoPreparo`,
            `idcategoriaFK`
            FROM  `receita`
            WHERE idcategoriaFK = :categoria AND
            (ingrediente_1   like :s1 or
            ingrediente_2   like :s2 or
            ingrediente_3   like :s3 or
            ingrediente_4   like :s4 or
            ingrediente_5   like :s5 or
            ingrediente_6   like :s6 or
            ingrediente_7   like :s7 or
            ingrediente_8   like :s8 or
            ingrediente_9   like :s9 or
            ingrediente_10  like :s10 or
            ingrediente_11  like :s11 or
            ingrediente_12  like :s12 or
            ingrediente_13  like :s13 or
            ingrediente_14  like :s14 or
            ingrediente_15  like :s15)
        ");
        $stmt->execute([
            'categoria' => $category,
            's1' => $like, 's2' => $like, 's3' => $like, 's4' => $like, 's5' => $like,
            's6' => $like, 's7' => $like, 's8' => $like, 's9' => $like, 's10' => $like,
            's11' => $like, 's12' => $like, 's13' => $like, 's14' => $like, 's15' => $like,
        ]);
        $resultado = $stmt->fetchAll();
        $stmt = null;
        return $resultado;
    }
}
