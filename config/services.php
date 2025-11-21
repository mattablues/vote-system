<?php

declare(strict_types=1);

use Radix\Config\Config;
use Radix\Config\Dotenv;
use Radix\Console\CommandsRegistry;
use Radix\Database\DatabaseManager;
use Radix\Database\Migration\Migrator;
use Radix\Mailer\MailManager;
use Radix\Viewer\TemplateViewerInterface;

// Ladda miljövariabler
$dotenv = new Dotenv(ROOT_PATH . '/.env', ROOT_PATH);
$dotenv->load();

// Skapa containern
$container = new Radix\Container\Container();
$container->add(\Psr\Container\ContainerInterface::class, $container);

$excludeFiles = ['services.php', 'routes.php', 'middleware.php', 'providers.php', 'listeners.php'];

/** @var list<string> $configFiles */
$configFiles = glob(ROOT_PATH . '/config/*.php') ?: [];

$configFiles = array_filter($configFiles, function (string $file) use ($excludeFiles): bool {
    return !in_array(basename($file), $excludeFiles, true);
});

/** @var array<string,mixed> $configData */
$configData = [];

// Sammanslå innehåll från alla andra konfigurationsfiler
foreach ($configFiles as $file) {
    $loadedConfig = require $file;

    if (!is_array($loadedConfig)) {
        throw new \RuntimeException(sprintf('Config file "%s" must return an array.', $file));
    }

    /** @var array<string,mixed> $loadedConfig */
    $configData = array_merge_deep($configData, $loadedConfig);
}

// Registrera den sammanslagna konfigurationen i containern
/** @var array<string,mixed> $configData */
$container->add('config', new Config($configData));
$container->add(\Radix\Support\FileCache::class, fn() => new \Radix\Support\FileCache());

$container->addShared(\Radix\Support\Logger::class, fn() => new \Radix\Support\Logger('app'));

$container->add(\App\Services\HealthCheckService::class, function () {
    // injicera delad logger, eller skapa kanal-specifik
    $logger = new \Radix\Support\Logger('health');
    return new \App\Services\HealthCheckService($logger);
});

$container->addShared(\Radix\Database\Connection::class, function () use ($container) {
    $config = $container->get('config');

    if (!$config instanceof \Radix\Config\Config) {
        throw new \RuntimeException('Container returned invalid config instance.');
    }

    /** @var \Radix\Config\Config $config */

    $dbConfig = $config->get('database');

    if (!is_array($dbConfig)) {
        throw new \RuntimeException('Database configuration must be an array.');
    }

    /** @var array<string,mixed> $dbConfig */

    $driverRaw   = $dbConfig['driver']   ?? null;
    $databaseRaw = $dbConfig['database'] ?? null;
    $hostRaw     = $dbConfig['host']     ?? '127.0.0.1';
    $portRaw     = $dbConfig['port']     ?? 3306;
    $charsetRaw  = $dbConfig['charset']  ?? 'utf8mb4';
    $userRaw     = $dbConfig['username'] ?? null;
    $passRaw     = $dbConfig['password'] ?? null;
    $optionsRaw  = $dbConfig['options']  ?? [];

    if (!is_string($driverRaw) || $driverRaw === '') {
        throw new \RuntimeException('Database driver must be a non-empty string.');
    }
    if (!is_string($databaseRaw) || $databaseRaw === '') {
        throw new \RuntimeException('Database name must be a non-empty string.');
    }
    $driver   = $driverRaw;
    $database = $databaseRaw;

    $host = is_string($hostRaw) && $hostRaw !== '' ? $hostRaw : '127.0.0.1';

    if (is_int($portRaw)) {
        $port = (string) $portRaw;
    } elseif (is_string($portRaw) && $portRaw !== '') {
        $port = $portRaw;
    } else {
        $port = '3306';
    }

    $charset = is_string($charsetRaw) && $charsetRaw !== '' ? $charsetRaw : 'utf8mb4';

    $userRaw = $dbConfig['username'] ?? null;
    $passRaw = $dbConfig['password'] ?? null;

    if ($userRaw !== null && !is_string($userRaw)) {
        throw new \RuntimeException('Database username must be a string or null.');
    }
    if ($passRaw !== null && !is_string($passRaw)) {
        throw new \RuntimeException('Database password must be a string or null.');
    }

    /** @var string|null $username */
    $username = $userRaw;
    /** @var string|null $password */
    $password = $passRaw;

    $optionsRaw = $dbConfig['options'] ?? [];
    if (!is_array($optionsRaw)) {
        throw new \RuntimeException('Database options must be an array.');
    }
    /** @var array<mixed> $options */
    $options = $optionsRaw;

    // Skapa DSN-strängen baserat på föraren
    $dsn = match ($driver) {
        'sqlite' => "sqlite:{$database}",
        'mysql' => sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $host,
            $port,
            $database,
            $charset
        ),
        default => throw new \RuntimeException("Unsupported database driver: {$driver}"),
    };

    try {
        $pdo = new \PDO($dsn, $username, $password, $options);

        return new \Radix\Database\Connection($pdo);
    } catch (\PDOException $e) {
        throw new \RuntimeException(
            "Failed to connect to the database: " . $e->getMessage(),
            (int) $e->getCode(),
            $e
        );
    }
});

