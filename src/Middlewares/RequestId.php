<?php

declare(strict_types=1);

namespace App\Middlewares;

use Radix\Http\Request;
use Radix\Http\Response;
use Radix\Middleware\MiddlewareInterface;

final class RequestId implements MiddlewareInterface
{
    public function process(Request $request, \Radix\Http\RequestHandlerInterface $next): Response
    {
        $requestId = isset($_SERVER['HTTP_X_REQUEST_ID']) && is_string($_SERVER['HTTP_X_REQUEST_ID'])
            ? $_SERVER['HTTP_X_REQUEST_ID']
            : bin2hex(random_bytes(12));
        $_SERVER['HTTP_X_REQUEST_ID'] = $requestId;

        $start = microtime(true);

        $response = $next->handle($request);

        $durationMs = (int) round((microtime(true) - $start) * 1000);
        $response->setHeader('X-Request-Id', $requestId);
        $response->setHeader('X-Response-Time', $durationMs . 'ms');

        $methodServer = $_SERVER['REQUEST_METHOD'] ?? null;
        $methodReq    = $request->method ?? null;
        $method = is_string($methodServer)
            ? $methodServer
            : (is_string($methodReq) ? $methodReq : 'GET');

        $uriServer = $_SERVER['REQUEST_URI'] ?? null;
        $uriReq    = $request->uri ?? null;
        $uri = is_string($uriServer)
            ? $uriServer
            : (is_string($uriReq) ? $uriReq : '/');

        $status = $response->getStatusCode();

        error_log(sprintf(
            '[%s] %s %s %d %dms',
            $requestId,
            $method,
            $uri,
            $status,
            $durationMs
        ));

        return $response;
    }
}