<?php

declare(strict_types=1);

namespace App\EventListeners;

use App\Events\UserPasswordEvent;
use Radix\Mailer\MailManager;

readonly class SendPasswordResetEmailListener
{
    public function __construct(private MailManager $mailManager) {}

    public function __invoke(UserPasswordEvent $event): void
    {
        $this->mailManager->send(
            $event->email,
            'Återställ lösenord',
            '',
            [
                'template' => 'emails.password-reset',
                'data' => [
                    'title' => 'Återställ lösenord',
                    'body' => 'Här kommer din återställningslänk.',
                    'firstName' => $event->firstName,
                    'lastName' => $event->lastName,
                    'url' => $event->resetLink,
                ],
                'reply_to' => $event->email,
            ]
        );
    }
}
