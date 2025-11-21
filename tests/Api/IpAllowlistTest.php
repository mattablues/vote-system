<?php

declare(strict_types=1);

namespace Radix\Tests\Api;

use App\Middlewares\IpAllowlist;
use PHPUnit\Framework\TestCase;
use Radix\Http\Request;
use Radix\Http\RequestHandlerInterface;
use Radix\Http\Response;

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

        // Verifiera Content-Type headern för att döda MethodCallRemoval
        $headers = $response->getHeaders();
        $this->assertArrayHasKey('Content-Type', $headers);
        $this->assertSame('text/plain; charset=utf-8', $headers['Content-Type']);
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

    public function testAllowsCidrIpv6InProduction(): void
    {
        $response = $this->runMiddleware(
            server: [
                'REMOTE_ADDR' => '2001:db8::1',
            ],
            allowlist: '2001:db8::/32',
            appEnv: 'production'
        );

        $this->assertSame(200, $response->getStatusCode(), 'IP inom IPv6-CIDR ska tillåtas i production.');
    }

    public function testBlocksCidrIpv6OutsideRangeInProduction(): void
    {
        $response = $this->runMiddleware(
            server: [
                'REMOTE_ADDR' => '2001:db9::1', // db9 istället för db8
            ],
            allowlist: '2001:db8::/32',
            appEnv: 'production'
        );

        $this->assertSame(403, $response->getStatusCode(), 'IP utanför IPv6-CIDR ska blockeras i production.');
    }

    public function testAllowsComplexIpv6CidrInProduction(): void
    {
        // Använd en adress med både nollor och FFFF för att maximera chansen att padding-fel slår igenom
        // ffff = 11111111 11111111 (8+8 bitar, opåverkade av pad 7)
        // 0000 = 00000000 00000000 (7+7 bitar med pad 7)

        // Allow: ffff:0000::/17 (16 bitar ffff + 1 bit av nollan)
        // 17 bitar.
        // Original: ffff (16 bitar) + 0 (1 bit).
        // Mutant: ffff (16 bitar) + 0 (1 bit). -> Borde vara lika.

        // Låt oss prova mask som täcker "slutet" av en "kort" byte.
        // ffff:0001:: / 32.
        // 0001 = 00000000 00000001.
        // Mutant pad 7 på 00: "0000000". (Mappar 8 bitar till 7).
        // 01: "0000001".

        $response = $this->runMiddleware(
            server: [
                'REMOTE_ADDR' => 'ffff:0001::1',
            ],
            allowlist: 'ffff:0001::/32',
            appEnv: 'production'
        );

        $this->assertSame(200, $response->getStatusCode(), 'Complex IPv6 CIDR match failed');
    }

    public function testAllowsIpv6Cidr128InProduction(): void
    {
        $response = $this->runMiddleware(
            server: [
                'REMOTE_ADDR' => '2001:db8::1',
            ],
            allowlist: '2001:db8::1/128', // Specifik /128 mask
            appEnv: 'production'
        );

        $this->assertSame(200, $response->getStatusCode(), 'IPv6 med /128 mask ska tillåtas i production.');
    }

    public function testAllowsIpv6Cidr0InProduction(): void
    {
        $response = $this->runMiddleware(
            server: [
                'REMOTE_ADDR' => '2001:db8::1',
            ],
            allowlist: '::/0', // Hela IPv6-internet
            appEnv: 'production'
        );

        $this->assertSame(200, $response->getStatusCode(), 'IPv6 med /0 mask ska tillåtas i production.');
    }

    public function testBlocksIpv4AgainstIpv6Allowlist(): void
    {
        $response = $this->runMiddleware(
            server: [
                'REMOTE_ADDR' => '192.168.1.1',
            ],
            allowlist: '::/0', // Endast IPv6 tillåtet
            appEnv: 'production'
        );

        $this->assertSame(403, $response->getStatusCode(), 'IPv4 ska inte matchas mot IPv6-regler.');
    }

    public function testBlocksIpv4With32MaskDifference(): void
    {
        // Test för att döda DecrementInteger på IPv4 mask
        // 192.168.1.1 = ...00000001
        // 192.168.1.0 = ...00000000
        // /32 mask ska skilja på dessa.
        // Om masken korrumperas till att ignorera sista biten (som /31), så matchar de.

        $response = $this->runMiddleware(
            server: [
                'REMOTE_ADDR' => '192.168.1.0',
            ],
            allowlist: '192.168.1.1/32',
            appEnv: 'production'
        );

        $this->assertSame(403, $response->getStatusCode(), 'IP som skiljer sig på sista biten ska blockeras vid /32 mask.');
    }

    public function testAllowsIpv4Cidr32InProduction(): void
    {
        // Test för att döda GreaterThan på IPv4 mask (/32)
        $response = $this->runMiddleware(
            server: [
                'REMOTE_ADDR' => '192.168.1.42',
            ],
            allowlist: '192.168.1.42/32',
            appEnv: 'production'
        );

        $this->assertSame(200, $response->getStatusCode(), 'IP med /32 mask ska tillåtas i production.');
    }

    public function testAllowsIpv4Cidr0InProduction(): void
    {
        // Test för att döda LessThan på IPv4 mask (/0)
        $response = $this->runMiddleware(
            server: [
                'REMOTE_ADDR' => '192.168.1.42',
            ],
            allowlist: '0.0.0.0/0', // Hela IPv4-internet
            appEnv: 'production'
        );

        $this->assertSame(200, $response->getStatusCode(), 'IP med /0 mask ska tillåtas i production.');
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
        // Ange betrodd proxy FÖRE middleware körs
        putenv('TRUSTED_PROXY=10.0.0.1');

        $response = $this->runMiddleware(
            server: [
                'REMOTE_ADDR' => '10.0.0.1', // betrodd proxy
                // Lägg till mellanslag i början av X-Forwarded-For för att döda UnwrapArrayMap mutant
                'HTTP_X_FORWARDED_FOR' => ' 203.0.113.9, 192.168.1.1',
            ],
            allowlist: '203.0.113.0/24',
            appEnv: 'production'
        );

        $this->assertSame(200, $response->getStatusCode(), 'När proxy är betrodd ska första X-Forwarded-For-IP matchas mot allowlist.');

        // Städa upp
        putenv('TRUSTED_PROXY');
    }

    public function testTrustedProxyWithWhitespaceInXForwardedFor(): void
    {
        // Ange betrodd proxy
        putenv('TRUSTED_PROXY=10.0.0.1');

        $response = $this->runMiddleware(
            server: [
                'REMOTE_ADDR' => '10.0.0.1',
                // Mellanslag i början av IP
                'HTTP_X_FORWARDED_FOR' => ' 203.0.113.5',
            ],
            allowlist: '203.0.113.5',
            appEnv: 'production'
        );

        $this->assertSame(200, $response->getStatusCode(), 'X-Forwarded-For med mellanslag ska trimmas och tillåtas.');

        putenv('TRUSTED_PROXY');
    }

    public function testUntrustedProxyCannotSpoofIp(): void
    {
        // Konfigurera en betrodd proxy
        putenv('TRUSTED_PROXY=10.0.0.1');

        $response = $this->runMiddleware(
            server: [
                'REMOTE_ADDR' => '10.0.0.666', // En annan IP
                'HTTP_X_FORWARDED_FOR' => '203.0.113.5', // En tillåten IP
            ],
            allowlist: '203.0.113.5',
            appEnv: 'production'
        );

        $this->assertSame(403, $response->getStatusCode(), 'Endast den betrodda proxyn får ange X-Forwarded-For.');

        putenv('TRUSTED_PROXY');
    }

    public function testAllowsIpWithWhitespaceInList(): void
    {
        $response = $this->runMiddleware(
            server: [
                'REMOTE_ADDR' => '198.51.100.42',
            ],
            allowlist: '203.0.113.5, 198.51.100.0/24', // Notera mellanslag efter komma
            appEnv: 'production'
        );

        $this->assertSame(200, $response->getStatusCode(), 'Allowlist med mellanslag ska fungera (ska trimmas).');
    }
}
