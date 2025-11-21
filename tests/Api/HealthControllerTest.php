<?php

declare(strict_types=1);

namespace App\Services {
    // Vi använder en statisk variabel för att kommunicera med testet.
    class FileSystemSpy
    {
        public static ?string $lastFilePath = null;
        public static ?int $lastMkdirPermissions = null;
    }

    /**
     * @param resource|null $context
     */
    function file_put_contents(string $filename, mixed $data, int $flags = 0, mixed $context = null): int|false
    {
        FileSystemSpy::$lastFilePath = $filename;
        /** @var resource|null $context */
        return \file_put_contents($filename, $data, $flags, $context);
    }

    /**
     * @param resource|null $context
     */
    function mkdir(string $directory, int $permissions = 0o777, bool $recursive = false, mixed $context = null): bool
    {
        FileSystemSpy::$lastMkdirPermissions = $permissions;
        /** @var resource|null $context */
        return \mkdir($directory, $permissions, $recursive, $context);
    }
}

namespace Radix\Tests\Api {

    use PDOStatement;
    use PHPUnit\Framework\TestCase;
    use Radix\Container\ApplicationContainer;
    use Radix\Container\Container;
    use Radix\Database\Connection;
    use Radix\EventDispatcher\EventDispatcher;
    use Radix\Http\Request;
    use Radix\Http\Response;
    use Radix\Routing\Dispatcher;
    use Radix\Routing\Router;
    use Radix\Viewer\RadixTemplateViewer;
    use Radix\Viewer\TemplateViewerInterface;

    // Definiera Spy-klassen i samma namespace som testet
    class TestSpyLogger extends \Radix\Support\Logger
    {
        /** @var array<int, string|array{msg: string, ctx: array<string, mixed>}> */
        public array $logs = [];

        public function __construct() {}

        public function info(string $message, array $context = []): void
        {
            $this->logs[] = ['msg' => $message, 'ctx' => $context];
        }
        public function error(string $message, array $context = []): void
        {
            $this->logs[] = ['msg' => $message, 'ctx' => $context];
        }
        public function warning(string $message, array $context = []): void {}
        public function debug(string $message, array $context = []): void {}
    }

    final class HealthControllerTest extends TestCase
    {
        protected function setUp(): void
        {
            parent::setUp();
            ApplicationContainer::reset();

            putenv('HEALTH_REQUIRE_TOKEN=off');

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
            $pdoStmt = $this->createMock(PDOStatement::class);

            // Mocka Connection så execute() returnerar PDOStatement
            $dbConn = $this->createMock(Connection::class);
            $dbConn->method('execute')->willReturn($pdoStmt);

            // Minimal DatabaseManager-stub
            $dbManager = new class ($dbConn) {
                public function __construct(private Connection $conn) {}
                public function connection(): Connection
                {
                    return $this->conn;
                }
            };
            $container->add(\Radix\Database\DatabaseManager::class, fn() => $dbManager);

            ApplicationContainer::set($container);
        }

        protected function tearDown(): void
        {
            // Reset spies
            \App\Services\FileSystemSpy::$lastFilePath = null;
            \App\Services\FileSystemSpy::$lastMkdirPermissions = null;

            putenv('HEALTH_REQUIRE_TOKEN');
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
            putenv('APP_ENV=testing');

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

            // Verifiera längden på genererat Request ID (12 bytes * 2 hex chars = 24 chars)
            // Detta dödar IncrementInteger/DecrementInteger mutationer på random_bytes(12)
            $reqId = $headers['X-Request-Id'];
            $this->assertIsString($reqId);
            // Om vi inte sätter X-Request-Id i requesten ska den genereras med längd 24
            $this->assertSame(24, strlen($reqId), 'Generated Request ID should be 24 chars long (12 bytes hex)');

            $this->assertArrayHasKey('X-Response-Time', $headers);

            // Assert that X-Response-Time is a string and ends with "ms"
            $responseTime = $headers['X-Response-Time'];
            $this->assertIsString($responseTime);
            $this->assertStringEndsWith('ms', $responseTime);

            // Kontrollera formatet (siffror följt av ms) för att döda ConcatOperandRemoval
            $this->assertMatchesRegularExpression('/^\d+ms$/', $responseTime);

            // Döda Multiplication mutant: Kontrollera att värdet är rimligt (inte en timestamp)
            $msValue = (int) substr($responseTime, 0, -2);
            $this->assertLessThan(10000, $msValue, 'Response time should be reasonable (< 10s), not a timestamp');

            // Döda MethodCallRemoval för cache-headers
            $this->assertArrayHasKey('Cache-Control', $headers);
            $this->assertSame('no-store, must-revalidate, max-age=0', $headers['Cache-Control']);

            $this->assertArrayHasKey('Pragma', $headers);
            $this->assertSame('no-cache', $headers['Pragma']);

            $this->assertArrayHasKey('Expires', $headers);
            $this->assertSame('0', $headers['Expires']);

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
            $this->assertSame(JSON_ERROR_NONE, json_last_error());

        }

