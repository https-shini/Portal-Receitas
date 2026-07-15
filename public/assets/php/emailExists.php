<?php

declare(strict_types=1);

$services = require __DIR__ . '/../../../config/bootstrap.php';

try {
    $result = $services['authController']->register($_POST);
} catch (PDOException) {
    http_response_code(503);
    require __DIR__ . '/../../../src/Presentation/View/unavailable.php';
    exit;
}

header('Refresh:0;url=' . $result['redirect']);

if (!empty($result['alert'])) {
    echo "<script>alert(" . json_encode($result['alert'], JSON_UNESCAPED_UNICODE) . ");</script>";
}

exit;
