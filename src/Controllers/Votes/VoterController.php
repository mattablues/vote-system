<?php

declare(strict_types=1);

namespace App\Controllers\Votes;

use App\Enums\VoterContext;
use App\Events\VoterRegisteredEvent;
use App\Models\Voter;
use App\Services\VoterService;
use Radix\Controller\AbstractController;
use Radix\EventDispatcher\EventDispatcher;
use Radix\Http\RedirectResponse;
use Radix\Http\Response;
use Radix\Support\Token;
use Radix\Support\Validator;

class VoterController extends AbstractController
{
    public function __construct(
        private readonly EventDispatcher $eventDispatcher,
        private readonly VoterService $voterService
    ) {}

    public function index(): Response
    {
        $honeypotId = generate_honeypot_id();

        // Spara id:t i sessionen
        $this->request->session()->set('honeypot_id', $honeypotId);

        return $this->view('votes.voter.index', [
            'honeypotId' => $honeypotId, // Skicka också till vyn
        ]);
    }

    public function create(): Response
    {
        $this->before();
        $data = $this->request->post;


        $expectedHoneypotId = $this->request->session()->get('honeypot_id');

        if (!is_string($expectedHoneypotId) || $expectedHoneypotId === '') {
            return new RedirectResponse(route('voter.index')); // Tillbaka till formuläret
        }

        // Validera inkommande data
        $validator = new Validator($data, [
            'email' => 'required|email|unique:App\Models\Voter,email',
            'password' => 'required|min:8|max:15',
            'password_confirmation' => 'required|confirmed:password',
            $expectedHoneypotId => 'honeypot', // Dynamisk validering
        ]);

        if (!$validator->validate()) {
            $this->request->session()->set('old', $data);

            $newHoneypotId = generate_honeypot_id();
            $this->request->session()->set('honeypot_id', $newHoneypotId);

            // Kontrollera fel och hantera potentiella honeypot-fält.
            $errors = $validator->errors();

            // Hämta det förväntade honeypot-id:t från sessionen.
            $newHoneypotId = $this->request->session()->get('honeypot_id');

            // Om honeypot-felet är närvarande, lägg till ett specifikt fel.
            $honeypotErrors = preg_grep('/^hp_/', array_keys($errors));

            if (!empty($honeypotErrors)) {
                // Om det finns minst en nyckel som börjar med 'hp_', hantera felet.
                $validator->addError('form-error', 'Det verkar som att du försöker skicka spam. Försök igen.');
            }

            return $this->view('votes.voter.index', [
                'honeypotId' => $newHoneypotId, // Skicka det nya id:t till vyn
                'errors' => $validator->errors(),
            ]);
        }

        $data = $this->request->filterFields($data);
        $this->request->session()->remove('honeypot_id');
        $this->request->session()->remove('old');

        $token = new Token();
        $tokenValue = $token->value();

        $voter = new Voter();
        $voter->fill([
            'email' => $data['email'],
            'activation' => $token->hashHmac(),
        ]);

        if (isset($data['password']) && is_string($data['password']) && $data['password'] !== '') {
            $password = $data['password']; // här vet PHPStan att det är string

            $voter->password = $password;
        }

        $voter->save();

        $activationLink = getenv('APP_URL') . route('voter.activate', ['token' => $tokenValue]);

        $email  = is_string($data['email'] ?? null) ? $data['email'] : '';

        // Skicka e-postmeddelande
        $this->eventDispatcher->dispatch(new VoterRegisteredEvent(
            email: $email,
            activationLink: $activationLink,
            context: VoterContext::Activate
        ));

        // Ställ in flash-meddelande och omdirigera
        $this->request->session()->setFlashMessage(
            "$email har registrerats. Kolla din email för aktiveringslänken."
        );

        return new RedirectResponse(route('home.index'));
    }

    public function activate(string $token): Response
    {
        $token = new Token($token);
        $hashedToken = $token->hashHmac();

        // Hämta statusposten som matchar
        $voter = Voter::where('activation', '=', $hashedToken)->first();

        if (!$voter) {
            // Posten hittades inte, hantera felet
            $this->request->session()->setFlashMessage('Aktiveringslänken är ogiltig eller så har du redan aktiverats', 'error');
            return new RedirectResponse(route('voter.index'));
        }

        $voter->fill(['status' => 'activated', 'activation' => null]);
        $voter->save();               // Spara modellen

        // Ställ in flashmeddelande och omdirigera
        $this->request->session()->setFlashMessage('Du har nu aktiverats, och kan nu rösta.');
        return new RedirectResponse(route('home.index'));
    }

