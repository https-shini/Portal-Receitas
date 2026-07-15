<?php

declare(strict_types=1);

$services = require __DIR__ . '/../../../config/bootstrap.php';
$result = $services['authController']->register($_POST);

header('Refresh:0;url=' . $result['redirect']);

if (!empty($result['alert'])) {
    echo "<script>alert(" . json_encode($result['alert'], JSON_UNESCAPED_UNICODE) . ");</script>";
}

exit;
