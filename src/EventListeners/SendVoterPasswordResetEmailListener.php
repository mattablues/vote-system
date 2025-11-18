<?php

declare(strict_types=1);

namespace App\EventListeners;

use App\Events\VoterPasswordEvent;
use Radix\Mailer\MailManager;

readonly class SendVoterPasswordResetEmailListener
{
    public function __construct(private MailManager $mailManager) {}

    public function __invoke(VoterPasswordEvent $event): void
    {
        $this->mailManager->send(
            $event->email,
            'Återställ lösenord',
            '',
            [
                'template' => 'emails.voter-password-reset',
                'data' => [
                    'title' => 'Återställ lösenord',
                    'body' => 'Här kommer din återställningslänk.',
                    'email' => $event->email,
                    'url' => $event->resetLink,
                ],
                'reply_to' => $event->email,
            ]
        );
    }
}