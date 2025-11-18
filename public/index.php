<?php

declare(strict_types=1);

/** @var \Radix\Routing\Router $router */
/** @var \Radix\Container\Container $container */
/** @var array<string, class-string> $middleware */

define('ROOT_PATH', dirname(__DIR__));

require_once ROOT_PATH . '/vendor/autoload.php';
require_once ROOT_PATH . '/bootstrap/app.php';

$dispatcher = new \Radix\Routing\Dispatcher($router, $container, $middleware);
$request = \Radix\Http\Request::createFromGlobals();

$container->addShared(\Radix\Http\Request::class, $request);

/** @var \Radix\Session\SessionInterface $session */
$session = $container->get(\Radix\Session\SessionInterface::class);
$request->setSession($session);

$response = $dispatcher->handle($request);
$response->send();