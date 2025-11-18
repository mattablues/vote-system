<?php

declare(strict_types=1);

namespace App\Controllers\Votes;

use App\Services\VoterService;
use App\Services\VoterSessionService;
use Radix\Controller\AbstractController;
use Radix\Http\RedirectResponse;
use Radix\Http\Response;
use Radix\Support\Validator;

class VoterAuthController extends AbstractController
{
    public function __construct(
        private readonly VoterService $voterService,
        private readonly VoterSessionService $voterSession
    ) {
    }

    public function login(): Response
    {
        // Visa login-formulär
        return $this->view('votes.voter.login');
    }

    public function create(): Response
    {
        $this->before();
        $data = $this->request->post;

        $validator = new Validator($data, [
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if (!$validator->validate()) {
            return $this->view('votes.voter.login', [
                'errors' => $validator->errors(),
            ]);
        }

        $email  = is_string($data['email'] ?? null) ? $data['email'] : '';

        // (använd VoterAuthService direkt, inte via $this->voterService->throttle)
        // Antingen injicerar du VoterAuthService i konstruktorn, eller hämtar den via VoterService getter.
        // Här antar vi direkt via VoterService med en metod getThrottle().
        /** @var \App\Services\VoterAuthService $throttle */
        $throttle = (function ($svc) {
            // enkel accessor utan att ändra VoterService publikt API i onödan:
            $r = new \ReflectionClass($svc);
            $p = $r->getProperty('throttle');
            $p->setAccessible(true);
            return $p->getValue($svc);
        })($this->voterService);

        if ($throttle->isBlocked($email)) {
            $val = $throttle->getBlockedUntil($email);
            $blockedUntil = is_numeric($val) ? (int)$val : 0;
            $remainingTime = max(0, $blockedUntil - time());

            // Räkna ut minuter och sekunder kvar
            $minutes = intdiv($remainingTime, 60);
            $seconds = $remainingTime % 60;

            $errorMessage = "För många misslyckade försök. Försök igen om $minutes minuter och $seconds sekunder.";

            return $this->view('votes.voter.login', [
                'errors' => [
                    'form-error' => [$errorMessage],
                ],
            ]);
        }

        $errors = [];
        $voter = $this->voterService->authenticate($data, $errors);

        if (!$voter) {
            foreach ($errors as $field => $messages) {
                foreach ($messages as $msg) {
                    $validator->addError($field, $msg);
                }
            }
            return $this->view('votes.voter.login', [
                'errors' => $validator->errors(),
            ]);
        }

        // Logga in och redirecta till intended URL (om satt), annars hem
        $this->voterSession->login($voter);

        $intendedRaw = $this->request->session()->get('intended_url');
        $this->request->session()->remove('intended_url');

        $this->request->session()->setFlashMessage('Du är nu inloggad som röstberättigad.');

        $intended = is_string($intendedRaw) && $intendedRaw !== ''
            ? $intendedRaw
            : route('home.index');

        return new RedirectResponse($intended);
    }

    public function logout(): RedirectResponse
    {
        $this->voterSession->logout();
        $this->request->session()->setFlashMessage('Du har loggats ut som röstberättigad.');
        return new RedirectResponse(route('home.index'));
    }
}