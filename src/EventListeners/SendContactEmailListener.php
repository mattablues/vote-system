<?php

declare(strict_types=1);

namespace App\EventListeners;

use App\Events\ContactFormEvent;
use Radix\Mailer\MailManager;
use RuntimeException;

readonly class SendContactEmailListener
{
    public function __construct(private MailManager $mailManager) {}

    public function __invoke(ContactFormEvent $event): void
    {
        $to = getenv('MAIL_EMAIL');

        if ($to === false || $to === '') {
            throw new RuntimeException('MAIL_EMAIL env-variabeln är inte satt.');
        }

        $this->mailManager->send(
            $to, // Mottagarens e-postadress
            'Förfrågan', // E-postämne
            '', // Tom body eftersom template används
            [
                'template' => 'emails.contact', // Skicka template att rendera
                'data' => [
                    'heading' => 'Message from contact form',
                    'body'  => $event->message,
                    'name' => $event->firstName . ' ' . $event->lastName,
                    'email' => $event->email,
                ], // Template-data
                'from' => getenv('MAIL_EMAIL'), // Statisk avsändaradress (din server kräver detta)
                'from_name' => 'Support Team', // Text för avsändare
                'reply_to' => $event->email, // Registrerarens e-postadress (gör det möjligt att svara till avsändaren)
            ]
        );
    }
}
