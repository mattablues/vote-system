<?php

declare(strict_types=1);

namespace App\Middlewares;

use Radix\Http\Exception\PageNotFoundException;
use Radix\Http\Request;
use Radix\Http\Response;
use Radix\Http\RequestHandlerInterface;
use Radix\Middleware\MiddlewareInterface;

class PrivateApp implements MiddlewareInterface
{
    public function process(Request $request, RequestHandlerInterface $next): Response
    {
        if (getenv('APP_PRIVATE') === '1') {
            throw new PageNotFoundException('This page is private.');
        }

        return $next->handle($request);
    }
}