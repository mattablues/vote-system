<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Subject;
use App\Models\Voter;
use App\Models\Vote;

readonly class SubjectService
{
    public function __construct(private VoterService $voterService)
    {
    }

    /**
     * Skapa nytt ämne och första röst.
     * Returnerar array med ['subject' => Subject, 'vote' => Vote].
     * Vid fel, fyll $errors likt VoteController::create.
     *
     * @param array<string, mixed>        $data
     * @param array<string, list<string>> $errors
     * @return array{subject: Subject, vote?: Vote}|null
     */
    public function createSubject(array $data, array &$errors): ?array
    {
        // 1) Autentisera väljaren via central logik
        $voter = $this->voterService->authenticate($data, $errors);
        if (!$voter instanceof Voter) {
            return null;
        }

        // 2) Skapa ämnet (exempel — komplettera med faktisk logik)
        $catIdVal = $data['category_id'] ?? 0;
        $categoryId = is_numeric($catIdVal) ? (int) $catIdVal : 0;

        $subVal = $data['subject'] ?? '';
        $subjectText = is_string($subVal) ? $subVal : '';

        $subject = new Subject();
        $subject->fill([
            'category_id' => $categoryId,
            'subject' => $subjectText,
            'published' => 0,
        ]);

        $subject->save();

        // 3) Eventuellt: skapa första röst om din UX kräver det
        // $vote = $this->voterService->castVote($subject, $voter, (int) $data['vote']);

        return ['subject' => $subject /*, 'vote' => $vote */];
    }
}