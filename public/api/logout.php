<?php

declare(strict_types=1);

/** POST /api/logout.php — encerramento de sessão (200 · 405). */

require __DIR__ . '/_bootstrap.php';

apiRequireMethod('POST');

apiRespond($services['authController']->logoutApi());
