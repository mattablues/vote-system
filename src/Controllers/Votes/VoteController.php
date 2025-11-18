<?php

declare(strict_types=1);

namespace App\Controllers\Votes;

use App\Models\Subject;
use App\Services\VoterService;
use App\Services\VoterSessionService;
use Radix\Controller\AbstractController;
use Radix\Http\RedirectResponse;
use Radix\Http\Response;
use Radix\Support\Validator;

class VoteController extends AbstractController
{
    public function __construct(
        private readonly VoterService $voterService,
        private readonly VoterSessionService $voterSession

    )
    {
    }

    public function index(string $id): Response
    {
        $subject = Subject::with('category')
            ->withCount('vote')
            ->where('id', '=', $id)->first();

        return $this->view('votes.vote.index', [
            'subject' => $subject,
        ]);
    }

    public function create(string $id): Response
    {
        $this->before();

        $data = $this->request->post;

        /** @var \App\Models\Subject|null $subject */
        $subject = Subject::with('category')->where('id', '=', (int) $id)
            ->withCount('vote')
            ->first();

        if (!$subject) {
            // Hantera fallet där ämnet inte finns (t.ex. ogiltigt ID)
            $this->request->session()->setFlashMessage('Ämnet hittades inte.', 'error');
            return new RedirectResponse(route('home.index'));
        }

        // Hämta inloggad väljare (middleware har redan krävt login)
        $currentVoter = $this->voterSession->current();

        $validator = new Validator($data, [
            'vote' => 'required|in:0,1,2',
        ]);

        if (!$validator->validate()) {
            $this->request->session()->set('old', $data);
            return $this->view('votes.vote.index', [
                'subject' => $subject,
                'isVoterAuthenticated' => (bool)$currentVoter,
                'voterEmail' => $this->request->session()->get('voter_email'),
                'errors'  => $validator->errors(),
            ]);
        }

        if ($currentVoter && $this->voterService->hasAlreadyVoted($subject, $currentVoter)) {
            $validator->addError('form-error', 'Du har redan röstat på ämnet.');
            $this->request->session()->set('old', $data);
            return $this->view('votes.vote.index', [
                'subject' => $subject,
                'isVoterAuthenticated' => true,
                'voterEmail' => $this->request->session()->get('voter_email'),
                'errors'  => $validator->errors(),
            ]);
        }

        $filtered = $this->request->filterFields($data);

        if ($currentVoter) {
             // Säkerställ att vi castar till int (filterFields returnerar array<string, mixed>)
             $voteVal = $filtered['vote'] ?? 0;
             $voteValue = is_numeric($voteVal) ? (int) $voteVal : 0;
             $this->voterService->castVote($subject, $currentVoter, $voteValue);
        }

        $this->request->session()->remove('old');
        $this->request->session()->setFlashMessage('Din röst har sparats.');

        $catIdRaw = $subject->getAttribute('category_id');
        $categoryId = is_numeric($catIdRaw) ? (int) $catIdRaw : 0;

        return new RedirectResponse(route('votes.category.show', ['id' => $categoryId]));
    }
}