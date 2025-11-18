<?php

declare(strict_types=1);

namespace App\Middlewares;

use Radix\Http\Request;
use Radix\Http\RequestHandlerInterface;
use Radix\Http\Response;
use Radix\Middleware\MiddlewareInterface;

final class IpAllowlist implements MiddlewareInterface
{
    public function process(Request $request, RequestHandlerInterface $next): Response
    {
        // Hämta client IP som sträng
        $clientIp = '';
        if (isset($_SERVER['REMOTE_ADDR']) && is_string($_SERVER['REMOTE_ADDR'])) {
            $clientIp = $_SERVER['REMOTE_ADDR'];
        }

        $forwardedRaw = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null;
        $forwardedFor = is_string($forwardedRaw) ? $forwardedRaw : '';

        $trustedProxyEnv = getenv('TRUSTED_PROXY');
        $trustedProxy = is_string($trustedProxyEnv) && $trustedProxyEnv !== '' ? $trustedProxyEnv : null;

        if ($trustedProxy !== null && isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] === $trustedProxy) {
            if ($forwardedFor !== '') {
                $parts = array_map('trim', explode(',', $forwardedFor));
                if (!empty($parts)) {
                    $clientIp = $parts[0];
                }
            }
        }

        $allowlistEnv = getenv('HEALTH_IP_ALLOWLIST');
        $allowlist = is_string($allowlistEnv) ? $allowlistEnv : '';
        $allowed = array_filter(array_map('trim', explode(',', $allowlist)));

        $appEnvEnv = getenv('APP_ENV');
        $env = is_string($appEnvEnv) && $appEnvEnv !== '' ? $appEnvEnv : 'production';

        $isLocal = in_array($clientIp, ['127.0.0.1', '::1'], true);

        // Släpp igenom helt i local/development
        if (in_array($env, ['local', 'development'], true) || $isLocal) {
            return $next->handle($request);
        }

        $permitted = false;
        foreach ($allowed as $rule) {
            // $rule är alltid string här
            if ($rule === $clientIp) {
                $permitted = true;
                break;
            }
            if (str_contains($rule, '/')) {
                [$subnet, $maskStr] = explode('/', $rule, 2);
                $mask = (int) $maskStr;

                // IPv4-CIDR
                if (
                    filter_var($clientIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) &&
                    filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)
                ) {
                    if ($mask < 0 || $mask > 32) {
                        continue;
                    }

                    $ipLong = ip2long($clientIp);
                    $subnetLong = ip2long($subnet);
                    if ($ipLong === false || $subnetLong === false) {
                        continue;
                    }

                    $maskLong = -1 << (32 - $mask);
                    $maskLong = $maskLong & 0xFFFFFFFF;
                    if (($ipLong & $maskLong) === ($subnetLong & $maskLong)) {
                        $permitted = true;
                        break;
                    }
                }

                // IPv6-CIDR
                if (
                    filter_var($clientIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) &&
                    filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)
                ) {
                    if ($mask < 0 || $mask > 128) {
                        continue;
                    }
                    if ($this->ipv6InCidr($clientIp, $subnet, $mask)) {
                        $permitted = true;
                        break;
                    }
                }
            }
        }

        if (!$permitted) {
            $res = new Response();
            $res->setStatusCode(403);
            $res->setHeader('Content-Type', 'text/plain; charset=utf-8');
            $res->setBody('Forbidden');
            return $res;
        }

        return $next->handle($request);
    }

    private function ipv6InCidr(string $ip, string $subnet, int $mask): bool
    {
        $ipBin = $this->inet6ToBits($ip);
        $subnetBin = $this->inet6ToBits($subnet);

        if ($ipBin === null || $subnetBin === null) {
            return false;
        }

        return substr($ipBin, 0, $mask) === substr($subnetBin, 0, $mask);
    }

    private function inet6ToBits(string $ip): ?string
    {
        $packed = @inet_pton($ip);
        if ($packed === false) {
            return null;
        }
        $bits = '';
        foreach (str_split($packed) as $char) {
            $bits .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }
        return $bits;
    }
}