        public function testRequestIdMiddlewarePreservesExistingId(): void
        {
            // Sätt en miljövariabel/servervariabel för request ID
            $customId = 'custom-request-id-123';
            $_SERVER['HTTP_X_REQUEST_ID'] = $customId;

            try {
                $router = new Router();
                // VIKTIGT: Lägg till middleware på rutten!
                $router->get('/test', function () { return new Response(); })
                       ->middleware(['request.id']);

                $middleware = [
                    'request.id' => \App\Middlewares\RequestId::class,
                ];

                $container = ApplicationContainer::get();
                $dispatcher = new Dispatcher($router, $container, $middleware);

                // Vi behöver inte ens skicka med det i Request-objektet om middlewaret läser från $_SERVER direkt,
                // men för säkerhets skull (om RequestId uppdateras att läsa från Request) kan vi ha det där med.
                // Middlewaret i fråga läser dock från $_SERVER['HTTP_X_REQUEST_ID'].
                $request = new Request('/test', 'GET', [], [], [], [], ['HTTP_X_REQUEST_ID' => $customId]);

                $response = $dispatcher->handle($request);

                $headers = $response->getHeaders();
                $this->assertArrayHasKey('X-Request-Id', $headers);
                $this->assertSame($customId, $headers['X-Request-Id'], 'Middleware should preserve existing X-Request-Id');
            } finally {
                // Städa upp
                unset($_SERVER['HTTP_X_REQUEST_ID']);
            }
        }

        public function testHealthReturns500WhenChecksFail(): void
        {
            putenv('APP_ENV=testing');

            // 1. Förbered en container där vi mockar HealthCheckService att returnera fel
            $mockHealthService = $this->createMock(\App\Services\HealthCheckService::class);
            $mockHealthService->method('run')->willReturn(['_ok' => false, 'db' => 'fail']);

            $container = ApplicationContainer::get();

            // PHPStan Fix 1: Berätta att vi använder den konkreta klassen, inte bara interfacet
            /** @var \Radix\Container\Container $container */
            $container->add(\App\Services\HealthCheckService::class, fn() => $mockHealthService);

            $router = new Router();
            $router->get('/health', [\App\Controllers\Api\HealthController::class, 'index']);

            $middleware = [];
            $dispatcher = new Dispatcher($router, $container, $middleware);

            $request = new Request(
                uri: '/health',
                method: 'GET',
                get: [],
                post: [],
                files: [],
                cookie: [],
                server: []
            );

            // 2. Kör requesten
            $response = $dispatcher->handle($request);

            // 3. Verifiera att vi fick 500
            $this->assertSame(500, $response->getStatusCode());

            $body = json_decode($response->getBody(), true);
            $this->assertIsArray($body);

            // PHPStan Fix 2: Berätta hur arrayen ser ut
            /** @var array{ok: bool} $body */
            $this->assertFalse($body['ok']);
        }

