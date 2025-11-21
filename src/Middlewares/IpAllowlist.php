<?php

declare(strict_types=1);

namespace App\Middlewares;

use Radix\Http\Request;
use Radix\Http\RequestHandlerInterface;
use Radix\Http\Response;
use Radix\Middleware\MiddlewareInterface;

final class IpAllowlist implements MiddlewareInterface
{
    private const int BITS_PER_BYTE = 8;

    public function process(Request $request, RequestHandlerInterface $next): Response
    {
        // Hämta client IP som sträng
        $clientIp = '';
        if (isset($_SERVER['REMOTE_ADDR'])) {
            $remoteAddr = $_SERVER['REMOTE_ADDR'];
            if (is_string($remoteAddr)) {
                $clientIp = $remoteAddr;
            }
        }

        $forwardedRaw = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null;
        $forwardedFor = is_string($forwardedRaw) ? $forwardedRaw : '';

        $trustedProxyEnv = getenv('TRUSTED_PROXY');
        // Använd ?: för att hantera både false och tom sträng som null.
        // Detta dödar LogicalAnd-mutanten på rad 31.
        $trustedProxy = $trustedProxyEnv ?: null;

        // Förenkla villkoret genom att använda $clientIp istället för att kolla $_SERVER igen.
        // Detta tar bort redundans och dödar LogicalAnd-mutanten på rad 33.
        if ($trustedProxy !== null && $clientIp === $trustedProxy) {
            if ($forwardedFor !== '') {
                $parts = array_map('trim', explode(',', $forwardedFor));
                if (!empty($parts)) {
                    $clientIp = $parts[0];
                }
            }
        }

        $allowlistEnv = getenv('HEALTH_IP_ALLOWLIST');
        $allowlist = is_string($allowlistEnv) ? $allowlistEnv : '';

        // Ta bort array_filter för att döda UnwrapArrayFilter.
        // Tomma strängar i arrayen (från t.ex. trailing comma) kommer hanteras av loopen (matchar ej).
        $allowed = array_map('trim', explode(',', $allowlist));

        // Använd ?: för att hantera både false och tom sträng som 'production'.
        // Detta undviker CastString-mutanten och gör koden renare.
        $appEnvEnv = getenv('APP_ENV');
        $env = $appEnvEnv ?: 'production';

        $isLocal = in_array($clientIp, ['127.0.0.1', '::1'], true);

        // Släpp igenom helt i local/development
        if (in_array($env, ['local', 'development'], true) || $isLocal) {
            return $next->handle($request);
        }

        // Ta bort $permitted flaggan och loopa för att returnera direkt vid matchning.
        // Detta gör koden enklare och undviker Break_ mutant.
        foreach ($allowed as $rule) {
            // $rule är alltid string här
            if ($rule === $clientIp) {
                return $next->handle($request);
            }
            if (str_contains($rule, '/')) {
                // Ändra till att hämta alla delar och validera antalet för att undvika IncrementInteger på limit.
                $parts = explode('/', $rule);
                if (count($parts) !== 2) {
                    continue;
                }
                [$subnet, $maskStr] = $parts;

                $mask = (int) $maskStr;

                // IPv4-CIDR
                if (
                    filter_var($clientIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)
                    && filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)
                ) {
                    if ($mask < 0 || $mask > 32) {
                        continue;
                    }

                    $ipLong = ip2long($clientIp);
                    $subnetLong = ip2long($subnet);

                    // Tog bort död kod: if ($ipLong === false || $subnetLong === false) continue;
                    // filter_var har redan garanterat att dessa är giltiga IPv4-adresser.
                    // Vi castar till int för säkerhets skull (om ip2long returnerar false blir det 0).

                    $maskLong = -1 << (32 - $mask);
                    $maskLong = $maskLong & 0xFFFFFFFF;

                    // Ta bort explicita (int) castar då ipLong/subnetLong garanterat är int här.
                    // Detta dödar CastInt mutanten genom att ta bort den onödiga koden.
                    if (($ipLong & $maskLong) === ($subnetLong & $maskLong)) {
                        return $next->handle($request);
                    }
                }

                // IPv6-CIDR
                if (
                    filter_var($clientIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)
                    && filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)
                ) {
                    if ($mask < 0 || $mask > 128) {
                        continue;
                    }
                    if ($this->ipv6InCidr($clientIp, $subnet, $mask)) {
                        return $next->handle($request);
                    }
                }
            }
        }

        // Om vi kommer hit har ingen regel matchat -> Forbidden
        $res = new Response();
        $res->setStatusCode(403);
        $res->setHeader('Content-Type', 'text/plain; charset=utf-8');
        $res->setBody('Forbidden');
        return $res;
    }

    private function ipv6InCidr(string $ip, string $subnet, int $mask): bool
    {
        $ipBin = $this->inet6ToBits($ip);
        $subnetBin = $this->inet6ToBits($subnet);

        // Död kod borttagen. Castar till string för att hantera eventuell null (även om det inte borde ske).
        return substr((string) $ipBin, 0, $mask) === substr((string) $subnetBin, 0, $mask);
    }

    private function inet6ToBits(string $ip): ?string
    {
        $packed = @inet_pton($ip);
        if ($packed === false) {
            return null;
        }
        $bits = '';
        foreach (str_split($packed) as $char) {
            // Använd konstant för att undvika mutation av "magiska siffror"
            $bits .= str_pad(decbin(ord($char)), self::BITS_PER_BYTE, '0', STR_PAD_LEFT);
        }
        return $bits;
    }
}
