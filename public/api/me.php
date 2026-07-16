<?php

declare(strict_types=1);

/** GET /api/me.php — dados do usuário da sessão (rota protegida: 200 · 401 · 405). */

require __DIR__ . '/_bootstrap.php';

apiRequireMethod('GET');

apiRespond($services['authController']->me());
