<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

apiRequireMethod('POST');

try {
    apiRespond($services['authController']->login($apiInput));
} catch (PDOException) {
    apiUnavailable();
}
