<?php

declare(strict_types=1);

$envMailDebug    = getenv('MAIL_DEBUG');
$envMailCharset  = getenv('MAIL_CHARSET');
$envMailHost     = getenv('MAIL_HOST');
$envMailPort     = getenv('MAIL_PORT');
$envMailSecure   = getenv('MAIL_SECURE');
$envMailAuth     = getenv('MAIL_AUTH');
$envMailAccount  = getenv('MAIL_ACCOUNT');
$envMailPassword = getenv('MAIL_PASSWORD');
$envMailEmail    = getenv('MAIL_EMAIL');
$envMailFrom     = getenv('MAIL_FROM');

return [
    'email' => [
        'debug'    => $envMailDebug === false ? '0' : $envMailDebug,
        'charset'  => $envMailCharset === false ? 'UTF-8' : $envMailCharset,
        'host'     => $envMailHost === false ? '' : $envMailHost,
        'port'     => $envMailPort === false ? '' : $envMailPort,
        'secure'   => $envMailSecure === false ? 'tls' : $envMailSecure,
        'auth'     => $envMailAuth === false
            ? true
            : (bool) filter_var($envMailAuth, FILTER_VALIDATE_BOOLEAN),
        'username' => $envMailAccount === false ? '' : $envMailAccount,
        'password' => $envMailPassword === false ? '' : $envMailPassword,
        'email'    => $envMailEmail === false ? 'noreply@example.com' : $envMailEmail,
        'from'     => $envMailFrom === false ? 'No Reply' : $envMailFrom,
    ],
];