        public function testHealthReturns500WhenOkKeyIsMissing(): void
        {
            putenv('APP_ENV=testing');

            // Mocka så att '_ok' saknas i svaret
            $mockHealthService = $this->createMock(\App\Services\HealthCheckService::class);
            $mockHealthService->method('run')->willReturn(['db' => 'ok']); // Ingen '_ok' nyckel

            $container = ApplicationContainer::get();

            // PHPStan Fix 1 igen
            /** @var \Radix\Container\Container $container */
            $container->add(\App\Services\HealthCheckService::class, fn() => $mockHealthService);

            $router = new Router();
            $router->get('/health', [\App\Controllers\Api\HealthController::class, 'index']);

            $dispatcher = new Dispatcher($router, $container, []);
            $request = new Request(
                '/health',
                'GET',
                get: [],
                post: [],
                files: [],
                cookie: [],
                server: []
            );

            $response = $dispatcher->handle($request);

            $this->assertSame(500, $response->getStatusCode());

            $body = json_decode($response->getBody(), true);
            $this->assertIsArray($body);

            // PHPStan Fix 2 igen
            /** @var array{ok: bool} $body */
            $this->assertFalse($body['ok']);
        }

        public function testHealthReturns200WhenTokenRequiredIsOff(): void
        {
            putenv('APP_ENV=testing');
            putenv('HEALTH_REQUIRE_TOKEN=off');

            $mockHealthService = $this->createMock(\App\Services\HealthCheckService::class);
            $mockHealthService->method('run')->willReturn(['_ok' => true, 'db' => 'ok']);

            $container = ApplicationContainer::get();

            /** @var \Radix\Container\Container $container */
            $container->add(\App\Services\HealthCheckService::class, fn() => $mockHealthService);

            $router = new Router();
            $router->get('/health', [\App\Controllers\Api\HealthController::class, 'index']);

            $dispatcher = new Dispatcher($router, $container, []);
            $request = new Request(
                '/health',
                'GET',
                get: [],
                post: [],
                files: [],
                cookie: [],
                server: []
            );

            $response = $dispatcher->handle($request);

            $this->assertSame(200, $response->getStatusCode());

            putenv('HEALTH_REQUIRE_TOKEN');
        }

        public function testHealthWorksWithDefaultTokenSettings(): void
        {
            putenv('APP_ENV=testing');
            putenv('HEALTH_REQUIRE_TOKEN=off');

            $mockHealthService = $this->createMock(\App\Services\HealthCheckService::class);
            $mockHealthService->method('run')->willReturn(['_ok' => true]);

            $container = ApplicationContainer::get();
            /** @var \Radix\Container\Container $container */
            $container->add(\App\Services\HealthCheckService::class, fn() => $mockHealthService);

            $router = new Router();
            $router->get('/health', [\App\Controllers\Api\HealthController::class, 'index']);

            $dispatcher = new Dispatcher($router, $container, []);
            $request = new Request('/health', 'GET', [], [], [], [], []);

            $response = $dispatcher->handle($request);

            $this->assertSame(200, $response->getStatusCode());

            putenv('HEALTH_REQUIRE_TOKEN');
        }

        public function testHealthReturns200InLocalEnvironmentWithoutConfig(): void
        {
            // Set APP_ENV to local so the default match arm returns false (no token required).
            // This ensures we don't hit validateRequest() which would exit().
            putenv('APP_ENV=local');

            // Ensure HEALTH_REQUIRE_TOKEN is unset.
            // Original code: falls through to default arm -> checks APP_ENV -> returns false.
            // Mutated code (MatchArmRemoval): no default arm -> throws UnhandledMatchError.
            putenv('HEALTH_REQUIRE_TOKEN');

            $mockHealthService = $this->createMock(\App\Services\HealthCheckService::class);
            $mockHealthService->method('run')->willReturn(['_ok' => true, 'db' => 'ok']);

            $container = ApplicationContainer::get();
            /** @var \Radix\Container\Container $container */
            $container->add(\App\Services\HealthCheckService::class, fn() => $mockHealthService);

            $router = new Router();
            $router->get('/health', [\App\Controllers\Api\HealthController::class, 'index']);

            $dispatcher = new Dispatcher($router, $container, []);
            $request = new Request(
                '/health',
                'GET',
                get: [],
                post: [],
                files: [],
                cookie: [],
                server: []
            );

            $response = $dispatcher->handle($request);

            $this->assertSame(200, $response->getStatusCode());
        }

