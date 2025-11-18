<?php

declare(strict_types=1);

namespace Radix\Tests\Api;

use PHPUnit\Framework\TestCase;
use Radix\Container\ApplicationContainer;
use Radix\Container\Container;
use Radix\Database\Connection;
use Radix\EventDispatcher\EventDispatcher;
use Radix\Http\Request;
use Radix\Http\Response;
use Radix\Routing\Dispatcher;
use Radix\Routing\Router;
use Radix\Viewer\TemplateViewerInterface;
use Radix\Viewer\RadixTemplateViewer;

final class HealthControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        ApplicationContainer::reset();

        $projectRoot = dirname(__DIR__, 2);
        if (!defined('ROOT_PATH')) {
            define('ROOT_PATH', $projectRoot);
        }
        putenv('CACHE_PATH=' . $projectRoot . '/cache/views');


        $container = new Container();

        // Nödvändiga beroenden
        $container->add(Response::class, fn() => new Response());
        $container->add(EventDispatcher::class, fn() => new EventDispatcher());

        // Registrera TemplateViewer
        $container->add(TemplateViewerInterface::class, fn() => new RadixTemplateViewer());

        // Stubba PDOStatement minimalt
        $pdoStmt = $this->createMock(\PDOStatement::class);

        // Mocka Connection så execute() returnerar PDOStatement
        $dbConn = $this->createMock(Connection::class);
        $dbConn->method('execute')->willReturn($pdoStmt);

        // Minimal DatabaseManager-stub
        $dbManager = new class($dbConn) {
            public function __construct(private Connection $conn) {}
            public function connection(): Connection { return $this->conn; }
        };
        $container->add(\Radix\Database\DatabaseManager::class, fn() => $dbManager);

        ApplicationContainer::set($container);
    }

    protected function tearDown(): void
    {
        // Ta bort cache/health-katalogen
        $healthDir = rtrim((string) (defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__, 2)), '/\\') . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'health';
        if (is_dir($healthDir)) {
            foreach (scandir($healthDir) as $file) {
                if ($file === '.' || $file === '..') {
                    continue;
                }
                $path = $healthDir . DIRECTORY_SEPARATOR . $file;
                if (is_file($path)) {
                    @unlink($path);
                }
            }
            @rmdir($healthDir);
        }

        parent::tearDown();
    }

    public function testHealthReturnsOkJsonAndHeaders(): void
    {
        $router = new Router();
        $router->group(['path' => '/api/v1', 'middleware' => ['request.id']], function (Router $r) {
            $r->get('/health', [\App\Controllers\Api\HealthController::class, 'index'])->name('api.health.index');
            $r->get('/{any:.*}', function () {
                $resp = new Response();

                $method = $_SERVER['REQUEST_METHOD'] ?? '';
                if (!is_string($method)) {
                    $method = '';
                }

                if (strtoupper($method) === 'OPTIONS') {
                    $resp->setStatusCode(204);
                    return $resp;
                }
                $resp->setStatusCode(404);
                return $resp;
            })->name('api.preflight');
        });

        $middleware = [
            'request.id' => \App\Middlewares\RequestId::class,
        ];

        $container = ApplicationContainer::get();
        $dispatcher = new Dispatcher($router, $container, $middleware);

        $request = new Request(
            uri: '/api/v1/health',
            method: 'GET',
            get: [],
            post: [],
            files: [],
            cookie: [],
            server: []
        );

        $response = $dispatcher->handle($request);
        $this->assertSame(200, $response->getStatusCode());

        /** @var array<string, mixed> $headers */
        $headers = $response->getHeaders();
        $this->assertArrayHasKey('Content-Type', $headers);
        $this->assertArrayHasKey('X-Request-Id', $headers);
        $this->assertArrayHasKey('X-Response-Time', $headers);

        /** @var array{
         *     ok: bool,
         *     checks: array<string, mixed>
         * } $body
         */
        $body = json_decode($response->getBody(), true);
        $this->assertIsArray($body);

        $this->assertTrue($body['ok']);
        $this->assertArrayHasKey('php', $body['checks']);
        $this->assertArrayHasKey('time', $body['checks']);
    }
}