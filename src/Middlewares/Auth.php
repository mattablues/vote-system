<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Events\UserBlockedEvent;
use App\Models\Status;
use App\Models\User;
use Radix\EventDispatcher\EventDispatcher;
use Radix\Http\Exception\NotAuthorizedException;
use Radix\Http\RedirectResponse;
use Radix\Http\Request;
use Radix\Http\RequestHandlerInterface;
use Radix\Http\Response;
use Radix\Middleware\MiddlewareInterface;
use Radix\Session\SessionInterface;

readonly class Auth implements MiddlewareInterface
{
    public function __construct(private EventDispatcher $eventDispatcher) {}

    public function process(Request $request, RequestHandlerInterface $next): Response
    {
        $session = $request->session();

        // Kontrollera om användaren är autentiserad
        if (!$session->isAuthenticated()) {
            $session->setFlashMessage('Du måste vara inloggad för att besöka sidan!', 'info');
            return new RedirectResponse(route('auth.login.index'));
        }

        // Hämta autentiserad användare
        $userId = $session->get(\Radix\Session\Session::AUTH_KEY);

        if (!is_int($userId)) {
            // Om AUTH_KEY inte är en int är sessionen korrupt/ogiltig: behandla som ej inloggad
            $session->clear();
            $session->destroy();
            return new RedirectResponse(route('auth.login.index'));
        }

        /** @var int $userId */
        $user = User::with('status')->where('id', '=', $userId)->first();

        if (!$user instanceof User) {
            throw new NotAuthorizedException('User not found.');
        }

        // Kontrollera om användaren är blockerad
        $status = $user->getRelation('status');

        if (!$status instanceof Status) {
            /** @var Status|null $status */
            $status = $user->status()->first();
        }

        if ($status instanceof Status && $status->isBlocked()) { // Kontrollera blockeringsstatus;
            $this->eventDispatcher->dispatch(new UserBlockedEvent($userId));
        }

        // Uppdatera användarens status till "online" och kontrollera timeout
        $this->handleUserSessionLifecycle($session);

        // Skicka vidare till nästa middleware / hantering
        return $next->handle($request);
    }

    /**
     * Hantera användarens session-livscykel för att markera online/inaktiv status.
     */
    private function handleUserSessionLifecycle(SessionInterface $session): void
    {
        $userId = $session->get(\Radix\Session\Session::AUTH_KEY);

        if (!is_int($userId)) {
            // Ogiltigt userId => gör inget mer här
            return;
        }

        /** @var int $userId */
        $user = User::find($userId); // Hämta användaren

        if ($user instanceof User) {
            // Kontrollera timeout-inställningar
            $timeout = 15 * 60; // 15 minuter

            $rawLastLogin = $session->get('last_login', time());
            $lastLogin = is_int($rawLastLogin) ? $rawLastLogin : time();

            if (time() - $lastLogin > $timeout) {
                $user->setOffline();
                $session->clear();
                $session->destroy();

                redirect(route('auth.login.index')); // Omdirigera till inloggningssida
            }

            $session->set('last_login', time()); // Uppdatera för att fortsätta hålla sessionen aktiv
            $user->setOnline();
        }
    }
}
