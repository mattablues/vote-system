<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Events\UserRegisteredEvent;
use App\Models\Status;
use App\Models\User;
use Radix\Controller\AbstractController;
use Radix\Enums\Role;
use Radix\Enums\UserActivationContext;
use Radix\EventDispatcher\EventDispatcher;
use Radix\Http\RedirectResponse;
use Radix\Http\Response;
use Radix\Support\Token;
use Radix\Support\Validator;

class UserController extends AbstractController
{
    public function __construct(private readonly EventDispatcher $eventDispatcher)
    {
    }

    public function index(): Response
    {
        $rawPage = $this->request->get['page'] ?? 1;

        if (!is_int($rawPage) && !is_string($rawPage)) {
            // Fallback om någon skickar något knasigt
            $rawPage = 1;
        }

        /** @var int|string $rawPage */
        $page = (int) $rawPage;

        $users = User::with('status')->paginate(10, $page);

        return $this->view('admin.user.index', ['users' => $users]);
    }

    public function create(): Response
    {
        return $this->view('admin.user.create');
    }

    public function store(): Response
    {
        $this->before();

        $data = $this->request->post; // Hämta formulärdata

        // Validera data inklusive avatar
        $validator = new Validator($data, [
            'first_name' => 'required|min:2|max:15',
            'last_name' => 'required|min:2|max:15',
            'email' => 'required|email|unique:App\Models\User,email',
        ]);

        if (!$validator->validate()) {
            // Om validering misslyckas, lagra gamla indata och returnera vy med felmeddelanden
            $this->request->session()->set('old', $data);

            return $this->view('admin.user.create', [
                'errors' => $validator->errors(),
            ]);
        }

        // Rensa och filtrera data innan lagring
        $data = $this->request->filterFields($data);
        $this->request->session()->remove('old');

        // Skapa en ny användare
        $user = new User();
        $user->fill([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
        ]);

        $password = generate_password();

        // Sätt lösenord unikt (handled)
        $user->password = $password;
        $user->save();

        // Skapa en API-token i tokens-tabellen
        \App\Models\Token::createToken((int)$user->id, 'API Token for user registration');

        // Skapa token
        $token = new Token();
        $tokenValue = $token->value();

        // Skapa och fyll statusobjekt
        $status = new Status();

        $status->fill([
            'activation' => $token->hashHmac(),
        ]);

        // Explicit sätt guardat fält
        $status->user_id = $user->id;
        $status->save();

        $activationLink = getenv('APP_URL') . route('auth.register.activate', ['token' => $tokenValue]);

        $firstName = is_string($data['first_name'] ?? null) ? $data['first_name'] : '';
        $lastName  = is_string($data['last_name'] ?? null) ? $data['last_name'] : '';
        $email  = is_string($data['email'] ?? null) ? $data['email'] : '';

        // Skicka e-postmeddelande
        $this->eventDispatcher->dispatch(new UserRegisteredEvent(
            email: $email,
            firstName: $firstName,
            lastName: $lastName,
            activationLink: $activationLink,
            password: $password,
            context: UserActivationContext::Admin,
        ));

        // Ställ in flash-meddelande och omdirigera
        $this->request->session()->setFlashMessage(
            "Konto har skapats för $firstName $lastName och aktiveringslänk skickad."
        );

        return new RedirectResponse(route('admin.user.index'));
    }

    public function sendActivation(string $id): Response
    {
        $this->before();

        $user = User::find($id);

        if (!$user) {
            $this->request->session()->setFlashMessage(
                "Användare kunde inte hittas."
            );

            return new RedirectResponse(route('admin.user.index'));
        }

        $token = new Token();
        $tokenValue = $token->value();

        $user->loadMissing('status');

        /** @var Status|null $status */
        $status = $user->getRelation('status');

        if (!$status instanceof Status) {
            throw new \RuntimeException('Status relation is not loaded or invalid.');
        }

        $status->fill([
            'activation' => $token->hashHmac(),
            'status' => 'activate'
        ]);

        $status->save();

        $activationLink = getenv('APP_URL') . route('auth.register.activate', ['token' => $tokenValue]);

        // Skicka e-postmeddelande
        $this->eventDispatcher->dispatch(new UserRegisteredEvent(
            email: $user->email,
            firstName: $user->first_name,
            lastName: $user->last_name,
            activationLink: $activationLink,
            context: UserActivationContext::Resend,
        ));

        // Ställ in flash-meddelande och omdirigera
        $this->request->session()->setFlashMessage(
            "Aktiveringslänk skickad till $user->email."
        );

        $rawPage = $this->request->get['page'] ?? 1;

        if (!is_int($rawPage) && !is_string($rawPage)) {
            // Fallback om någon skickar något knasigt
            $rawPage = 1;
        }

        /** @var int|string $rawPage */
        $currentPage = (int) $rawPage;

        return new RedirectResponse(route('admin.user.index') . '?page=' . $currentPage);
    }