    public function unregister(): Response
    {
        $honeypotId = generate_honeypot_id();

        // Spara id:t i sessionen
        $this->request->session()->set('honeypot_id', $honeypotId);

        return $this->view('votes.voter.unregister', [
            'honeypotId' => $honeypotId, // Skicka också till vyn
        ]);
    }

    public function store(): Response
    {
        $this->before();
        $data = $this->request->post;

        $expectedHoneypotId = $this->request->session()->get('honeypot_id');

        if (!is_string($expectedHoneypotId) || $expectedHoneypotId === '') {
            return new RedirectResponse(route('voter.password-forgot.index')); // Tillbaka till formuläret
        }

        // Validera inkommande data
        $validator = new Validator($data, [
            'email' => 'required|email',
            'password' => 'required',
            $expectedHoneypotId => 'honeypot', // Dynamisk validering
        ]);

        if (!$validator->validate()) {
            $newHoneypotId = generate_honeypot_id();
            $this->request->session()->set('honeypot_id', $newHoneypotId);

            // Kontrollera fel och hantera potentiella honeypot-fält.
            $errors = $validator->errors();

            // Hämta det förväntade honeypot-id:t från sessionen.
            $newHoneypotId = $this->request->session()->get('honeypot_id');

            // Om honeypot-felet är närvarande, lägg till ett specifikt fel.
            $honeypotErrors = preg_grep('/^hp_/', array_keys($errors));

            if (!empty($honeypotErrors)) {
                // Om det finns minst en nyckel som börjar med 'hp_', hantera felet.
                $validator->addError('form-error', 'Det verkar som att du försöker skicka spam. Försök igen.');
            }

            return $this->view('votes.voter.unregister', [
                'honeypotId' => $newHoneypotId, // Skicka det nya id:t till vyn
                'errors' => $validator->errors(),
            ]);
        }

        // Försök autentisera väljaren centralt (inkl. throttling/status)
        $errors = [];
        $voter = $this->voterService->authenticate($data, $errors);

        if (!$voter) {
            // Lägg in alla fel från authenticate (t.ex. throttling/status/lösenord)
            foreach ($errors as $field => $messages) {
                foreach ($messages as $msg) {
                    $validator->addError($field, $msg);
                }
            }
            // Behåll/uppdatera honeypot-id för ny rendering
            $newHoneypotId = generate_honeypot_id();
            $this->request->session()->set('honeypot_id', $newHoneypotId);

            return $this->view('votes.voter.unregister', [
                'honeypotId' => $newHoneypotId,
                'errors'  => $validator->errors(),
            ]);
        }

        // Sanera data
        $data = $this->request->filterFields($data);
        $this->request->session()->remove('honeypot_id');

        $token = new Token();
        $tokenValue = $token->value();

        $voter->fill([
            'activation' => $token->hashHmac(),
        ]);

        $voter->save();

        $deactivationLink = getenv('APP_URL') . route('voter.delete', ['token' => $tokenValue]);

        $email  = is_string($data['email'] ?? null) ? $data['email'] : '';

        // Skicka e-postmeddelande
        $this->eventDispatcher->dispatch(new VoterRegisteredEvent(
            email: $email,
            deactivationLink: $deactivationLink,
            context: VoterContext::Deactivate
        ));

        // Ställ in flash-meddelande och omdirigera
        $this->request->session()->setFlashMessage(
            "$email har begärt att avregistrera sig. Kolla din email för avregistreringslänken."
        );

        return new RedirectResponse(route('home.index'));
    }

    public function delete(string $token): Response
    {
        $token = new Token($token);
        $hashedToken = $token->hashHmac();

        // Hämta statusposten som matchar
        $voter = Voter::where('activation', '=', $hashedToken)->first();

        if (!$voter) {
            // Posten hittades inte, hantera felet
            $this->request->session()->setFlashMessage('Avregistreringslänken är ogiltig', 'error');
            return new RedirectResponse(route('voter.unregister'));
        }

        return $this->view('votes.voter.delete', [
            'token' => $token->value(),
        ]);
    }

    public function remove(string $token): Response
    {
        $this->before();

        $token = new Token($token);
        $hashedToken = $token->hashHmac();
        // Hämta statusposten som matchar
        $voter = Voter::where('activation', '=', $hashedToken)->first();

        if (!$voter) {
            // Posten hittades inte, hantera felet
            $this->request->session()->setFlashMessage('Avregistreringslänken är ogiltig', 'error');
            return new RedirectResponse(route('voter.unregister'));
        }

        $voter->forceDelete();              // Spara modellen

        // Ställ in flashmeddelande och omdirigera
        $this->request->session()->setFlashMessage('Du har nu avregistrerat dig och kan inte längre rösta.', 'error');
        return new RedirectResponse(route('home.index'));
    }
}
