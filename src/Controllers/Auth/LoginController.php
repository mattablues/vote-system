<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Services\AuthService;
use Radix\Auth\Auth;
use Radix\Controller\AbstractController;
use Radix\Http\RedirectResponse;
use Radix\Http\Response;
use Radix\Support\Validator;

class LoginController extends AbstractController
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly Auth $auth,
    ) {}

    public function index(): Response
    {
        return $this->view('auth.login.index');
    }

    public function create(): Response
    {
        $this->before();
        $data = $this->request->post;

        // Validera inskickade data
        $validator = new Validator($data, [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!$validator->validate()) {
            return $this->view('auth.login.index', [
                'errors' => $validator->errors(),
            ]);
        }

        // Säkerställ att email och password är strängar
        $rawEmail = $data['email'] ?? null;
        $rawPassword = $data['password'] ?? null;

        if (!is_string($rawEmail) || $rawEmail === '' || !is_string($rawPassword) || $rawPassword === '') {
            return $this->view('auth.login.index', [
                'errors' => [
                    'form-error' => ['Ogiltiga inloggningsuppgifter.'],
                ],
            ]);
        }

        $email = $rawEmail;
        $password = $rawPassword;

        // Kontrollera om användaren är blockerad
        if ($this->authService->isBlocked($email)) {
            $blockedUntil = $this->authService->getBlockedUntil($email);
            $remainingTime = $blockedUntil !== null ? $blockedUntil - time() : 0;

            $minutes = $remainingTime > 0 ? intdiv($remainingTime, 60) : 0;
            $seconds = $remainingTime > 0 ? $remainingTime % 60 : 0;

            $errorMessage = "För många misslyckade försök. Försök igen om $minutes minuter och $seconds sekunder.";

            return $this->view('auth.login.index', [
                'errors' => [
                    'form-error' => [$errorMessage],
                ],
            ]);
        }

        /** @var array{email:string,password:string} $loginData */
        $loginData = [
            'email' => $email,
            'password' => $password,
        ];

        $user = $this->authService->login($loginData);

        // Kontrollera statusfel eller misslyckad inloggning
        $statusError = $this->authService->getStatusError($user);

        if ($statusError || !$user) {
            return $this->view('auth.login.index', [
                'errors' => ['form-error' => [$statusError ?: 'Inloggning misslyckades.']],
            ]);
        }

        // Logga in användaren och markera som online
        $this->auth->login($user->id);
        $this->request->session()->setFlashMessage("Välkommen tillbaka, $user->first_name $user->last_name!");

        return new RedirectResponse(route('dashboard.index'));
    }
}