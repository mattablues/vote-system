<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Models\User;
use InvalidArgumentException;
use Radix\Http\Exception\NotAuthorizedException;
use Radix\Http\Request;
use Radix\Http\RequestHandlerInterface;
use Radix\Http\Response;
use Radix\Middleware\MiddlewareInterface;
use Radix\Session\Session;

final readonly class RoleRequired implements MiddlewareInterface
{
    public function __construct(
        private ?string $exact = null,
        private ?string $min = null
    ) {
        if ($this->exact === null && $this->min === null) {
            throw new InvalidArgumentException('RoleRequired kräver exact eller min.');
        }
        if ($this->exact !== null && $this->min !== null) {
            throw new InvalidArgumentException('Ange antingen exact eller min, inte båda.');
        }
    }

    public function process(Request $request, RequestHandlerInterface $next): Response
    {
        $id = $request->session()->get(Session::AUTH_KEY);
        if (!$id) {
            throw new NotAuthorizedException('Unable to identify user session.');
        }

        $user = User::select(['id', 'role'])
            ->where('id', '=', $id)
            ->first();

        if (!$user) {
            throw new NotAuthorizedException('User not found.');
        }

        /** @var User $user */
        if ($this->exact !== null) {
            if (!$user->hasRole($this->exact)) {
                throw new NotAuthorizedException('You do not have permission to access this page');
            }
        } else {
            // $this->min är garanterat icke-null här p.g.a. konstruktorns validering
            if (!$user->hasAtLeast((string) $this->min)) {
                throw new NotAuthorizedException('You do not have permission to access this page');
            }
        }

        return $next->handle($request);
    }

    public static function exact(string $role): self
    {
        return new self(exact: $role);
    }

    public static function min(string $role): self
    {
        return new self(min: $role);
    }
}
