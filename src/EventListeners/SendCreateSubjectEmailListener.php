<?php

declare(strict_types=1);

namespace App\EventListeners;

use App\Events\CreateSubjectEvent;
use Radix\Mailer\MailManager;

readonly class SendCreateSubjectEmailListener
{
    public function __construct(private MailManager $mailManager) {}

    public function __invoke(CreateSubjectEvent $event): void
    {
        $to = getenv('MAIL_EMAIL');

        if ($to === false || $to === '') {
            throw new \RuntimeException('MAIL_EMAIL env-variabeln är inte satt.');
        }

        $this->mailManager->send(
            $to,
            'Ämne skapat',
            '',
            [
                'template' => 'emails.create-subject',
                'data' => [
                    'title' => 'Nytt ämne',
                    'body' => "Ett nytt ämne har skapats av $event->email.",
                    'category' => $event->category,
                    'subject' => $event->subject,
                ],
                'reply_to' => $event->email,
            ]
        );
    }
}