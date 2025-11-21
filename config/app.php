<?php

declare(strict_types=1);

return [
    'app' => [
        'env' => getenv('APP_ENV') ?: 'production',
        'lang' => getenv('APP_LANG') ?: 'en',
        'name' => getenv('APP_NAME') ?: 'Your App Name',
        'copy' => getenv('APP_COPY') ?: 'Your Copyright',
        'timezone' => getenv('APP_TIMEZONE') ?: 'UTC',
        'url' => getenv('APP_URL') ?: 'http://localhost',
        'maintenance' => getenv('APP_MAINTENANCE') ?: '0',
        'debug' => getenv('APP_DEBUG') ?: '0',
    ],
];
