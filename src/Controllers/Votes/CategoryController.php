<?php

declare(strict_types=1);

namespace App\Controllers\Votes;

use App\Models\Category;
use App\Models\Vote;
use App\Services\VoterSessionService;
use Radix\Controller\AbstractController;
use Radix\Http\Response;

class CategoryController extends AbstractController
{
    public function __construct(
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

        $categories = Category::withCount('subject')->withCount('vote')
            ->orderBy('subject_count', 'desc')
            ->orderBy('vote_count', 'desc')
            ->paginate(10, $page);

        return $this->view('votes.category.index', ['categories' => $categories]);
    }

    public function show(string $id): Response
    {
        $rawPage = $this->request->get['page'] ?? 1;

        if (!is_int($rawPage) && !is_string($rawPage)) {
            // Fallback om någon skickar något knasigt
            $rawPage = 1;
        }

        /** @var int|string $rawPage */
        $page = (int) $rawPage;

        $subjects = \App\Models\Subject::where('category_id', '=', $id)
            ->withCount('vote')
            ->withCountWhere('vote', 'vote', 0)
            ->withCountWhere('vote', 'vote', 1)
            ->withCountWhere('vote', 'vote', 2)
            ->orderBy('vote_count', 'desc')
            ->paginate(10, $page);

        $category = Category::with('subject')->where('id', '=', $id)->first();

        $isAuth = $this->voterSession->isAuthenticated();
        $alreadyVotedIds = [];

        if ($isAuth) {
            $voter = $this->voterSession->current();
            if ($voter) {
                $rows = Vote::select(['subject_id'])
                    ->where('voter_id', '=', (int) $voter->id)
                    ->get();
                foreach ($rows as $row) {
                    $val = $row->getAttribute('subject_id');
                    $alreadyVotedIds[] = is_numeric($val) ? (int) $val : null;
                }
            }
        }

        return $this->view('votes.category.show', [
            'category' => $category,
            'subjects' => $subjects,
            'isVoterAuthenticated' => $isAuth,
            'subjectIdsAlreadyVoted' => $alreadyVotedIds,
        ]);
    }
}
