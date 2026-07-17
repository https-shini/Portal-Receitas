<?php
/**
 * Header global (banner + navegação). Presente em todas as páginas.
 *
 * Reflete o estado de autenticação: o catálogo é público, então o botão
 * "Entrar" só aparece para visitantes; o menu do usuário (perfil, sair) só
 * para autenticados.
 *
 * @var bool $isLogged     Sessão autenticada?
 * @var bool $showLoginCta Exibir o botão "Entrar" para visitantes
 *                         (false nas próprias telas de login/cadastro).
 */
$isLogged = $isLogged ?? false;
$showLoginCta = $showLoginCta ?? true;
?>
<a class="skip-link" href="#conteudo">Pular para o conteúdo</a>

<header class="site-header" role="banner">
    <div class="container site-header__row">
        <a class="brand" href="index.php" aria-label="HomeMadeGourmet — início">
            <img src="./assets/img/logoIcon.png" alt="" width="36" height="36">
            <span class="brand__name">HomeMadeGourmet</span>
        </a>

        <nav class="site-nav" aria-label="Navegação principal">
            <a class="site-nav__link" href="index.php"><i class="las la-utensils" aria-hidden="true"></i> Receitas</a>
        </nav>

        <?php if ($isLogged): ?>
            <details class="user-menu" id="userMenu">
                <summary aria-label="Abrir menu do usuário">
                    <span class="btn btn--ghost btn--icon" aria-hidden="true"><i class="las la-user"></i></span>
                </summary>
                <nav class="user-menu__panel" aria-label="Menu do usuário">
                    <a class="user-menu__item" href="profile.php">
                        <i class="las la-user-cog" aria-hidden="true"></i> Meu perfil
                    </a>
                    <a class="user-menu__item" href="./assets/php/logout.php">
                        <i class="las la-sign-out-alt" aria-hidden="true"></i> Sair da conta
                    </a>
                </nav>
            </details>
        <?php elseif ($showLoginCta): ?>
            <a class="btn btn--soft" href="login.php">
                <i class="las la-sign-in-alt" aria-hidden="true"></i> Entrar
            </a>
        <?php endif; ?>

        <button type="button" class="btn btn--ghost btn--icon js-theme-toggle" aria-label="Alternar tema claro/escuro" aria-pressed="false">
            <i class="las la-moon" aria-hidden="true"></i>
        </button>
    </div>
</header>
