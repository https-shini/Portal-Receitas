<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

apiRequireMethod('POST');

apiRespond($services['authController']->logoutApi());