    public function block(string $id): Response
    {
        $this->before();

        $user = User::find($id);

        if (!$user) {
            $this->request->session()->setFlashMessage(
                "Användare kunde inte hittas."
            );

            return new RedirectResponse(route('admin.user.index'));
        }

        $user->loadMissing('status');

        /** @var Status|null $status */
        $status = $user->getRelation('status');

        if (!$status instanceof Status) {
            throw new \RuntimeException('Status relation is not loaded or invalid.');
        }

        $status->fill([
            'status' => 'blocked',
            'active' => 'offline',
        ]);

        $status->save();

        $this->request->session()->setFlashMessage(
            "onto för $user->first_name $user->last_name har blockerats."
        );

        $rawPage = $this->request->get['page'] ?? 1;

        if (!is_int($rawPage) && !is_string($rawPage)) {
            // Fallback om någon skickar något knasigt
            $rawPage = 1;
        }

        /** @var int|string $rawPage */
        $currentPage = (int) $rawPage;

        return new RedirectResponse(route('admin.user.index') . '?page=' . $currentPage);
    }

    public function closed(): Response
    {
        $this->before();

        $rawPage = $this->request->get['page'] ?? 1;

        if (!is_int($rawPage) && !is_string($rawPage)) {
            // Fallback om någon skickar något knasigt
            $rawPage = 1;
        }

        /** @var int|string $rawPage */
        $page = (int) $rawPage;

        $users = User::with('status')->getOnlySoftDeleted()->paginate(10, $page);

        return $this->view('admin.user.closed', ['users' => $users]);
    }

    public function restore(string $id): Response
    {
        $this->before();

        $user = User::find($id, true);

        if (!$user) {
            $rawPage = $this->request->get['page'] ?? 1;

            if (!is_int($rawPage) && !is_string($rawPage)) {
                // Fallback om någon skickar något knasigt
                $rawPage = 1;
            }

            /** @var int|string $rawPage */
            $currentPage = (int) $rawPage;

            $this->request->session()->setFlashMessage(
                "Användare kunde inte hittas."
            );

            return new RedirectResponse(route('admin.user.closed'). '?page=' . $currentPage);
        }

        $user->restore();

        $token = new Token();
        $tokenValue = $token->value();

        $user->loadMissing('status');

        /** @var Status|null $status */
        $status = $user->getRelation('status');

        if (!$status instanceof Status) {
            throw new \RuntimeException('Status relation is not loaded or invalid.');
        }

        $status->fill([
            'activation' => $token->hashHmac(),
            'status' => 'activate',
        ]);

        $status->save();

        $activationLink = getenv('APP_URL') . route('auth.register.activate', ['token' => $tokenValue]);

        // Skicka e-postmeddelande
        $this->eventDispatcher->dispatch(new UserRegisteredEvent(
            email: $user->email,
            firstName: $user->first_name,
            lastName: $user->last_name,
            activationLink: $activationLink,
            context: UserActivationContext::Resend
        ));

        $this->request->session()->setFlashMessage(
            "Konto för $user->first_name $user->last_name har återställts, aktiveringslänk skickad."
        );

        return new RedirectResponse(route('admin.user.index'));
    }

    public function role(string $id): Response
    {
        $this->before();

        $rawRoleInput = $this->request->post['role'] ?? null;

        if (!is_string($rawRoleInput)) {
            $this->request->session()->setFlashMessage('Något blev fel, prova igen.', 'error');

            return new RedirectResponse(route('user.show', ['id' => $id]));
        }

        $roleInput = $rawRoleInput;
        $roleEnum = Role::tryFromName($roleInput);

        if ($roleEnum === null) {
            $this->request->session()->setFlashMessage('Ogiltig behörighetsnivå angiven.', 'error');

            return new RedirectResponse(route('user.show', ['id' => $id]));
        }

        $user = User::find($id);

        if ($user === null) {
            $this->request->session()->setFlashMessage('Användare saknas.', 'error');

            return new RedirectResponse(route('user.show', ['id' => $id]));
        }

        if ($user->isAdmin()) {
            $this->request->session()->setFlashMessage('Du kan inte ändra en admin.', 'error');

            return new RedirectResponse(route('admin.user.index'));
        }

        $user->setRole($roleEnum);
        $user->save();

        $roleName = $roleEnum->value;

        $this->request->session()->setFlashMessage(
            "$user->first_name $user->last_name har tilldelats behörighet {$roleName}"
        );

        return new RedirectResponse(route('user.show', ['id' => $user->id]));
    }
}