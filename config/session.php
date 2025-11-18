<?php

declare(strict_types=1);

return [
    'session' => [
        'driver' => getenv('SESSION_DRIVER') ?: 'file', // 'file' eller 'database'
        'file_path' => getenv('SESSION_FILE_PATH') ?: sys_get_temp_dir(),
        'table' => getenv('SESSION_TABLE') ?: 'sessions',
        'lifetime' => getenv('SESSION_LIFETIME') ? (int)getenv('SESSION_LIFETIME') : 1440,
    ]
];