<?php

declare(strict_types=1);

namespace App\Middlewares;

use Radix\Http\Request;
use Radix\Http\RequestHandlerInterface;
use Radix\Http\Response;
use Radix\Middleware\MiddlewareInterface;

final class RequireModeratorOrHigher implements MiddlewareInterface
{
    private RoleRequired $inner;

    public function __construct()
    {
        $this->inner = new RoleRequired(min: 'moderator');
    }

    public function process(Request $request, RequestHandlerInterface $next): Response
    {
        return $this->inner->process($request, $next);
    }
}