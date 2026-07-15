<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

apiRequireMethod('GET');

apiRespond($services['authController']->me());
