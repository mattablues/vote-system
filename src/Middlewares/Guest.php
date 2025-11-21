<?php

declare(strict_types=1);

namespace App\Middlewares;

use Radix\Http\RedirectResponse;
use Radix\Http\Request;
use Radix\Http\RequestHandlerInterface;
use Radix\Http\Response;
use Radix\Middleware\MiddlewareInterface;
use Radix\Session\Session;

class Guest implements MiddlewareInterface
{
    public function process(Request $request, RequestHandlerInterface $next): Response
    {
        if ($request->session()->has(Session::AUTH_KEY)) {

            $request->session()->setFlashMessage('Sidan kan inte visas när du är inloggad!', 'warning');

            return new RedirectResponse(route('dashboard.index'));
        }

        return $next->handle($request);
    }
}
