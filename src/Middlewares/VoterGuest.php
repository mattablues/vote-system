<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Services\VoterSessionService;
use Radix\Http\RedirectResponse;
use Radix\Http\Request;
use Radix\Http\RequestHandlerInterface;
use Radix\Http\Response;
use Radix\Middleware\MiddlewareInterface;

readonly class VoterGuest implements MiddlewareInterface
{
    public function __construct(private VoterSessionService $voterSession) {}

    public function process(Request $request, RequestHandlerInterface $next): Response
    {
        if ($this->voterSession->isAuthenticated()) {
            $request->session()->setFlashMessage('Du är redan inloggad som röstberättigad.', 'info');
            return new RedirectResponse(route('votes.subject.index'));
        }

        return $next->handle($request);
    }
}