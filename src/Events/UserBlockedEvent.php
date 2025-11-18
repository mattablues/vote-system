<?php

declare(strict_types=1);

namespace App\Events;

use Radix\EventDispatcher\Event;

class UserBlockedEvent extends Event
{
    public function __construct(
        private readonly int $userId,
    ) {
    }

    public function getUserId(): int
    {
        return $this->userId;
    }
}
