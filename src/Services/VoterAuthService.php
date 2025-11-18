<?php

declare(strict_types=1);

namespace App\Services;

use Radix\Session\SessionInterface;

readonly class VoterAuthService
{
    public function __construct(private SessionInterface $session)
    {
    }

    public function isBlocked(string $email): bool
    {
        $failedKey = $this->failedKey($email);
        $blockedKey = $this->blockedKey($email);

        $failedAttempts = $this->session->get($failedKey, 0);
        $blockedUntil = $this->session->get($blockedKey);

        if ($blockedUntil && time() < $blockedUntil) {
            return true;
        }

        if ($failedAttempts >= 5) {
            $this->session->set($blockedKey, time() + 5 * 60);
            $this->clearFailedAttempts($email, false);
            return true;
        }

        return false;
    }

    public function trackFailedAttempt(string $email): void
    {
        $failedKey = $this->failedKey($email);

        $val = $this->session->get($failedKey, 0);
        $current = is_numeric($val) ? (int) $val : 0;

        $this->session->set($failedKey, $current + 1);
    }

    public function clearFailedAttempts(string $email, bool $removeBlocked = true): void
    {
        $this->session->remove($this->failedKey($email));
        if ($removeBlocked) {
            $this->session->remove($this->blockedKey($email));
        }
    }

    public function getBlockedUntil(string $email): ?int
    {
        $value = $this->session->get($this->blockedKey($email));

        // Returnera endast int eller null
        return is_numeric($value) ? (int) $value : null;
    }

    private function failedKey(string $email): string
    {
        return "v_failed_attempts_$email";
    }

    private function blockedKey(string $email): string
    {
        return "v_blocked_until_$email";
    }
}