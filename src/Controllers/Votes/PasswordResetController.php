<?php

declare(strict_types=1);

namespace App\Controllers\Votes;

use App\Models\Voter;
use Radix\Controller\AbstractController;
use Radix\Http\RedirectResponse;
use Radix\Http\Response;
use Radix\Support\Token;
use Radix\Support\Validator;

class PasswordResetController extends AbstractController
{
    public function index(string $token): Response
    {
        $token =  new Token($token);
        $hashedToken = $token->hashHmac();

        $voter = Voter::where('password_reset', '=', $hashedToken)->first();

        /** @var Voter $voter */
        if (!$voter || strtotime($voter->reset_expires_at) < time()) {
            $this->request->session()->setFlashMessage('Återställningslänken är inte giltig, begär en ny.','error');

            return new RedirectResponse(route('votes.password-forgot.index'));
        }

        return $this->view('votes.voter.password-reset', [
            'token' => $token->value()
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
            return $this->view('votes.voter.password-reset', [
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
            return new RedirectResponse(route('voter.password-forgot.index'));
        }

        $token = new Token($rawToken);
        $hashedToken = $token->hashHmac();

        $voter = Voter::where('password_reset', '=', $hashedToken)->first();

        /** @var Voter $voter */
        if (!$voter || strtotime($voter->reset_expires_at) < time()) {
            $this->request->session()->setFlashMessage('Återställningslänken är inte giltig, begär en ny.','error');

            return new RedirectResponse(route('votes.password-forgot.index'));
        }

        $voter->fill(['password_reset' => null, 'reset_expires_at' => null]);
        $voter->save();

        if (isset($data['password']) && is_string($data['password']) && $data['password'] !== '') {
            $password = $data['password']; // här vet PHPStan att det är string

            $voter->password = $password;
        }

        $voter->save();

        $this->request->session()->setFlashMessage('Ditt lösenord har återställts, du kan nu rösta igen.');

        return new RedirectResponse(route('votes.subject.index'));
    }
}