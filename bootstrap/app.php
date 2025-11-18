<?php

declare(strict_types=1);

$container = require ROOT_PATH . '/config/services.php';

ini_set('session.gc_maxlifetime', '1200'); // 20 minuter
ini_set('session.gc_probability', '1');
ini_set('session.gc_divisor', '100');

date_default_timezone_set(getApplicationTimezone());

set_error_handler('\Radix\Error\RadixErrorHandler::handleError');
set_exception_handler('\Radix\Error\RadixErrorHandler::handleException');

if(getenv('APP_ENV') !== 'development' && is_running_from_console()) {
   http_response_code(403);
   exit('Forbidden: Access is denied.');
}

if (getenv('APP_MAINTENANCE') === '1') {
    throw new \Radix\Http\Exception\MaintenanceException();
}

if (!$container instanceof \Psr\Container\ContainerInterface) {
    $type = is_object($container) ? get_class($container) : gettype($container);

    throw new RuntimeException(
        'Container verkar vara felaktig: ' . $type
    );
}

/** @var Radix\Session\RadixSessionHandler&SessionHandlerInterface $sessionHandler */
$sessionHandler = $container->get(Radix\Session\RadixSessionHandler::class);
session_set_save_handler($sessionHandler, true);

/** @var \Radix\Session\SessionInterface $session */
$session = $container->get(\Radix\Session\SessionInterface::class);
$session->start();

setAppContainer($container);

$providers = require ROOT_PATH . '/config/providers.php';

/**
 * @var array<int, class-string<\Radix\ServiceProvider\ServiceProviderInterface>> $providers
 */
foreach ($providers as $providerClass) {
    if (!is_string($providerClass)) {
        throw new RuntimeException('Varje provider-klass måste vara en sträng (class-string).');
    }

    $provider = $container->get($providerClass);

    if (!$provider instanceof \Radix\ServiceProvider\ServiceProviderInterface) {
        throw new RuntimeException(sprintf(
            'Provider "%s" implementerar inte ServiceProviderInterface.',
            $providerClass
        ));
    }

    $provider->register();
}

$router = require ROOT_PATH . '/config/routes.php';
$middleware = require ROOT_PATH . '/config/middleware.php';

return $container;