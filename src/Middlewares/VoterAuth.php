<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Services\VoterSessionService;
use Radix\Http\RedirectResponse;
use Radix\Http\Request;
use Radix\Http\RequestHandlerInterface;
use Radix\Http\Response;
use Radix\Middleware\MiddlewareInterface;

readonly class VoterAuth implements MiddlewareInterface
{
    public function __construct(private VoterSessionService $voterSession) {}

    public function process(Request $request, RequestHandlerInterface $next): Response
    {
        if (!$this->voterSession->isAuthenticated()) {
            // Spara intended URL och skicka till voter-login
            $request->session()->set('intended_url', $request->fullUrl());
            $request->session()->setFlashMessage('Logga in som röstberättigad för att fortsätta.', 'info');
            return new RedirectResponse(route('voter.auth.login'));
        }

        return $next->handle($request);
    }
}
