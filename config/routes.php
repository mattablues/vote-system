<?php

declare(strict_types=1);

use Radix\Routing\Router;

$router = new Router;

require ROOT_PATH . '/routes/web.php';
require ROOT_PATH . '/routes/api.php';

return $router;