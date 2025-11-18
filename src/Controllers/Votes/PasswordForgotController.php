<?php

declare(strict_types=1);

namespace App\Controllers\Votes;

use App\Events\UserPasswordEvent;
use App\Events\VoterPasswordEvent;
use App\Models\Voter;
use App\Services\AuthService;
use Radix\Controller\AbstractController;
use Radix\EventDispatcher\EventDispatcher;
use Radix\Http\RedirectResponse;
use Radix\Http\Response;
use Radix\Support\Token;
use Radix\Support\Validator;

class PasswordForgotController extends AbstractController
{
    public function __construct(
        private readonly EventDispatcher  $eventDispatcher,
        private readonly AuthService $authService,
    )
    {
    }

    public function index(): Response
    {
        return $this->view('votes.voter.password-forgot');
    }

    public function create(): Response
    {
        $this->before();
        $data = $this->request->post;

        $validator = new Validator($data, [
            'email' => 'required|email',
        ]);

        if (!$validator->validate()) {
            return $this->view('votes.voter.password-forgot', [
                'errors' => $validator->errors(),
            ]);
        }

        // Säkerställ att email är en sträng
        $rawEmail = $data['email'] ?? null;
        if (!is_string($rawEmail) || $rawEmail === '') {
            // Valideringen borde egentligen ha fångat detta, men vi skyddar oss ändå
            $this->request->session()->setFlashMessage('Ogiltig e‑postadress.', 'error');
            return new RedirectResponse(route('voter.password-forgot.index'));
        }

        $email = $rawEmail;
        $ip = $this->request->ip(); // Hämta användarens IP-adress

        // Kontrollera om användaren är tillfälligt blockerad på grund av för många försök
        if ($this->authService->isBlocked($email)) {
            $blockedUntil = $this->authService->getBlockedUntil($email);
            $remainingTime = $blockedUntil - time();

            // Räkna ut minuter och sekunder kvar
            $minutes = intdiv($remainingTime, 60);
            $seconds = $remainingTime % 60;
            $errorMessage = "För många försök. Försök igen om $minutes minuter och $seconds sekunder.";

            return $this->view('votes.voter.password-forgot', [
                'errors' => [
                    'form-error' => [$errorMessage],
                ],
            ]);
        }

        if ($this->authService->isIpBlocked($ip)) {
            $blockedUntil = $this->authService->getBlockedIpUntil($ip);
            $remainingTime = $blockedUntil - time();

            // Räkna ut minuter och sekunder kvar
            $minutes = intdiv($remainingTime, 60);
            $seconds = $remainingTime % 60;

            $errorMessage = "För många förfrågningar från denna IP. Försök igen om $minutes minuter och $seconds sekunder.";

            return $this->view('votes.voter.password-forgot', [
                'errors' => [
                    'form-error' => [$errorMessage],
                ],
            ]);
        }

        $voter = Voter::where('email', '=', $email)->first();

        if ($voter) {
            if ($voter->getAttribute('status') === 'activated') {
                $token = new Token();
                $tokenValue = $token->value();
                $tokenHash = $token->hashHmac();
                $resetExpiresAt = time() + 60 * 60 * 2;

                $voter->fill([
                    'password_reset' => $tokenHash,
                    'reset_expires_at' => date('Y-m-d H:i:s', $resetExpiresAt),
                ]);

                $voter->save();

                $resetLink = getenv('APP_URL') . route('voter.password-reset.index', ['token' => $tokenValue]);

                // Skicka e-postmeddelande
                $this->eventDispatcher->dispatch(new VoterPasswordEvent(
                    email: $email,
                    resetLink: $resetLink
                ));

                // Återställ misslyckade försök vid framgång
                $this->authService->clearFailedAttempts($email);
                $this->authService->clearFailedIpAttempt($ip);
            }
        }

        // Spara misslyckat försök om användaren inte finns eller något annat går fel
        if (!$voter || $voter->getAttribute('status') !== 'activated') {
            $this->authService->trackFailedAttempt($email);
            $this->authService->trackFailedIpAttempt($ip);
        }

        $this->request->session()->setFlashMessage('Ett e-postmeddelande med återställningsinformation har skickats till din e-postadress.');

        return new RedirectResponse(route('votes.subject.index'));
    }
}