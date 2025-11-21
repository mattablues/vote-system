<?php

declare(strict_types=1);

namespace App\Events;

use Radix\Enums\UserActivationContext;
use Radix\EventDispatcher\Event;

final class UserRegisteredEvent extends Event
{
    public function __construct(
        public string $email,
        public string $firstName,
        public string $lastName,
        public string $activationLink,
        public ?string $password = null,
        public UserActivationContext $context = UserActivationContext::User
    ) {}
}
