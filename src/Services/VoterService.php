<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Subject;
use App\Models\Vote;
use App\Models\Voter;

readonly class VoterService
{
    public function __construct(private VoterAuthService $throttle) {}

    /**
     * Verifiera väljare och returnera modellen eller null.
     * Sätter felmeddelande i $errors['form-error'][] vid misslyckande.
     *
     * @param array<string, mixed>       $data
     * @param array<string, list<string>> $errors
     */
    public function authenticate(array $data, array &$errors): ?Voter
    {
        $rawEmail = $data['email'] ?? '';
        $email = is_string($rawEmail) ? $rawEmail : '';

        if ($email !== '' && $this->throttle->isBlocked($email)) {
            $errors['form-error'][] = 'För många försök. Försök igen senare.';
            return null;
        }

        $voter = Voter::where('email', '=', $email)->first();

        $rawPassword = $data['password'] ?? '';
        $inputPassword = is_string($rawPassword) ? $rawPassword : '';

        // Hämta hash säkert och casta till string (fetchGuardedAttribute returnerar mixed)
        $rawHash = ($voter instanceof Voter) ? $voter->fetchGuardedAttribute('password') : '';
        $hash = is_string($rawHash) ? $rawHash : '';

        if (!$voter instanceof Voter || !password_verify($inputPassword, $hash)) {
            if ($email !== '') {
                $this->throttle->trackFailedAttempt($email);
            }
            $errors['form-error'][] = 'Ogiltig e-postadress/lösenord.';
            return null;
        }

        $status = $voter->status ?? null;

        if ($status === 'blocked') {
            $errors['form-error'][] = 'Du är blockerad, kontakta supporten.';
            return null;
        }

        if ($status !== 'activated') {
            $errors['form-error'][] = 'Du har inte aktiverat dig.';
            return null;
        }

        if ($email !== '') {
            $this->throttle->clearFailedAttempts($email);
        }

        return $voter;
    }

    /**
     * Kontrollera om väljaren redan röstat på ämnet.
     */
    public function hasAlreadyVoted(Subject $subject, Voter $voter): bool
    {
        return (bool) Vote::where('subject_id', '=', $subject->id)
            ->where('voter_id', '=', $voter->id)
            ->first();
    }

    /**
     * Skapa röst.
     */
    public function castVote(Subject $subject, Voter $voter, int $voteValue): Vote
    {
        $vote = new Vote();
        $vote->fill([
            'subject_id' => $subject->id,
            'voter_id'   => $voter->id,
            'vote'       => $voteValue,
            'voted_at'   => date('Y-m-d H:i:s'),
        ]);

        $vote->save();

        return $vote;
    }
}