$container->addShared(\Radix\DateTime\RadixDateTime::class, function () use ($container) {
    $config = $container->get('config');

    if (!$config instanceof \Radix\Config\Config) {
        throw new \RuntimeException('Container returned invalid config instance.');
    }

    /** @var \Radix\Config\Config $config */

    return new \Radix\DateTime\RadixDateTime($config);
});

$container->add(\Radix\Database\Migration\Migrator::class, function () use ($container) {
    $connection = $container->get(\Radix\Database\Connection::class);

    if (!$connection instanceof \Radix\Database\Connection) {
        throw new \RuntimeException('Container returned invalid database connection.');
    }

    /** @var \Radix\Database\Connection $connection */

    $migrationsPath = ROOT_PATH . '/migrations';

    return new Migrator($connection, $migrationsPath);
});

$container->add(\Radix\Console\Commands\MigrationCommand::class, function () use ($container) {
    $migrator = $container->get(\Radix\Database\Migration\Migrator::class);

    if (!$migrator instanceof \Radix\Database\Migration\Migrator) {
        throw new \RuntimeException('Container returned invalid migrator instance.');
    }

    /** @var \Radix\Database\Migration\Migrator $migrator */

    return new \Radix\Console\Commands\MigrationCommand($migrator);
});

$container->add(\Radix\Console\Commands\MakeControllerCommand::class, function () {
    $controllerPath = ROOT_PATH . '/src/Controllers';
    $templatePath = ROOT_PATH . '/templates';

    return new \Radix\Console\Commands\MakeControllerCommand($controllerPath, $templatePath);
});

$container->add(\Radix\Console\Commands\MakeEventCommand::class, function () {
    $eventPath = ROOT_PATH . '/src/Events';
    $templatePath = ROOT_PATH . '/templates';

    return new \Radix\Console\Commands\MakeEventCommand($eventPath, $templatePath);
});

$container->add(\Radix\Console\Commands\MakeListenerCommand::class, function () {
    $listenerPath = ROOT_PATH . '/src/EventListeners';
    $templatePath = ROOT_PATH . '/templates';

    return new \Radix\Console\Commands\MakeListenerCommand($listenerPath, $templatePath);
});

$container->add(\Radix\Console\Commands\MakeMiddlewareCommand::class, function () {
    $middlewarePath = ROOT_PATH . '/src/Middlewares';
    $templatePath = ROOT_PATH . '/templates';

    return new \Radix\Console\Commands\MakeMiddlewareCommand($middlewarePath, $templatePath);
});

$container->add(\Radix\Console\Commands\MakeServiceCommand::class, function () {
    $servicePath = ROOT_PATH . '/src/Services';
    $templatePath = ROOT_PATH . '/templates';

    return new \Radix\Console\Commands\MakeServiceCommand($servicePath, $templatePath);
});

$container->add(\Radix\Console\Commands\MakeProviderCommand::class, function () {
    $providerPath = ROOT_PATH . '/src/Providers';
    $templatePath = ROOT_PATH . '/templates';

    return new \Radix\Console\Commands\MakeProviderCommand($providerPath, $templatePath);
});

$container->add(\Radix\Console\Commands\MakeMigrationCommand::class, function () {
    $migrationPath = ROOT_PATH . '/migrations';
    $templatePath = ROOT_PATH . '/templates/migrations';

    // Säkerställ att katalogerna är tillgängliga
    if (!is_dir($migrationPath)) {
        mkdir($migrationPath, 0o755, true);
    }
    if (!is_dir($templatePath)) {
        mkdir($templatePath, 0o755, true);
    }

    // Returnera en korrekt instans
    return new Radix\Console\Commands\MakeMigrationCommand($migrationPath, $templatePath);
});

$container->add(\Radix\Database\DatabaseManager::class, function () use ($container) {
    return new DatabaseManager($container);
});

$container->add(\Radix\Console\Commands\MakeModelCommand::class, function () {
    // Definiera paths för modeller och mallar
    $modelPath = ROOT_PATH . '/src/Models';
    $templatePath = ROOT_PATH . '/templates';

    // Kontrollera och skapa katalogerna om de inte existerar
    if (!is_dir($modelPath)) {
        mkdir($modelPath, 0o755, true);
    }
    if (!is_dir($templatePath)) {
        mkdir($templatePath, 0o755, true);
    }

    // Returnera instansen av MakeModelCommand med rätt beroenden
    return new Radix\Console\Commands\MakeModelCommand($modelPath, $templatePath);
});