        public function testHealthReturns200WhenTokenRequiredIsFalseString(): void
        {
            putenv('APP_ENV=testing');
            putenv('HEALTH_REQUIRE_TOKEN=false');

            $mockHealthService = $this->createMock(\App\Services\HealthCheckService::class);
            $mockHealthService->method('run')->willReturn(['_ok' => true, 'db' => 'ok']);

            $container = ApplicationContainer::get();

            /** @var \Radix\Container\Container $container */
            $container->add(\App\Services\HealthCheckService::class, fn() => $mockHealthService);

            $router = new Router();
            $router->get('/health', [\App\Controllers\Api\HealthController::class, 'index']);

            $dispatcher = new Dispatcher($router, $container, []);
            $request = new Request(
                '/health',
                'GET',
                get: [],
                post: [],
                files: [],
                cookie: [],
                server: []
            );

            $response = $dispatcher->handle($request);

            $this->assertSame(200, $response->getStatusCode());

            putenv('HEALTH_REQUIRE_TOKEN');
        }

        public function testHealthReturns200WhenTokenRequiredIsZeroString(): void
        {
            putenv('APP_ENV=testing');
            putenv('HEALTH_REQUIRE_TOKEN=0');

            $mockHealthService = $this->createMock(\App\Services\HealthCheckService::class);
            $mockHealthService->method('run')->willReturn(['_ok' => true, 'db' => 'ok']);

            $container = ApplicationContainer::get();

            /** @var \Radix\Container\Container $container */
            $container->add(\App\Services\HealthCheckService::class, fn() => $mockHealthService);

            $router = new Router();
            $router->get('/health', [\App\Controllers\Api\HealthController::class, 'index']);

            $dispatcher = new Dispatcher($router, $container, []);
            $request = new Request(
                '/health',
                'GET',
                get: [],
                post: [],
                files: [],
                cookie: [],
                server: []
            );

            $response = $dispatcher->handle($request);

            $this->assertSame(200, $response->getStatusCode());

            putenv('HEALTH_REQUIRE_TOKEN');
        }


        public function testHealthValidatesTokenWhenRequiredIsTrueString(): void
        {
            // Sätt miljövariabler för att isolera skillnaden mellan original och mutant.
            putenv('APP_ENV=local');
            putenv('HEALTH_REQUIRE_TOKEN=true');
            putenv('API_TOKEN=secret123'); // För att passera validering utan exit()

            $mockHealthService = $this->createMock(\App\Services\HealthCheckService::class);
            $mockHealthService->method('run')->willReturn(['_ok' => true, 'db' => 'ok']);

            $container = ApplicationContainer::get();
            /** @var \Radix\Container\Container $container */
            $container->add(\App\Services\HealthCheckService::class, fn() => $mockHealthService);

            // Mocka DB-kopplingen för att verifiera att cleanupExpiredTokens() körs.
            $pdoStmt = $this->createMock(PDOStatement::class);
            $dbConn = $this->createMock(Connection::class);

            // Vi förväntar oss att execute anropas (för DELETE query i cleanupExpiredTokens).
            $dbConn->expects($this->atLeastOnce())
                ->method('execute')
                ->willReturn($pdoStmt);

            // Skapa en anonym DatabaseManager som returnerar vår mockade connection
            $dbManager = new class ($dbConn) {
                public function __construct(private Connection $conn) {}
                public function connection(): Connection
                {
                    return $this->conn;
                }
            };
            $container->add(\Radix\Database\DatabaseManager::class, fn() => $dbManager);

            $router = new Router();
            $router->get('/health', [\App\Controllers\Api\HealthController::class, 'index']);

            $dispatcher = new Dispatcher($router, $container, []);

            // Skicka headern via server-arrayen som HTTP_AUTHORIZATION
            $request = new Request(
                uri: '/health',
                method: 'GET',
                get: [],
                post: [],
                files: [],
                cookie: [],
                server: ['HTTP_AUTHORIZATION' => 'Bearer secret123']
            );

            $response = $dispatcher->handle($request);

            $this->assertSame(200, $response->getStatusCode());

            putenv('HEALTH_REQUIRE_TOKEN');
            putenv('API_TOKEN');
        }

