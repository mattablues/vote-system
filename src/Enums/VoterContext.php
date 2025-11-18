<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Class VoterContext
 * @package Radix\Enums
 */
enum VoterContext: string
{
    case Activate = 'activate';
    case Deactivate = 'deactivate';
}