$container->add(\Radix\Database\Migration\Schema::class, function () use ($container) {
    $connection = $container->get(\Radix\Database\Connection::class);

    if (!$connection instanceof \Radix\Database\Connection) {
        throw new \RuntimeException('Container returned invalid database connection.');
    }

    /** @var \Radix\Database\Connection $connection */

    return new \Radix\Database\Migration\Schema($connection);
});

$container->add(\Radix\Console\CommandsRegistry::class, function () {
    $registry = new CommandsRegistry();

    // Registrera alla CLI-kommandon med det nya namnsystemet
    $registry->register('migrations:migrate', Radix\Console\Commands\MigrationCommand::class);
    $registry->register('migrations:rollback', Radix\Console\Commands\MigrationCommand::class);
    $registry->register('make:migration', Radix\Console\Commands\MakeMigrationCommand::class);
    $registry->register('make:model', Radix\Console\Commands\MakeModelCommand::class); // Nytt kommando
    $registry->register('make:controller', Radix\Console\Commands\MakeControllerCommand::class);
    $registry->register('make:event', Radix\Console\Commands\MakeEventCommand::class);
    $registry->register('make:listener', Radix\Console\Commands\MakeListenerCommand::class);
    $registry->register('make:middleware', Radix\Console\Commands\MakeMiddlewareCommand::class);
    $registry->register('make:service', Radix\Console\Commands\MakeServiceCommand::class);
    $registry->register('make:provider', Radix\Console\Commands\MakeProviderCommand::class);

    return $registry;
});

$container->addShared(\Radix\Session\RadixSessionHandler::class, function () use ($container) {
    /** @var \PDO|null $dbConnection */
    $dbConnection = null;

    try {
        /** @var \Radix\Database\Connection $connection */
        $connection = $container->get(\Radix\Database\Connection::class);
        $dbConnection = $connection->getPDO();
    } catch (\Throwable $e) {
        /** @var \Radix\Support\Logger $logger */
        $logger = $container->get(\Radix\Support\Logger::class);
        $logger->error('Kunde inte hämta PDO för sessionshantering.', [
            'exception' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
    }

    /** @var \Radix\Config\Config $config */
    $config = $container->get('config');

    /** @var array<string,mixed> $sessionConfig */
    $sessionConfig = $config->get('session');

    return new \Radix\Session\RadixSessionHandler($sessionConfig, $dbConnection);
});

$container->addShared(\Radix\Session\SessionInterface::class, \Radix\Session\Session::class);

$container->add(\Radix\Http\Response::class);
$container->add(\Radix\Http\Request::class);
$container->add(\Radix\Routing\Router::class);

$container->addShared(\Radix\Viewer\TemplateViewerInterface::class, function () use ($container) {
    $session = $container->get(\Radix\Session\SessionInterface::class);
    $datetime = $container->get(\Radix\DateTime\RadixDateTime::class); // Hämta den delade RadixDateTime-instansen
    $viewer = new \Radix\Viewer\RadixTemplateViewer();
    $viewer->enableDebugMode(getenv('APP_DEBUG') === '1');

    // Lägg till delade variabler
    $viewer->shared('datetime', $datetime); // Gör datetime tillgänglig i alla vyer
    $viewer->shared('session', $session);

    /** @var \Radix\Session\SessionInterface $session */

    $userIdRaw = $session->get(\Radix\Session\Session::AUTH_KEY);

    /** @var int|null $userId */
    $userId = is_int($userIdRaw) ? $userIdRaw : null;

    if ($userId) {
        /** @var \App\Models\User|null $user */
        $user = \App\Models\User::with(['status', 'token'])
            ->where('id', '=', $userId)
            ->first();

        if ($user !== null) {
            $viewer->shared('currentUser', $user); // Gör currentUser tillgänglig i alla vyer

            $tokenRelation = $user->getRelation('token');

            $currentToken = is_object($tokenRelation) && method_exists($tokenRelation, 'getAttribute')
                ? $tokenRelation->getAttribute('value')
                : null;

            $viewer->shared('currentToken', $currentToken); // Gör currentToken tillgänglig i alla vyer
        }
    }

    return $viewer;
});

$container->addShared(\Radix\EventDispatcher\EventDispatcher::class, \Radix\EventDispatcher\EventDispatcher::class);

$container->add(MailManager::class, function () use ($container) {
    /** @var Config $config */
    $config = $container->get('config');

    /** @var TemplateViewerInterface $templateViewer */
    $templateViewer = $container->get(TemplateViewerInterface::class);

    return MailManager::createDefault($templateViewer, $config);
});

return $container;
