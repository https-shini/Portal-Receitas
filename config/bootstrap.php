<?php

declare(strict_types=1);

use App\Application\UseCase\AuthenticateUserUseCase;
use App\Application\UseCase\FindRecipesUseCase;
use App\Application\UseCase\RegisterUserUseCase;
use App\Application\UseCase\UpdateUserProfileUseCase;
use App\Infrastructure\Database\PdoConnectionFactory;
use App\Infrastructure\Repository\PdoRecipeRepository;
use App\Infrastructure\Repository\PdoUserRepository;
use App\Presentation\Controller\AuthController;
use App\Presentation\Controller\ProfileController;
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
    getenv('DB_SSL_VERIFY') !== 'false'
);

$userRepository = new PdoUserRepository($connectionFactory);
$recipeRepository = new PdoRecipeRepository($connectionFactory);

$authenticateUserUseCase = new AuthenticateUserUseCase($userRepository);
$registerUserUseCase = new RegisterUserUseCase($userRepository);
$updateUserProfileUseCase = new UpdateUserProfileUseCase($userRepository);
$findRecipesUseCase = new FindRecipesUseCase($recipeRepository);

$sessionManager = new SessionManager();

return [
    'authController' => new AuthController($registerUserUseCase, $authenticateUserUseCase, $sessionManager),
    'profileController' => new ProfileController($updateUserProfileUseCase, $sessionManager),
    'recipeController' => new RecipeController($findRecipesUseCase, $sessionManager),
    'sessionManager' => $sessionManager,
];
