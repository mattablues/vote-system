<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use Radix\Controller\AbstractController;
use Radix\Http\Response;

final class HealthWebController extends AbstractController
{
    public function __construct(private readonly \App\Services\HealthCheckService $health) {}

    public function index(): Response
    {
        $checks = $this->health->run();
        $ok = (bool) ($checks['_ok'] ?? false);

        return $this->view('admin.health.index', [
            'checks' => $checks,
            'ok' => $ok,
        ]);
    }
}