        public function testHealthValidatesTokenWhenRequiredIsOneString(): void
        {
            // Samma logik som för 'true', men nu testar vi '1'.
            // Om mutanten tar bort '1' kommer den falla till default -> local -> false -> ingen validering -> fail.
            putenv('APP_ENV=local');
            putenv('HEALTH_REQUIRE_TOKEN=1');
            putenv('API_TOKEN=secret123');

            $mockHealthService = $this->createMock(\App\Services\HealthCheckService::class);
            $mockHealthService->method('run')->willReturn(['_ok' => true, 'db' => 'ok']);

            $container = ApplicationContainer::get();
            /** @var \Radix\Container\Container $container */
            $container->add(\App\Services\HealthCheckService::class, fn() => $mockHealthService);

            $pdoStmt = $this->createMock(PDOStatement::class);
            $dbConn = $this->createMock(Connection::class);

            $dbConn->expects($this->atLeastOnce())
                ->method('execute')
                ->willReturn($pdoStmt);

            $dbManager = new class ($dbConn) {
                public function __construct(private Connection $conn) {}
                public function connection(): Connection
                {
                    return $this->conn;
                }
            };
            $container->add(\Radix\Database\DatabaseManager::class, fn() => $dbManager);

            $router = new Router();
            $router->get('/health', [\App\Controllers\Api\HealthController::class, 'index']);

            $dispatcher = new Dispatcher($router, $container, []);

            $request = new Request(
                uri: '/health',
                method: 'GET',
                get: [],
                post: [],
                files: [],
                cookie: [],
                server: ['HTTP_AUTHORIZATION' => 'Bearer secret123']
            );

            $response = $dispatcher->handle($request);

            $this->assertSame(200, $response->getStatusCode());

            putenv('HEALTH_REQUIRE_TOKEN');
            putenv('API_TOKEN');
        }

        public function testHealthValidatesTokenWhenRequiredIsOnString(): void
        {
            // Samma logik som för 'true', men nu testar vi 'on'.
            putenv('APP_ENV=local');
            putenv('HEALTH_REQUIRE_TOKEN=on');
            putenv('API_TOKEN=secret123');

            $mockHealthService = $this->createMock(\App\Services\HealthCheckService::class);
            $mockHealthService->method('run')->willReturn(['_ok' => true, 'db' => 'ok']);

            $container = ApplicationContainer::get();
            /** @var \Radix\Container\Container $container */
            $container->add(\App\Services\HealthCheckService::class, fn() => $mockHealthService);

            $pdoStmt = $this->createMock(PDOStatement::class);
            $dbConn = $this->createMock(Connection::class);

            $dbConn->expects($this->atLeastOnce())
                ->method('execute')
                ->willReturn($pdoStmt);

            $dbManager = new class ($dbConn) {
                public function __construct(private Connection $conn) {}
                public function connection(): Connection
                {
                    return $this->conn;
                }
            };
            $container->add(\Radix\Database\DatabaseManager::class, fn() => $dbManager);

            $router = new Router();
            $router->get('/health', [\App\Controllers\Api\HealthController::class, 'index']);

            $dispatcher = new Dispatcher($router, $container, []);

            $request = new Request(
                uri: '/health',
                method: 'GET',
                get: [],
                post: [],
                files: [],
                cookie: [],
                server: ['HTTP_AUTHORIZATION' => 'Bearer secret123']
            );

            $response = $dispatcher->handle($request);

            $this->assertSame(200, $response->getStatusCode());

            putenv('HEALTH_REQUIRE_TOKEN');
            putenv('API_TOKEN');
        }

