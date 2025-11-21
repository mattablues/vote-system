<?php

declare(strict_types=1);

namespace App\Events;

use Radix\EventDispatcher\Event;

class CreateSubjectEvent extends Event
{
    public function __construct(
        public readonly string $email,
        public readonly string $category,
        public readonly string $subject,
    ) {}
}
