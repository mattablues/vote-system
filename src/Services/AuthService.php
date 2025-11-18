<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Radix\Session\SessionInterface;

class AuthService
{
    private SessionInterface $session;

    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    // ---- Kontroll av blockering för användare ----
    public function isBlocked(string $email): bool
    {
        $failedKey = $this->getFailedAttemptsKey($email);
        $blockedUntilKey = $this->getBlockedUntilKey($email);

        $failedAttemptsValue = $this->session->get($failedKey, 0);
        $failedAttempts = is_int($failedAttemptsValue) ? $failedAttemptsValue : 0;

        $blockedUntilValue = $this->session->get($blockedUntilKey);
        $blockedUntil = is_int($blockedUntilValue) ? $blockedUntilValue : null;

        // Om användaren är blockerad tills ett visst datum
        if ($blockedUntil !== null && time() < $blockedUntil) {
            return true;
        }

        // Blockera användaren om försöksgränsen överskrids
        if ($failedAttempts >= 5) {
            $blockedTime = time() + 5 * 60; // Blockera i 5 minuter
            $this->session->set($blockedUntilKey, $blockedTime);
            $this->clearFailedAttempts($email, false);
            return true;
        }

        return false;
    }

    // ---- Spåra och hantera misslyckade försök för inloggning ----
    public function trackFailedAttempt(string $email): void
    {
        $failedKey = $this->getFailedAttemptsKey($email);
        $value = $this->session->get($failedKey, 0);
        $failedAttempts = is_int($value) ? $value : 0;

        $this->session->set($failedKey, $failedAttempts + 1);
    }

    public function clearFailedAttempts(string $email, bool $removeBlocked = true): void
    {
        $failedKey = $this->getFailedAttemptsKey($email);
        $blockedUntilKey = $this->getBlockedUntilKey($email);

        $this->session->remove($failedKey);

        if ($removeBlocked) {
            $this->session->remove($blockedUntilKey);
        }
    }

    public function getBlockedUntil(string $email): ?int
    {
        $blockedUntilKey = $this->getBlockedUntilKey($email);
        $value = $this->session->get($blockedUntilKey);

        if ($value === null) {
            return null;
        }

        return is_int($value) ? $value : null;
    }

    // ---- Kontroll och hantering för lösenordsåterställning ----
    public function isPasswordResetBlocked(string $email): bool
    {
        $failedKey = $this->getFailedResetAttemptsKey($email);
        $blockedUntilKey = $this->getBlockedResetUntilKey($email);

        $failedAttemptsValue = $this->session->get($failedKey, 0);
        $failedAttempts = is_int($failedAttemptsValue) ? $failedAttemptsValue : 0;

        $blockedUntilValue = $this->session->get($blockedUntilKey);
        $blockedUntil = is_int($blockedUntilValue) ? $blockedUntilValue : null;

        if ($blockedUntil !== null && time() < $blockedUntil) {
            return true;
        }

        if ($failedAttempts >= 5) {
            $blockedTime = time() + 5 * 60; // Blockera lösenordsåterställning i 5 minuter
            $this->session->set($blockedUntilKey, $blockedTime);
            $this->session->remove($failedKey);
            return true;
        }

        return false;
    }

    public function trackFailedPasswordResetAttempt(string $email): void
    {
        $failedKey = $this->getFailedResetAttemptsKey($email);
        $value = $this->session->get($failedKey, 0);
        $failedAttempts = is_int($value) ? $value : 0;

        $this->session->set($failedKey, $failedAttempts + 1);
    }

    public function clearFailedResetAttempts(string $email, bool $removeBlocked = true): void
    {
        $failedKey = $this->getFailedResetAttemptsKey($email);
        $blockedUntilKey = $this->getBlockedResetUntilKey($email);

        $this->session->remove($failedKey);

        if ($removeBlocked) {
            $this->session->remove($blockedUntilKey);
        }
    }