        public function testHealthServiceLogsCheckExecution(): void
        {
            $spyLogger = new TestSpyLogger();

            $service = new \App\Services\HealthCheckService($spyLogger);
            $service->run();

            $this->assertNotEmpty($spyLogger->logs, 'Expected logs to be written');

            $firstLogEntry = $spyLogger->logs[0] ?? [];

            // Kontrollera att 'php' finns i kontexten för att döda ArrayItemRemoval
            if (is_array($firstLogEntry)) {
                $ctx = $firstLogEntry['ctx'] ?? [];
                $this->assertArrayHasKey('php', $ctx, 'Context should contain "php" key');
                $this->assertArrayHasKey('time', $ctx, 'Context should contain "time" key');
            }

            $firstMsg = '';
            if (is_array($firstLogEntry)) {
                $firstMsg = $firstLogEntry['msg'] ?? '';
            } elseif (is_string($firstLogEntry)) {
                $firstMsg = $firstLogEntry;
            }

            if (!is_string($firstMsg)) {
                $firstMsg = '';
            }

            $this->assertStringContainsString('start', $firstMsg, 'Expected start log');
        }

        public function testHealthServiceLogsFileSystemCheckSuccess(): void
        {
            $spyLogger = new TestSpyLogger();

            $service = new \App\Services\HealthCheckService($spyLogger);
            $service->run();

            $foundFsLog = false;
            $foundDirInContext = false;

            foreach ($spyLogger->logs as $entry) {
                if (!is_array($entry)) {
                    continue;
                }
                $msg = $entry['msg'] ?? '';
                if (!is_string($msg)) {
                    continue;
                }

                if (str_contains($msg, 'fs=ok')) {
                    $foundFsLog = true;
                    $ctx = $entry['ctx'] ?? [];
                    if (is_array($ctx) && isset($ctx['dir']) && !empty($ctx['dir'])) {
                        $foundDirInContext = true;
                    }
                    break;
                }
            }

            $this->assertTrue($foundFsLog, 'Expected fs=ok log message not found');
            $this->assertTrue($foundDirInContext, 'Expected "dir" key in context for fs=ok log');
        }

        public function testHealthServiceFailsIfProbeFileIsWrittenToWrongLocation(): void
        {
            $projectRoot = defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__, 2);
            $cacheDir = $projectRoot . '/cache';
            if (!is_dir($cacheDir)) {
                mkdir($cacheDir, 0o777, true);
            }

            $trapFile = $cacheDir . DIRECTORY_SEPARATOR . 'healthprobe.txt';

            file_put_contents($trapFile, 'trap');

            chmod($trapFile, 0o444);

            try {
                $spyLogger = new TestSpyLogger();

                $service = new \App\Services\HealthCheckService($spyLogger);
                $result = $service->run();

                $this->assertTrue($result['_ok'], 'Health check failed! Does the mutant path match our trap file?');
                $this->assertEquals('ok', $result['fs'] ?? '', 'FS check failed');

            } finally {
                chmod($trapFile, 0o666);
                @unlink($trapFile);
            }
        }

        public function testHealthCheckExecutesDatabaseQuery(): void
        {
            // Skapa en mock för Connection där vi förväntar oss att execute anropas
            $pdoStmt = $this->createMock(PDOStatement::class);
            $dbConn = $this->createMock(Connection::class);

            // HÄR är assertionen som dödar MethodCallRemoval av $conn->execute(...)
            $dbConn->expects($this->once())
                ->method('execute')
                ->with('SELECT 1')
                ->willReturn($pdoStmt);

            // Vi måste ersätta DatabaseManager i containern med en som returnerar vår mockade connection
            // Eftersom app() använder ApplicationContainer::get(), måste vi uppdatera containern.

            $dbManager = new class ($dbConn) {
                public function __construct(private Connection $conn) {}
                public function connection(): Connection
                {
                    return $this->conn;
                }
            };

            $container = ApplicationContainer::get();
            // Vi kan behöva "binda om" om det är möjligt, eller skapa en ny container och sätta den.
            // I setUp skapas en ny container och sätts med ApplicationContainer::set().
            // Vi kan hämta den och lägga till vår nya definition.

            // Eftersom ApplicationContainer::get() returnerar containern som sattes i setUp...
            // Vi behöver bara överskriva definitionen.
            // Radix Container har ingen 'set' metod för att skriva över, men 'add' skriver oftast över.
            /** @var \Radix\Container\Container $container */
            $container->add(\Radix\Database\DatabaseManager::class, fn() => $dbManager);

            $spyLogger = new TestSpyLogger();
            $service = new \App\Services\HealthCheckService($spyLogger);
            $service->run();
        }

