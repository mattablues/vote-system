<?php

declare(strict_types=1);

namespace App\Events;

use Radix\EventDispatcher\Event;

class UserPasswordEvent extends Event
{
    public function __construct(
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly string $email,
        public readonly string $resetLink
    ) {}
}