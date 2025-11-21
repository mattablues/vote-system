<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Models\Status;
use App\Models\User;
use Radix\Controller\AbstractController;
use Radix\Http\RedirectResponse;
use Radix\Http\Response;
use Radix\Support\Token;
use Radix\Support\Validator;

class PasswordResetController extends AbstractController
{
    public function index(string $token): Response
    {
        $token = new Token($token);
        $hashedToken = $token->hashHmac();

        $status = Status::where('password_reset', '=', $hashedToken)->first();

        if (!$status) {
            $this->request->session()->setFlashMessage(
                'Återställningslänken är inte giltig, begär en ny.',
                'error'
            );

            return new RedirectResponse(route('auth.password-forgot.index'));
        }

        /** @var Status $status */
        if (strtotime($status->reset_expires_at) < time()) {
            $this->request->session()->setFlashMessage(
                'Återställningslänken är inte giltig, begär en ny.',
                'error'
            );

            return new RedirectResponse(route('auth.password-forgot.index'));
        }

        return $this->view('auth.password-reset.index', [
            'token' => $token->value(),
        ]);
    }

    public function create(string $token): Response
    {
        $this->before();
        $data = $this->request->post;

        $validator = new Validator($data, [
            'password' => 'required|min:8|max:15',
            'password_confirmation' => 'required|confirmed:password',
        ]);

        if (!$validator->validate()) {
            return $this->view('auth.password-reset.index', [
                'token' => $token,
                'errors' => $validator->errors(),
            ]);
        }

        $rawToken = $data['token'] ?? null;
        if (!is_string($rawToken) || $rawToken === '') {
            $this->request->session()->setFlashMessage(
                'Återställningslänken är inte giltig, begär en ny.',
                'error'
            );
            return new RedirectResponse(route('auth.password-forgot.index'));
        }

        $token = new Token($rawToken);
        $hashedToken = $token->hashHmac();

        $status = Status::where('password_reset', '=', $hashedToken)->first();

        if (!$status) {
            $this->request->session()->setFlashMessage(
                'Återställningslänken är inte giltig, begär en ny.',
                'error'
            );

            return new RedirectResponse(route('auth.password-forgot.index'));
        }

        /** @var Status $status */
        if (strtotime($status->reset_expires_at) < time()) {
            $this->request->session()->setFlashMessage(
                'Återställningslänken är inte giltig, begär en ny.',
                'error'
            );

            return new RedirectResponse(route('auth.password-forgot.index'));
        }

        $status->loadMissing('user');

        $user = $status->getRelation('user');

        if (!$user) {
            $this->request->session()->setFlashMessage(
                'Något gick fel. Försök igen senare.',
                'error'
            );

            return new RedirectResponse(route('auth.password-forgot.index'));
        }

        /** @var User $user */
        $status->fill(['password_reset' => null, 'reset_expires_at' => null]);
        $status->save();

        if (isset($data['password']) && is_string($data['password']) && $data['password'] !== '') {
            $password = $data['password']; // här vet PHPStan att det är string

            $user->password = $password;
        }

        $user->save();

        $this->request->session()->setFlashMessage('Ditt lösenord har återställts, du kan nu logga in.');

        return new RedirectResponse(route('auth.login.index'));
    }
}
