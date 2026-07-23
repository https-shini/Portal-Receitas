<?php

declare(strict_types=1);

use App\Application\Security\RateLimiterInterface;
use App\Application\UseCase\AuthenticateUserUseCase;
use App\Application\UseCase\DeleteUserAccountUseCase;
use App\Application\UseCase\FindRecipesUseCase;
use App\Application\UseCase\ListCategoriesUseCase;
use App\Application\UseCase\ListFavoritesUseCase;
use App\Application\UseCase\PostCommentUseCase;
use App\Application\UseCase\RateRecipeUseCase;
use App\Application\UseCase\RecommendRecipesUseCase;
use App\Application\UseCase\RegisterUserUseCase;
use App\Application\UseCase\ShowRecipeUseCase;
use App\Application\UseCase\ToggleFavoriteUseCase;
use App\Application\UseCase\UpdateUserProfileUseCase;
use App\Infrastructure\Database\PdoConnectionFactory;
use App\Infrastructure\Repository\PdoCategoryRepository;
use App\Infrastructure\Repository\PdoCommentRepository;
use App\Infrastructure\Repository\PdoFavoriteRepository;
use App\Infrastructure\Repository\PdoRatingRepository;
use App\Infrastructure\Repository\PdoRecipeRepository;
use App\Infrastructure\Repository\PdoUserRepository;
use App\Infrastructure\Security\FileRateLimiter;
use App\Presentation\Controller\AuthController;
use App\Presentation\Controller\CommentController;
use App\Presentation\Controller\FavoriteController;
use App\Presentation\Controller\ProfileController;
use App\Presentation\Controller\RatingController;
use App\Presentation\Controller\RecipeController;
use App\Presentation\Http\SessionManager;

/**
 * Composition root — único ponto do sistema que conhece as implementações
 * concretas e monta o grafo de dependências (injeção manual, sem contêiner).
 *
 * Retorna o mapa de serviços consumido pelos entrypoints em public/:
 * authController, profileController, recipeController e sessionManager.
 */

/*
 * Autoload: usa o vendor/autoload.php do Composer (gerado no build Docker).
 * O fallback PSR-4 manual cobre ambientes sem Composer (ex.: XAMPP com o
 * repositório copiado direto para o htdocs).
 */
$vendorAutoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($vendorAutoload)) {
    require_once $vendorAutoload;
} else {
    spl_autoload_register(static function (string $class): void {
        $prefix = 'App\\';
        if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
            return;
        }

        $relativeClass = substr($class, strlen($prefix));
        $file = __DIR__ . '/../src/' . str_replace('\\', '/', $relativeClass) . '.php';

        if (file_exists($file)) {
            require_once $file;
        }
    });
}

/*
 * Configuração 12-factor: tudo vem de variáveis de ambiente (compose e
 * render.yaml as definem). Os padrões apontam para um XAMPP local
 * (localhost/root/senha vazia), preservando o fluxo de desenvolvimento
 * do TCC original sem nenhum arquivo de configuração.
 */
$connectionFactory = new PdoConnectionFactory(
    getenv('DB_HOST') ?: 'localhost',
    getenv('DB_NAME') ?: 'tcc_receitas',
    getenv('DB_USER') ?: 'root',
    getenv('DB_PASS') !== false ? (string) getenv('DB_PASS') : '',
    getenv('DB_PORT') ?: '3306',
    getenv('DB_SSL_CA') ?: null,
    getenv('DB_SSL_VERIFY') !== 'false',
    getenv('DB_SOCKET') ?: null
);

$userRepository = new PdoUserRepository($connectionFactory);
$recipeRepository = new PdoRecipeRepository($connectionFactory);
$categoryRepository = new PdoCategoryRepository($connectionFactory);
$favoriteRepository = new PdoFavoriteRepository($connectionFactory);
$ratingRepository = new PdoRatingRepository($connectionFactory);
$commentRepository = new PdoCommentRepository($connectionFactory);

$authenticateUserUseCase = new AuthenticateUserUseCase($userRepository);
$registerUserUseCase = new RegisterUserUseCase($userRepository);
$updateUserProfileUseCase = new UpdateUserProfileUseCase($userRepository);
$deleteUserAccountUseCase = new DeleteUserAccountUseCase($userRepository);
$findRecipesUseCase = new FindRecipesUseCase($recipeRepository);
$listCategoriesUseCase = new ListCategoriesUseCase($categoryRepository);
$showRecipeUseCase = new ShowRecipeUseCase($recipeRepository);
$recommendRecipesUseCase = new RecommendRecipesUseCase($recipeRepository, $favoriteRepository);
$toggleFavoriteUseCase = new ToggleFavoriteUseCase($favoriteRepository);
$listFavoritesUseCase = new ListFavoritesUseCase($favoriteRepository);
$rateRecipeUseCase = new RateRecipeUseCase($ratingRepository);
$postCommentUseCase = new PostCommentUseCase($commentRepository);

$sessionManager = new SessionManager();

/** @var RateLimiterInterface $rateLimiter Proteção contra força bruta (por instância). */
$rateLimiter = new FileRateLimiter();

return [
    'authController' => new AuthController($registerUserUseCase, $authenticateUserUseCase, $deleteUserAccountUseCase, $sessionManager, $rateLimiter),
    'profileController' => new ProfileController($updateUserProfileUseCase, $sessionManager),
    'recipeController' => new RecipeController($findRecipesUseCase, $listCategoriesUseCase, $showRecipeUseCase, $recommendRecipesUseCase, $sessionManager),
    'favoriteController' => new FavoriteController($toggleFavoriteUseCase, $listFavoritesUseCase, $favoriteRepository, $sessionManager, $rateLimiter),
    'ratingController' => new RatingController($rateRecipeUseCase, $ratingRepository, $sessionManager, $rateLimiter),
    'commentController' => new CommentController($postCommentUseCase, $commentRepository, $sessionManager, $rateLimiter),
    'sessionManager' => $sessionManager,
];
