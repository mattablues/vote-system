<?php

declare(strict_types=1);

namespace App\Events;

use App\Enums\VoterContext;
use Radix\EventDispatcher\Event;

final class VoterRegisteredEvent extends Event
{
    public function __construct(
        public string $email,
        public string $activationLink = '',
        public string $deactivationLink = '',
        // 'activate' | 'deactivate'
        public VoterContext $context = VoterContext::Activate
    ) {}
}