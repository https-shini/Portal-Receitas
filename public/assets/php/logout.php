<?php

declare(strict_types=1);

$services = require __DIR__ . '/../../../config/bootstrap.php';
$redirect = $services['authController']->logout();

header('Location: ' . $redirect);
exit;
