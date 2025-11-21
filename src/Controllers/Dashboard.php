<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Category;
use App\Models\Subject;
use App\Models\Vote;
use App\Models\Voter;
use Radix\Controller\AbstractController;
use Radix\Http\Response;

class Dashboard extends AbstractController
{
    public function index(): Response
    {
        $numCategories = Category::count()->int();
        $numSubjectsPublished = Subject::where('published', '=', 1)->count()->int();
        $numSubjectsUnpublished = Subject::where('published', '=', 0)->count()->int();
        $numActivatedVoters = Voter::where('status', '=', 'activated')->count()->int();
        $numUnactivatedVoters = Voter::where('status', '=', 'activate')->count()->int();
        $numBlockedVoters = Voter::where('status', '=', 'blocked')->count()->int();
        $numVotes = Vote::count()->int();

        return $this->view('dashboard.index', [
            'numCategories' => $numCategories,
            'numSubjectsPublished' => $numSubjectsPublished,
            'numSubjectsUnpublished' => $numSubjectsUnpublished,
            'numActivatedVoters' => $numActivatedVoters,
            'numUnactivatedVoters' => $numUnactivatedVoters,
            'numBlockedVoters' => $numBlockedVoters,
            'numVotes' => $numVotes,
        ]);
    }
}
