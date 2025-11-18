<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Voter;

class VoterSessionService
{
    private const string SESS_AUTH  = 'voter_authenticated';
    private const string SESS_ID    = 'voter_id';
    private const string SESS_EMAIL = 'voter_email';
    private const string SESS_LAST  = 'voter_last_login';
    private const string SESS_IP    = 'voter_ip';
    private const string SESS_UA    = 'voter_user_agent';

    private int $timeoutSeconds = 24 * 60 * 60; // 24h (aktiv flöde under pågående session)

    public function __construct(
        private readonly \Radix\Http\Request $request
    ) {}

    public function login(Voter $voter): void
    {
        $session = $this->request->session();
        $session->set(self::SESS_AUTH, true);
        $session->set(self::SESS_ID, (int)$voter->id);
        $session->set(self::SESS_EMAIL, (string)$voter->email);
        $session->set(self::SESS_LAST, time());
        $session->set(self::SESS_IP, $_SERVER['REMOTE_ADDR'] ?? '');
        $session->set(self::SESS_UA, $_SERVER['HTTP_USER_AGENT'] ?? '');
    }

    public function logout(): void
    {
        $s = $this->request->session();
        $s->remove(self::SESS_AUTH);
        $s->remove(self::SESS_ID);
        $s->remove(self::SESS_EMAIL);
        $s->remove(self::SESS_LAST);
        $s->remove(self::SESS_IP);
        $s->remove(self::SESS_UA);
    }

    public function valid(): bool
    {
        $s = $this->request->session();
        if (!$s->has(self::SESS_AUTH) || !$s->get(self::SESS_AUTH)) {
            return false;
        }

        $lastVal = $s->get(self::SESS_LAST, 0);
        $last = is_numeric($lastVal) ? (int) $lastVal : 0;

        if ($last + $this->timeoutSeconds < time()) {
            return false;
        }

        $ipVal = $s->get(self::SESS_IP, '');
        $ip = is_string($ipVal) ? $ipVal : '';

        $uaVal = $s->get(self::SESS_UA, '');
        $ua = is_string($uaVal) ? $uaVal : '';

        if ($ip !== ($_SERVER['REMOTE_ADDR'] ?? '') || $ua !== ($_SERVER['HTTP_USER_AGENT'] ?? '')) {
            return false;
        }

        $s->set(self::SESS_LAST, time()); // förläng under aktivitet
        return true;
    }

    public function current(): ?Voter
    {
        if (!$this->valid()) {
            return null;
        }

        $value = $this->request->session()->get(self::SESS_ID, 0);
        $id = is_numeric($value) ? (int) $value : 0;

        if ($id <= 0) {
            return null;
        }

        return Voter::find($id);
    }

    public function isAuthenticated(): bool
    {
        return $this->valid();
    }
}