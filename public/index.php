<?php

declare(strict_types=1);

$services = require __DIR__ . '/../config/bootstrap.php';
$viewData = $services['recipeController']->list($_GET);

require __DIR__ . '/../src/Presentation/View/index.php';
