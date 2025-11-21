<?php

declare(strict_types=1);

namespace App\EventListeners;

use App\Enums\VoterContext;
use App\Events\VoterRegisteredEvent;
use Radix\Mailer\MailManager;

readonly class SendVoterActivationEmailListener
{
    public function __construct(private MailManager $mailManager) {}

    public function __invoke(VoterRegisteredEvent $event): void
    {
        $isActivate = $event->context === VoterContext::Activate;

        $subject     = $isActivate ? 'Aktivering' : 'Avaktivering';
        $title       = $isActivate ? 'Välkommen' : 'Avregistrering av konto';
        $introText   = $isActivate
            ? 'Du måste aktivera dig för att kunna rösta, klicka på följande aktiveringslänk'
            : 'Klicka på länken nedan för att avregistrera ditt konto';
        $buttonLabel = $isActivate ? 'Aktivera konto' : 'Avregistrera konto';
        $body        = $isActivate
            ? 'Om du inte förväntade dig detta meddelande kan du ignorera det.'
            : 'Du kan när som helst registrera ett nytt konto om du avregistrerar dig.';

        // Defensiv fallback om fel fält råkar skickas
        $url = $isActivate
            ? ($event->activationLink ?? '')
            : ($event->deactivationLink ?: ($event->activationLink ?? ''));

        $this->mailManager->send(
            $event->email,
            $subject,
            '',
            [
                'template' => 'emails.voter-activate',
                'data' => [
                    'title' => $title,
                    'introText' => $introText,
                    'body' => $body,
                    'url' => $url,
                    'buttonLabel' => $buttonLabel,
                ],
                'reply_to' => getenv('MAIL_EMAIL'),
            ]
        );
    }
}