        public function testHealthServiceLogsDatabaseCheckSuccess(): void
        {
            // Mocka app() och DB om det behövs, eller lita på att app() finns (vilket det verkar göra)
            // Om app() finns i testmiljön och vi har en mockad DB-koppling (från setUp), så borde 'db=ok' loggas.

            $spyLogger = new TestSpyLogger();
            $service = new \App\Services\HealthCheckService($spyLogger);

            // Vi behöver se till att app() returnerar en mockad DatabaseManager.
            // I setUp() gör vi: $container->add(\Radix\Database\DatabaseManager::class, fn() => $dbManager);
            // Och app() använder ApplicationContainer::get().
            // Så det borde fungera.

            $service->run();

            $foundDbLog = false;
            foreach ($spyLogger->logs as $entry) {
                $msg = is_array($entry) ? ($entry['msg'] ?? '') : (string) $entry;
                if (str_contains($msg, 'db=ok')) {
                    $foundDbLog = true;
                    break;
                }
            }

            $this->assertTrue($foundDbLog, 'Expected log message "db=ok" was not found. DB check might have failed or been skipped.');
        }

        public function testHealthServiceLogsDirectoryCreation(): void
        {
            // Använd samma logik som i servicen för att hitta katalogen
            $projectRoot = dirname(__DIR__, 2);
            // Ingen rtrim i servicen längre
            $healthDir = $projectRoot . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'health';

            // Se till att den är borta innan vi startar
            if (is_dir($healthDir)) {
                foreach (scandir($healthDir) ?: [] as $file) {
                    if ($file !== '.' && $file !== '..') {
                        @unlink($healthDir . DIRECTORY_SEPARATOR . $file);
                    }
                }
                @rmdir($healthDir);
            }

            // Dubbelkolla att den är borta
            $this->assertDirectoryDoesNotExist($healthDir, 'Failed to cleanup health directory before test');

            $spyLogger = new TestSpyLogger();

            $oldUmask = umask(0);

            try {
                $service = new \App\Services\HealthCheckService($spyLogger);
                $service->run();

                $foundCreatedDirLog = false;
                $foundDirInContext = false;

                foreach ($spyLogger->logs as $entry) {
                    if (!is_array($entry)) {
                        continue;
                    }
                    $msg = $entry['msg'] ?? '';
                    if (!is_string($msg)) {
                        continue;
                    }

                    if (str_contains($msg, 'created_dir')) {
                        $foundCreatedDirLog = true;
                        $ctx = $entry['ctx'] ?? [];
                        if (is_array($ctx) && isset($ctx['dir']) && !empty($ctx['dir'])) {
                            $foundDirInContext = true;
                        }
                        break;
                    }
                }

                $this->assertTrue($foundCreatedDirLog, 'Expected created_dir log message was not found');
                $this->assertTrue($foundDirInContext, 'Expected "dir" key in context for created_dir log');

                if (DIRECTORY_SEPARATOR === '/') {
                    $perms = fileperms($healthDir) & 0o777;
                    $this->assertEquals(0o755, $perms, sprintf('Directory permissions mismatch. Expected 0755, got 0%o', $perms));
                }

                $actualPerms = \App\Services\FileSystemSpy::$lastMkdirPermissions;
                if ($actualPerms !== null) {
                    $this->assertEquals(0o755, $actualPerms, sprintf('mkdir permissions mismatch. Expected 0755, got %d', $actualPerms));
                }

            } finally {
                umask($oldUmask);
            }
        }
    }
}
