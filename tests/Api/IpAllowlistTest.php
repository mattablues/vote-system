<?php

declare(strict_types=1);

namespace Radix\Tests\Api;

use App\Middlewares\IpAllowlist;
use PHPUnit\Framework\TestCase;
use Radix\Http\Request;
use Radix\Http\Response;
use Radix\Http\RequestHandlerInterface;

final class IpAllowlistTest extends TestCase
{
    /**
     * @param array<string, mixed> $server
     */
    private function runMiddleware(array $server, ?string $allowlist = null, ?string $appEnv = null): Response
    {
        // Sätt miljövariabler för testfall
        if ($allowlist !== null) {
            putenv("HEALTH_IP_ALLOWLIST={$allowlist}");
        } else {
            putenv("HEALTH_IP_ALLOWLIST");
        }

        if ($appEnv !== null) {
            putenv("APP_ENV={$appEnv}");
        } else {
            putenv("APP_ENV");
        }

        // Städa upp globala server-variabler och sätt våra
        $_SERVER = array_merge($_SERVER, $server);

        $middleware = new IpAllowlist();

        // Minimal handler som representerar nästa steg i kedjan
        $handler = new class implements RequestHandlerInterface {
            public function handle(Request $request): Response
            {
                $res = new Response();
                $res->setStatusCode(200);
                $res->setHeader('Content-Type', 'text/plain; charset=utf-8');
                $res->setBody('OK');
                return $res;
            }
        };

        /** @var array<string, mixed> $serverArray */
        $serverArray = $_SERVER;

        $request = new Request(
            uri: '/api/v1/health',
            method: 'GET',
            get: [],
            post: [],
            files: [],
            cookie: [],
            server: $serverArray
        );

        return $middleware->process($request, $handler);
    }

    public function testAllowsExactIpInProduction(): void
    {
        $response = $this->runMiddleware(
            server: [
                'REMOTE_ADDR' => '203.0.113.5',
            ],
            allowlist: '203.0.113.5, 198.51.100.0/24',
            appEnv: 'production'
        );

        $this->assertSame(200, $response->getStatusCode(), 'Exakt IP i allowlist ska tillåtas i production.');
    }

    public function testBlocksUnknownIpInProduction(): void
    {
        $response = $this->runMiddleware(
            server: [
                'REMOTE_ADDR' => '203.0.113.123',
            ],
            allowlist: '198.51.100.0/24',
            appEnv: 'production'
        );

        $this->assertSame(403, $response->getStatusCode(), 'IP utanför allowlist ska blockeras i production.');
        $this->assertSame('Forbidden', $response->getBody());
    }

    public function testAllowsCidrIpv4InProduction(): void
    {
        $response = $this->runMiddleware(
            server: [
                'REMOTE_ADDR' => '198.51.100.42',
            ],
            allowlist: '198.51.100.0/24',
            appEnv: 'production'
        );

        $this->assertSame(200, $response->getStatusCode(), 'IP inom IPv4-CIDR ska tillåtas i production.');
    }

    public function testBypassesInDevelopment(): void
    {
        $response = $this->runMiddleware(
            server: [
                'REMOTE_ADDR' => '203.0.113.200',
            ],
            allowlist: '', // spelar ingen roll i dev
            appEnv: 'development'
        );

        $this->assertSame(200, $response->getStatusCode(), 'I development ska middleware släppa igenom alla IP.');
    }

    public function testTrustedProxyUsesXForwardedFor(): void
    {
        $response = $this->runMiddleware(
            server: [
                'REMOTE_ADDR' => '127.0.0.1', // betrodd proxy
                'HTTP_X_FORWARDED_FOR' => '203.0.113.9, 10.0.0.1',
            ],
            allowlist: '203.0.113.0/24',
            appEnv: 'production'
        );

        // Ange betrodd proxy för testet
        putenv('TRUSTED_PROXY=127.0.0.1');

        $this->assertSame(200, $response->getStatusCode(), 'När proxy är betrodd ska första X-Forwarded-For-IP matchas mot allowlist.');
    }
}