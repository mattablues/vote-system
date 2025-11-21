<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use Radix\Auth\Auth;
use Radix\Controller\AbstractController;
use Radix\Http\RedirectResponse;
use Radix\Http\Response;

class LogoutController extends AbstractController
{
    public function __construct(private readonly Auth $auth) {}

    /**
     * Hantera utloggning
     */
    public function index(): Response
    {
        $this->auth->logout(); // Uppdatera offline-status och förstör sessionen

        return new RedirectResponse(route('auth.logout.message'));
    }

    /**
     * Visa utloggningsmeddelande
     */
    public function logoutMessage(): Response
    {
        $this->request->session()->setFlashMessage('Du har nu loggats ut!');

        return new RedirectResponse(route('auth.login.index'));
    }

    public function closeLogoutMessage(): Response
    {
        $this->request->session()->setFlashMessage('Ditt konto har stängts, och du har nu loggats ut!', 'error');

        return new RedirectResponse(route('auth.login.index'));
    }

    public function blockedLogoutMessage(): Response
    {
        $this->request->session()->setFlashMessage('Ditt konto har blockerats, och du har nu loggats ut. Kontakta support.', 'error');

        return new RedirectResponse(route('auth.login.index'));
    }

    public function deletedLogoutMessage(): Response
    {
        $this->request->session()->setFlashMessage('Ditt konto har raderats, och du har nu loggats ut!', 'error');

        return new RedirectResponse(route('auth.login.index'));
    }
}
