<?php

declare(strict_types=1);

return [
    \App\Events\UserBlockedEvent::class => [
        [
            'listener' => \App\EventListeners\LogoutListener::class,
            'type' => 'container',
            'priority' => 20,
            'stopPropagation' => true, // Stoppa vidare lyssnarutrop efter denna
        ],
    ],
    \App\Events\UserRegisteredEvent::class => [
        [
            'listener' => \App\EventListeners\SendActivationEmailListener::class,
            'type' => 'custom',
            'dependencies' => [\Radix\Mailer\MailManager::class],
            'priority' => 10,
        ],
    ],
    \App\Events\ContactFormEvent::class => [
        [
            'listener' => \App\EventListeners\SendContactEmailListener::class,
            'type' => 'custom',
            'dependencies' => [\Radix\Mailer\MailManager::class],
            'priority' => 5, // Standardprioritet
        ],
    ],
    \App\Events\UserPasswordEvent::class => [
        [
            'listener' => \App\EventListeners\SendPasswordResetEmailListener::class,
            'type' => 'custom',
            'dependencies' => [\Radix\Mailer\MailManager::class],
        ],
    ],
];