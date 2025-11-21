<?php

declare(strict_types=1);

namespace App\EventListeners;

use App\Events\UserBlockedEvent;
use Radix\Http\RedirectResponse;
use Radix\Session\Session;
use Radix\Session\SessionInterface;

readonly class LogoutListener
{
    public function __construct(
        private SessionInterface $session
    ) {}

    public function __invoke(UserBlockedEvent $event): void
    {
        $authenticatedUserId = $this->session->get(Session::AUTH_KEY);

        if ($authenticatedUserId === $event->getUserId()) {
            $this->session->destroy();

            $response = new RedirectResponse(route('auth.logout.blocked-message'));
            $response->send();
        }
    }
}
