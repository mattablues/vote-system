<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Models\User;
use Radix\Http\Exception\NotAuthorizedException;
use Radix\Http\Request;
use Radix\Http\RequestHandlerInterface;
use Radix\Http\Response;
use Radix\Middleware\MiddlewareInterface;
use Radix\Session\Session;

readonly class Admin implements MiddlewareInterface
{
    public function process(Request $request, RequestHandlerInterface $next): Response
    {
        $id = $request->session()->get(Session::AUTH_KEY);

        if (!$id) {
            throw new NotAuthorizedException('Unable to identify user session.');
        }

        $user =  User::select(['id', 'role'])
                ->where('id', '=', $id)
                ->where('role', '=', 'admin')
                ->first();

        if (!$user) {
            throw new NotAuthorizedException('You do not have permission to access this page');
        }

        return $next->handle($request);
    }
}
