<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Events\VoterRegisteredEvent;
use App\Models\Voter;
use Radix\Controller\AbstractController;
use Radix\EventDispatcher\EventDispatcher;
use Radix\Http\RedirectResponse;
use Radix\Http\Response;
use Radix\Support\Token;

class VoterController extends AbstractController
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

        $voters = Voter::withCount('vote')
            ->paginate(10, (int)$page);

        return $this->view('admin.voter.index', ['voters' => $voters]);
    }

    public function sendActivation(string $id): Response
    {
        $this->before();

        $voter = Voter::find($id);

        if (!$voter) {
            $this->request->session()->setFlashMessage(
                "Användare kunde inte hittas."
            );

            return new RedirectResponse(route('admin.user.index'));
        }

        $token = new Token();
        $tokenValue = $token->value();

        $voter->fill([
            'activation' => $token->hashHmac(),
            'status' => 'activate'
        ]);

        $voter->save();

        $activationLink = getenv('APP_URL') . route('voter.activate', ['token' => $tokenValue]);

        // Skicka e-postmeddelande
        $this->eventDispatcher->dispatch(new VoterRegisteredEvent(
            email: $voter->email,
            activationLink: $activationLink
        ));

        // Ställ in flash-meddelande och omdirigera
        $this->request->session()->setFlashMessage(
            "Aktiveringslänk skickad till $voter->email."
        );

        $rawPage = $this->request->get['page'] ?? 1;

        if (!is_int($rawPage) && !is_string($rawPage)) {
            // Fallback om någon skickar något knasigt
            $rawPage = 1;
        }

        /** @var int|string $rawPage */
        $currentPage = (int) $rawPage;

        return new RedirectResponse(route('admin.voter.index') . '?page=' . $currentPage);
    }

    public function block(string $id): Response
    {
        $this->before();

        $voter = Voter::find($id);

        if (!$voter) {
            $this->request->session()->setFlashMessage(
                "Röstberättigad kunde inte hittas."
            );

            return new RedirectResponse(route('admin.voter.index'));
        }

        $voter->fill([
            'status' => 'blocked',
            'active' => 'offline',
        ]);

        $voter->save();

        $this->request->session()->setFlashMessage(
            "Röstberättigad $voter->email har blockerats."
        );

        $rawPage = $this->request->get['page'] ?? 1;

        if (!is_int($rawPage) && !is_string($rawPage)) {
            // Fallback om någon skickar något knasigt
            $rawPage = 1;
        }

        /** @var int|string $rawPage */
        $currentPage = (int) $rawPage;

        return new RedirectResponse(route('admin.voter.index') . '?page=' . $currentPage);
    }
}