    // ---- IP-baserad blockering och försök ----
    public function isIpBlocked(string $ip): bool
    {
        $failedKey = $this->getFailedIpAttemptsKey($ip);
        $blockedUntilKey = $this->getBlockedIpUntilKey($ip);

        $failedAttemptsValue = $this->session->get($failedKey, 0);
        $failedAttempts = is_int($failedAttemptsValue) ? $failedAttemptsValue : 0;

        $blockedUntilValue = $this->session->get($blockedUntilKey);
        $blockedUntil = is_int($blockedUntilValue) ? $blockedUntilValue : null;

        if ($blockedUntil !== null && time() < $blockedUntil) {
            return true;
        }

        if ($failedAttempts >= 20) {
            $blockedTime = time() + 10 * 60; // Blockera IP i 10 minuter
            $this->session->set($blockedUntilKey, $blockedTime);
            $this->session->remove($failedKey);
            return true;
        }

        return false;
    }

    public function trackFailedIpAttempt(string $ip): void
    {
        $failedKey = $this->getFailedIpAttemptsKey($ip);
        $value = $this->session->get($failedKey, 0);
        $failedAttempts = is_int($value) ? $value : 0;

        $this->session->set($failedKey, $failedAttempts + 1);
    }

    public function clearFailedIpAttempt(string $ip): void
    {
        $failedKey = $this->getFailedIpAttemptsKey($ip);
        $this->session->remove($failedKey);
    }

    public function getBlockedIpUntil(string $ip): ?int
    {
        $blockedUntilKey = $this->getBlockedIpUntilKey($ip);
        $value = $this->session->get($blockedUntilKey);

        if ($value === null) {
            return null;
        }

        // Säkerställ att vi bara returnerar int eller null
        return is_int($value) ? $value : null;
    }

    /**
     * @param array{
     *     email: string,
     *     password: string
     * } $data
     */
    public function login(array $data): ?User
    {
        $email = $data['email'];

        if ($this->isBlocked($email)) {
            return null;
        }

        // Uppdatera frågan för att inkludera soft-deleted användare
        $user = User::withSoftDeletes() // Aktivera soft-deletes
            ->select(['id', 'first_name', 'last_name', 'email'])
            ->where('email', '=', $email)
            ->first(); // Hämta den faktiska användaren

        // Säkerställ att resultatet är en User-instans
        if (!$user instanceof User) {
            $this->trackFailedAttempt($email);
            return null;
        }

        // Validera lösenord
        $password = $user->fetchGuardedAttribute('password');
        $user->forceFill(['password' => $password]);

        if (!$user->isPasswordValid($data['password'])) {
            $this->trackFailedAttempt($email);
            return null;
        }

        // Rensa tidigare misslyckade försök
        $this->clearFailedAttempts($email);

        return $user;
    }

    // ---- Hjälpmeddelanden baserat på kontostatus -----
    public function getStatusError(?User $user): ?string
    {
        if (!$user) {
            return 'Ogiltig e-postadress/lösenord.';
        }

        // Kontrollera om användarens konto är soft-deleted via fetchGuardedAttribute
        $deletedAt = $user->fetchGuardedAttribute('deleted_at');

        if ($deletedAt !== null) {
            return 'Ditt konto är stängt.';
        }

        /** @var \App\Models\Status|null $status */
        $status = $user->getRelation('status') ?? $user->status()->first();
        $userStatus = $status?->status;

        return match ($userStatus) {
            null => 'Konto saknar status.',
            'activate' => 'Ditt konto är inte aktiverat.',
            'blocked' => 'Ditt konto är blockerat.',
            default => null,
        };
    }

    // ---- Hjälpmetoder för session-nycklar ----
    private function getFailedAttemptsKey(string $email): string
    {
        return "failed_attempts_$email";
    }

    private function getBlockedUntilKey(string $email): string
    {
        return "blocked_until_$email";
    }

    private function getFailedResetAttemptsKey(string $email): string
    {
        return "failed_reset_attempts_$email";
    }

    private function getBlockedResetUntilKey(string $email): string
    {
        return "blocked_reset_until_$email";
    }

    private function getFailedIpAttemptsKey(string $ip): string
    {
        return "failed_ip_attempts_$ip";
    }

    private function getBlockedIpUntilKey(string $ip): string
    {
        return "blocked_ip_until_$ip";
    }
}