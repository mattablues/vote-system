<?php

declare(strict_types=1);

namespace App\Controllers\Votes;

use App\Events\CreateSubjectEvent;
use App\Models\Category;
use App\Models\Subject;
use App\Models\Vote;
use App\Services\VoterSessionService;
use Radix\Controller\AbstractController;
use Radix\EventDispatcher\EventDispatcher;
use Radix\Http\RedirectResponse;
use Radix\Http\Response;
use Radix\Support\Validator;

class SubjectController extends AbstractController
{
    public function __construct(
        public readonly EventDispatcher $eventDispatcher,
        private readonly VoterSessionService $voterSession
    ) {}

    public function index(): Response
    {
        $rawPage = $this->request->get['page'] ?? 1;

        if (!is_int($rawPage) && !is_string($rawPage)) {
            // Fallback om någon skickar något knasigt
            $rawPage = 1;
        }

        /** @var int|string $rawPage */
        $page = (int) $rawPage;

        $subjects = Subject::with(['category'])
            ->withCount('vote')
            ->withCountWhere('vote', 'vote', 0)
            ->withCountWhere('vote', 'vote', 1)
            ->withCountWhere('vote', 'vote', 2)
            ->where('published', '=', 1)
            ->orderBy('vote_count', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(10, $page);

        $isAuth = $this->voterSession->isAuthenticated();
        $alreadyVotedIds = [];

        if ($isAuth) {
            $voter = $this->voterSession->current();

            if ($voter) {
                $rows = Vote::select(['subject_id'])
                    ->where('voter_id', '=', $voter->id)
                    ->get();
                foreach ($rows as $row) {
                    $val = $row->getAttribute('subject_id');
                    $alreadyVotedIds[] = is_numeric($val) ? (int) $val : null;
                }
            }
        }

        return $this->view('votes.subject.index', [
            'subjects' => $subjects,
            'isVoterAuthenticated' => $isAuth,
            'subjectIdsAlreadyVoted' => $alreadyVotedIds,
        ]);
    }

    public function create(): Response
    {
        // Generera och spara hp-id för första visningen
        $honeypotId = generate_honeypot_id();
        $this->request->session()->set('honeypot_id', $honeypotId);

        $categories = Category::all();

        return $this->view('votes.subject.create', [
            'honeypotId' => $honeypotId,
            'categories' => $categories,
        ]);
    }

    public function store(): Response
    {
        $this->before();
        $data = $this->request->post;

        if (!$this->request->session()->get('honeypot_id')) {
            return new RedirectResponse(route('contact.index'));
        }

        $categories = Category::all();

        // Email/lösenord tas bort – voter är redan inloggad via middleware
        $validator = new Validator($data, [
            'category_id' => 'required|numeric',
            'subject'     => 'required|min:5|max:300|unique:App\Models\Subject,subject',
            'honeypot'    => 'honeypot_dynamic',
        ]);

        if (!$validator->validate()) {
            $this->request->session()->set('old', $data);

            $newHoneypotId = $validator->regenerateHoneypotId(fn() => generate_honeypot_id());

            return $this->view('votes.subject.create', [
                'honeypotId' => $newHoneypotId,
                'categories' => $categories,
                'errors'     => $validator->errors(),
            ]);
        }

        $filtered = $this->request->filterFields($data);
        $this->request->session()->remove('old');

        $catVal = $filtered['category_id'] ?? 0;
        $categoryId = is_numeric($catVal) ? (int) $catVal : 0;

        $subVal = $filtered['subject'] ?? '';
        $subjectText = is_string($subVal) ? $subVal : '';

        // Skapa ämnet utan voter-auth i service – väljaren är redan verifierad
        $subject = new Subject();
        $subject->fill([
            'category_id' => $categoryId,
            'subject'     => $subjectText,
            'published'   => 0,
        ]);
        $subject->save();

        /** @var \App\Models\Category|null $category */
        $category = Category::find($categoryId);

        // Hämta kategorinamn säkert
        $categoryNameRaw = ($category instanceof Category) ? $category->getAttribute('category') : '';
        $categoryName = is_string($categoryNameRaw) ? $categoryNameRaw : '';
        $email  = is_string($filtered['email'] ?? null) ? $filtered['email'] : '';

        $this->eventDispatcher->dispatch(new CreateSubjectEvent(
            email: $email,
            category: $categoryName,
            subject: $subjectText,
        ));

        $this->request->session()->remove('honeypot_id');
        $this->request->session()->remove('old');
        $this->request->session()->setFlashMessage('Ämnet har skapades och kommer att granskas innan det publiceras.');

        return new RedirectResponse(route('votes.subject.index'));
    }
}
