<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use Radix\Controller\ApiController;
use Radix\Http\JsonResponse;

class HealthController extends ApiController
{
    public function __construct(private readonly \App\Services\HealthCheckService $health) {}

    public function index(): JsonResponse
    {
        $env = getenv('APP_ENV') ?: 'production';

        $requireTokenEnv = getenv('HEALTH_REQUIRE_TOKEN');
        $requireToken = match ($requireTokenEnv) {
            '1', 'true', 'on' => true,
            '0', 'false', 'off' => false,
            default => !in_array($env, ['local', 'development'], true),
        };

        if ($requireToken) {
            $this->validateRequest();
        }

        $start = microtime(true);

        $checks = $this->health->run();
        /** @var bool $ok */
        $ok = ($checks['_ok'] ?? false);
        unset($checks['_ok']);

        if ($env === 'production') {
            $checks = [
                'db' => isset($checks['db']) ? ($checks['db'] === 'ok' ? 'ok' : 'fail') : 'unknown',
                'fs' => isset($checks['fs']) ? ($checks['fs'] === 'ok' ? 'ok' : 'fail') : 'unknown',
            ];
        }

        $res = new JsonResponse();
        $res->setStatusCode($ok ? 200 : 500);
        $res->setHeader('Content-Type', 'application/json; charset=utf-8');
        $res->setHeader('Cache-Control', 'no-store, must-revalidate, max-age=0');
        $res->setHeader('Pragma', 'no-cache');
        $res->setHeader('Expires', '0');

        // Låt CorsListener styra CORS (dev på, prod av via CORS_ENABLED)
        $body = json_encode(
            ['ok' => $ok, 'checks' => $checks],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        );

        // Låt CorsListener styra CORS (dev på, prod av via CORS_ENABLED)
        $res->setBody($body);

        return $res;
    }
}
