<?php
/** @var array $viewData */
$cards = $viewData['cards'] ?? [];
$details = $viewData['details'] ?? [];
$errorMessage = $viewData['errorMessage'] ?? null;
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HomeMadeGourmet</title>
    <link rel="stylesheet" href="./assets/css/index.css">
    <link href='https://fonts.googleapis.com/css?family=Inter' rel='stylesheet'>
    <link rel="stylesheet" href="https://maxst.icons8.com/vue-static/landings/line-awesome/font-awesome-line-awesome/css/all.min.css">
    <link rel="stylesheet" href="https://maxst.icons8.com/vue-static/landings/line-awesome/line-awesome/1.3.0/css/line-awesome.min.css">
    <link rel="stylesheet" href="https://maxst.icons8.com/vue-static/landings/line-awesome/font-awesome-line-awesome/css/all.min.css">
</head>

<body>
    <?php if (!empty($errorMessage)): ?>
        <script>alert(<?= json_encode($errorMessage, JSON_UNESCAPED_UNICODE) ?>)</script>
    <?php endif; ?>
    <div class="container">
        <nav class="recipeSearch">
            <form action="index.php" name="formSearch" method="GET" class="formSearch">
                <div class="navBar">
                    <div class="modal-perfil" id="btnModal">
                        <div class="IconeUsuario">
                            <a href="#" class="menuIcon"><img src="./assets/img/iconUser.png" alt="Icone de usuario"></a>
                        </div>
                        <div class="modal" id="modal">
                            <a href="profile.php">
                                <span>Alterar Informações</span>
                                <div class="img">
                                    <img src="./assets/img/iconUserModal.png" alt="">
                                </div>
                            </a>
                            <a href="./assets/php/logout.php">
                                <span>Sair da conta</span>
                                <div class="img">
                                    <img src="./assets/img/iconClose.png" alt="">
                                </div>
                            </a>
                        </div>
                    </div>

                    <div class="searchBar">
                        <input class="searchControl" name="pesquisa" type="search" placeholder="Digite o ingrediente da sua receita ou selecione a categoria pra filtrar">
                        <button class="searchBtn" type="submit" name="buscar">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                <div class="main-container" id="main-container">
                    <div class="radio-buttons">
                        <?php foreach ([1 => ['sushiIcone.png', 'Frutos do Mar', 'col-a'], 2 => ['massaIcone.png', 'Massas', 'col-b'], 3 => ['veganoIcone.png', 'Veganas', 'col-c'], 4 => ['croassaIcone.png', 'Salgados', 'col-d'], 5 => ['boloIcone.png', 'Doces', 'col-f'], 6 => ['carneIcone.png', 'Carnes', 'col-g']] as $id => $category): ?>
                            <label class="custom-radio <?= htmlspecialchars($category[2]) ?>">
                                <input type="radio" name="categoriaReceita" value="<?= $id ?>">
                                <span class="radio-btn"><i class="las la-check-circle"></i>
                                    <div class="recipe-icon">
                                        <img src="./assets/img/<?= htmlspecialchars($category[0]) ?>" />
                                        <h3><?= htmlspecialchars($category[1]) ?></h3>
                                    </div>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </form>
        </nav>

        <div class="recipeResults">
            <h2>SUA PESQUISA</h2>
            <div class="receitasCentro">
                <div class="receitas" id="receitas">
                    <?php foreach ($cards as $recipe): ?>
                        <div class="itemReceita">
                            <div class="btnAbrirReceita" id="<?= (int) $recipe['id'] ?>">
                                <div class="imgReceita" id="<?= (int) $recipe['id'] ?>">
                                    <img src="./assets/img/<?= htmlspecialchars($recipe['image']) ?>" id="<?= (int) $recipe['id'] ?>">
                                </div>
                                <div class="alignItems" id="<?= (int) $recipe['id'] ?>">
                                    <div class="nomeReceita" id="<?= (int) $recipe['id'] ?>">
                                        <h3 id="<?= (int) $recipe['id'] ?>"><?= htmlspecialchars($recipe['name']) ?></h3>
                                    </div>
                                    <div class="infoAdicionaisReceita" id="<?= (int) $recipe['id'] ?>">
                                        <div class="tempoPreparoReceita" id="<?= (int) $recipe['id'] ?>">
                                            <img src="./assets/img/garfoFaca.png" alt="IconeGarfoFaca" id="<?= (int) $recipe['id'] ?>">
                                            <p id="<?= (int) $recipe['id'] ?>"><?= htmlspecialchars($recipe['time']) ?></p>
                                        </div>
                                        <div class="categoriaReceita">
                                            <img src="./assets/img/categoriaIcone.png" alt="iconeCategoria" id="<?= (int) $recipe['id'] ?>">
                                            <p id="<?= (int) $recipe['id'] ?>"><?= htmlspecialchars($recipe['category']) ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <?php foreach ($details as $recipe): ?>
            <div class="detalhesReceita" id="detalhesReceita<?= (int) $recipe['id'] ?>">
                <div class="col-left">
                    <div class="videoReceita"><?= $recipe['video'] ?></div>
                    <div class="ingredientesReceita">
                        <h2>Ingredientes</h2>
                        <p>
                            <?php foreach ($recipe['ingredients'] as $ingredient): ?>
                                -> <?= htmlspecialchars($ingredient) ?><br>
                            <?php endforeach; ?>
                        </p>
                    </div>
                </div>
                <div class="col-right">
                    <div class="btnCloseAndHeader">
                        <h1><?= htmlspecialchars($recipe['name']) ?></h1>
                        <a href="#"><i class="las la-times btnClose" id="<?= (int) $recipe['id'] ?>"></i></a>
                    </div>
                    <div class="informacoesReceita">
                        <div class="divInfo">
                            <img src="./assets/img/categoriaIcon.png" alt="">
                            <label><?= htmlspecialchars($recipe['category']) ?></label>
                        </div>
                        <div class="divInfo">
                            <img src="./assets/img/tempoPreparoIcon.png" alt="">
                            <label><?= htmlspecialchars($recipe['time']) ?></label>
                        </div>
                        <div class="divInfoExclusiva">
                            <img src="./assets/img/porcoesIcon.png" alt="">
                            <label><?= (int) $recipe['servings'] ?> Porções</label>
                        </div>
                        <div class="divInfo">
                            <img src="./assets/img/gastoCaloricoIcon.png" alt="">
                            <label><?= htmlspecialchars((string) $recipe['calories']) ?> cal</label>
                        </div>
                    </div>
                    <div class="modoPreparoReceita">
                        <div class="modoPreparoTitulo">
                            <div class="imgModoPreparo">
                                <img src="./assets/img/modoPreparoIcon.png" alt="">
                            </div>
                            <h2>Modo de Preparo</h2>
                        </div>
                        <p>
                            <?php foreach ($recipe['preparation'] as $step): ?>
                                <?= htmlspecialchars($step) ?><br>
                            <?php endforeach; ?>
                        </p>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <script src="./assets/js/script-index.js"></script>
    </div>
</body>

</html>
