<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Category;
use App\Models\Subject;
use Radix\Controller\AbstractController;
use Radix\Http\Response;

class HomeController extends AbstractController
{
    public function index(): Response
    {
        $subjects = Subject::with(['category'])
            ->withCount('vote')
            ->limit(4)
            ->where('published', '=', 1)
            ->orderBy('vote_count', 'desc')->get();

        $latestSubjects = Subject::limit(3)
            ->where('published', '=', 1)
            ->orderBy('id', 'desc')->get();

        $categories = Category::withCount('subject')
            ->withCount('subject')
            ->withCount('vote')
            ->limit(4)
            ->orderBy('subject_count', 'desc')
            ->orderBy('vote_count', 'desc')->get();

        $latestCategories = Category::limit(3)
            ->orderBy('id', 'desc')->get();

        return $this->view('home.index', [
            'subjects' => $subjects,
            'latestSubjects' => $latestSubjects,
            'categories' => $categories,
            'latestCategories' => $latestCategories,
        ]);
    }
}