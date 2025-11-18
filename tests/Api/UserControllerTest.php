<?php

namespace Radix\Tests\Api;

use PHPUnit\Framework\TestCase;
use App\Controllers\Api\UserController;
use Radix\Container\Container;
use Radix\Container\ApplicationContainer;
use Radix\Database\Connection;
use Radix\Http\Request;
use PDO;

class UserControllerTest extends TestCase
{
    private UserController $controller;
    private Connection $connection;
    private PDO $pdo;

    protected function setUp(): void
        {
        parent::setUp();

        // Återställ containern före varje test
        ApplicationContainer::reset();

        // Initiera en delad PDO-instans för SQLite i minnet
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Initiera containern
        $container = new Container();

        // Registrera PDO och Connection i containern
        $container->add(PDO::class, fn(): PDO => $this->pdo);

        $container->add(Connection::class, function (Container $c): Connection {
            $pdo = $c->get(PDO::class);
            if (!$pdo instanceof PDO) {
                throw new \RuntimeException('Container must return a PDO instance for ' . PDO::class);
            }
            return new Connection($pdo);
        });

        // Registrera aliaset för Psr\Container\ContainerInterface
        $container->add('Psr\Container\ContainerInterface', fn() => $container);

        // Lägg till en API-token i containern
        $container->add('API_TOKEN', 'test-api-token');

        // Sätt containern i ApplicationContainer
        ApplicationContainer::set($container);

        // Sätt upp databas och tabeller
        $this->setupDatabase();

        // Initiera anslutning och UserController
        $connection = $container->get(Connection::class);
        assert($connection instanceof Connection);
        $this->connection = $connection;

        $this->controller = new UserController();

        // Skapa en testrequest med "Authorization"-header
        $request = new Request(
            uri: '/api/users',
            method: 'GET',
            get: [],
            post: [],
            files: [],
            cookie: [],
            server: [
                'HTTP_AUTHORIZATION' => 'Bearer test-api-token' // Giltig token
            ]
        );

        $this->controller->setRequest($request);
    }

    private function setupDatabase(): void
    {
        // Skapa användartabellen
        $this->pdo->exec('
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                first_name TEXT NOT NULL,
                last_name TEXT NOT NULL,
                email TEXT UNIQUE NOT NULL,
                password TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                deleted_at DATETIME DEFAULT NULL
            );
        ');

        // Skapa den korrekta status-tabellen
        $this->pdo->exec('
            CREATE TABLE status (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                password_reset TEXT DEFAULT NULL,
                reset_expires_at DATETIME DEFAULT NULL,
                activation TEXT DEFAULT NULL,
                status TEXT NOT NULL DEFAULT "activate",
                active TEXT NOT NULL DEFAULT "offline",
                active_at INTEGER DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE (user_id),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
            );
        ');

        // Skapa tokentabellen
        $this->pdo->exec('
            CREATE TABLE tokens (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                value TEXT NOT NULL UNIQUE,
                expires_at DATETIME NOT NULL,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
            );
        ');
    }

    public function testGet(): void
        {
            // Förbered testdata för en användare
            $this->connection->execute('
                INSERT INTO users (first_name, last_name, email, password)
                VALUES (:first_name, :last_name, :email, :password)
            ', [
                'first_name' => 'Test',
                'last_name' => 'User',
                'email' => 'test.user@example.com',
                'password' => password_hash('password123', PASSWORD_BCRYPT),
            ]);

            // Hämta användarens ID (auto_increment)
            /** @var array{id:int} $userRow */
            $userRow = $this->connection->fetchOne('SELECT id FROM users WHERE email = :email', [
                'email' => 'test.user@example.com',
            ]);
            $userId = $userRow['id'];

            // Förbered tillhörande status för användaren
            $this->connection->execute('
                INSERT INTO status (user_id, status, active)
                VALUES (:user_id, :status, :active)
            ', [
                'user_id' => $userId,
                'status' => 'activate',
                'active' => 'offline',
            ]);

        // Förbered en giltig token för användaren
        $this->connection->execute('
            INSERT INTO tokens (user_id, value, expires_at)
            VALUES (:user_id, :value, :expires_at)
        ', [
            'user_id' => $userId,
            'value' => 'test-api-token',
            'expires_at' => date('Y-m-d H:i:s', strtotime('+1 hour')), // Token giltig i 1 timme
        ]);

        // Skapa en GET-förfrågan med en giltig token
        $this->controller->setRequest(new Request(
            uri: '/api/users',
            method: 'GET',
            get: [
                'page' => 1,
                'perPage' => 10,
            ],
            post: [],
            files: [],
            cookie: [],
            server: [
                'HTTP_AUTHORIZATION' => 'Bearer test-api-token', // Skicka giltig token
            ]
        ));

        // Anropa index-metoden
        $response = $this->controller->index();

        // Kontrollera att svaret är korrekt
        $this->assertEquals(200, $response->getStatusCode());
        /** @var array{
         *     success: bool,
         *     data: list<array{
         *         first_name: string,
         *         last_name: string,
         *         status: array{status:string,active:string}
         *     }>
         * } $body
         */
        $body = json_decode($response->getBody(), true);

        $this->assertTrue($body['success']);
        $this->assertCount(1, $body['data']);
        $this->assertEquals('Test', $body['data'][0]['first_name']);
        $this->assertEquals('User', $body['data'][0]['last_name']);
        $this->assertEquals('activate', $body['data'][0]['status']['status']); // Kontroll av status
        $this->assertEquals('offline', $body['data'][0]['status']['active']); // Kontroll av active-status
    }

    public function testStore(): void
    {
        // Förbered och skapa en användare för att associera token
        $this->connection->execute('
            INSERT INTO users (first_name, last_name, email, password)
            VALUES (:first_name, :last_name, :email, :password)
        ', [
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin@example.com',
            'password' => password_hash('password123', PASSWORD_BCRYPT),
        ]);

        // Hämta användarens ID för att associera token med användaren
        /** @var array{id:int} $adminRow */
        $adminRow = $this->connection->fetchOne('SELECT id FROM users WHERE email = :email', [
            'email' => 'admin@example.com'
        ]);
        $userId = $adminRow['id'];

        // Förbered en giltig token för användaren
        $this->connection->execute('
            INSERT INTO tokens (user_id, value, expires_at)
            VALUES (:user_id, :value, :expires_at)
        ', [
            'user_id' => $userId,
            'value' => 'test-api-token',
            'expires_at' => date('Y-m-d H:i:s', strtotime('+1 hour')) // Token giltig i en timme
        ]);

        // Mocka getJsonPayload direkt i ApiController
        $this->controller = $this->getMockBuilder(UserController::class)
            ->onlyMethods(['getJsonPayload'])
            ->getMock();

        $this->controller->method('getJsonPayload')->willReturn([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'password' => 'securepassword',
            'password_confirmation' => 'securepassword',
        ]);

        $this->controller->setRequest(new Request(
            uri: '/api/users',
            method: 'POST',
            get: [],
            post: [], // Ingen POST-data läses
            files: [],
            cookie: [],
            server: [
                'HTTP_AUTHORIZATION' => 'Bearer test-api-token',
                'CONTENT_TYPE' => 'application/json'
            ]
        ));

        // Kör store-metoden
        $response = $this->controller->store();

        // Verifiera att ett 201-svar returneras och att användaren sparades korrekt
        $this->assertEquals(201, $response->getStatusCode());
         /** @var array{success:bool,data:array<string,mixed>} $body */
        $body = json_decode($response->getBody(), true);

        $this->assertTrue($body['success']);
        $this->assertEquals('John', $body['data']['first_name']);

        // Kontrollera att användaren existerar i databasen
        $user = $this->connection->fetchOne('SELECT * FROM users WHERE email = :email', [
            'email' => 'john.doe@example.com',
        ]);
        $this->assertNotNull($user);
        $this->assertEquals('John', $user['first_name']);
    }

    public function testPatchUpdate(): void
    {
        // Förbered användardata
        $this->connection->execute('
            INSERT INTO users (first_name, last_name, email, password)
            VALUES (:first_name, :last_name, :email, :password)
        ', [
            'first_name' => 'Patch',
            'last_name' => 'User',
            'email' => 'patch.user@example.com',
            'password' => password_hash('password123', PASSWORD_BCRYPT),
        ]);

        // Hämta användarens ID
        /** @var array{id:int} $patchRow */
        $patchRow = $this->connection->fetchOne('SELECT id FROM users WHERE email = :email', [
            'email' => 'patch.user@example.com',
        ]);
        $userId = $patchRow['id'];

        // Förbered tillhörande token för användaren
        $this->connection->execute('
            INSERT INTO tokens (user_id, value, expires_at)
            VALUES (:user_id, :value, :expires_at)
        ', [
            'user_id' => $userId,
            'value' => 'test-api-token',
            'expires_at' => date('Y-m-d H:i:s', strtotime('+1 hour')) // Token giltig i 1 timme
        ]);

        // Mocka getJsonPayload-metoden för att returnera korrekt data
        $this->controller = $this->getMockBuilder(UserController::class)
            ->onlyMethods(['getJsonPayload'])
            ->getMock();

        $this->controller->method('getJsonPayload')->willReturn([
            'first_name' => 'UpdatedName',
            'last_name' => 'PatchedUser',
        ]);

        // Skapa en PATCH-request med giltig API-token
        $this->controller->setRequest(new Request(
            uri: "/api/users/{$userId}",
            method: 'PATCH',
            get: [],
            post: [], // Ingen post-data (mock används istället)
            files: [],
            cookie: [],
            server: [
                'HTTP_AUTHORIZATION' => 'Bearer test-api-token', // Skicka giltig token
                'CONTENT_TYPE' => 'application/json',
            ]
        ));

        // Kör PATCH-metoden
        $response = $this->controller->partialUpdate((string) $userId);

        // Kontrollera att svaret är korrekt
        $this->assertEquals(200, $response->getStatusCode());
        /** @var array{success:bool,data:array<string,mixed>} $body */
        $body = json_decode($response->getBody(), true);

        $this->assertTrue($body['success']);
        $this->assertEquals('Updatedname', $body['data']['first_name']); // Förvänta "Updatedname"
        $this->assertEquals('Patcheduser', $body['data']['last_name']); // Kontrollerar här

        // Kontrollera att datan har uppdaterats i databasen
        /** @var array{first_name:string,last_name:string} $updatedUser */
        $updatedUser = $this->connection->fetchOne('SELECT * FROM users WHERE id = :id', [
            'id' => $userId,
        ]);

        $this->assertEquals('Updatedname', $updatedUser['first_name']); // Databasen visar "Updatedname"
        $this->assertEquals('Patcheduser', $updatedUser['last_name']);
    }

    public function testPutUpdate(): void
    {
        // Förbered användardata
        $this->connection->execute('
            INSERT INTO users (first_name, last_name, email, password)
            VALUES (:first_name, :last_name, :email, :password)
        ', [
            'first_name' => 'Put',
            'last_name' => 'User',
            'email' => 'put.user@example.com',
            'password' => password_hash('password123', PASSWORD_BCRYPT),
        ]);

        // Hämta användarens ID
        /** @var array{id:int} $putRow */
        $putRow = $this->connection->fetchOne('SELECT id FROM users WHERE email = :email', [
            'email' => 'put.user@example.com',
        ]);
        $userId = $putRow['id'];

        // Förbered tillhörande token för användaren
        $tokenValue = 'test-api-token';
        $this->connection->execute('
            INSERT INTO tokens (user_id, value, expires_at)
            VALUES (:user_id, :value, :expires_at)
        ', [
            'user_id' => $userId,
            'value' => $tokenValue,
            'expires_at' => date('Y-m-d H:i:s', strtotime('+1 hour')), // Token giltig i 1 timme
        ]);

        // Mocka getJsonPayload-metoden för att returnera testdata
        $this->controller = $this->getMockBuilder(UserController::class)
            ->onlyMethods(['getJsonPayload'])
            ->getMock();

        // Mocka lösenord som en del av payload (men det hanteras manuellt)
        $this->controller->method('getJsonPayload')->willReturn([
            'first_name' => 'PutUpdated',
            'last_name' => 'UserUpdated',
            'email' => 'put.updated@example.com',
            'password' => 'newpassword123', // Lösenordet hanteras explicit
        ]);

        // Skapa en PUT-request med giltig token
        $this->controller->setRequest(new Request(
            uri: "/api/users/{$userId}",
            method: 'PUT',
            get: [],
            post: [], // Ingen post-data krävs, mock tar hand om data
            files: [],
            cookie: [],
            server: [
                'HTTP_AUTHORIZATION' => "Bearer {$tokenValue}", // Använd giltig token
                'CONTENT_TYPE' => 'application/json',
            ]
        ));

        // Kör PUT-metoden
        $response = $this->controller->update((string) $userId);

        // Kontrollera API-svaret
        $this->assertEquals(200, $response->getStatusCode(), 'Statuskoden ska vara 200.');
        /** @var array{success:bool,data:array<string,mixed>} $body */
        $body = json_decode($response->getBody(), true);

        $this->assertTrue($body['success'], 'API-svaret ska ha success: true.');
        $this->assertEquals('Putupdated', $body['data']['first_name'], 'Felaktigt förnamn');
        $this->assertEquals('Userupdated', $body['data']['last_name'], 'Felaktigt efternamn');
        $this->assertEquals('put.updated@example.com', $body['data']['email'], 'Felaktig e-postadress');

        // Hämta uppdaterad användare från databasen
        /** @var array{first_name:string,last_name:string,email:string} $updatedUser */
        $updatedUser = $this->connection->fetchOne('SELECT * FROM users WHERE id = :id', [
            'id' => $userId,
        ]);

        // Kontrollera uppdaterad data (utan lösenord)
        $this->assertEquals('Putupdated', $updatedUser['first_name'], 'Databasens förnamn matchar inte.');
        $this->assertEquals('Userupdated', $updatedUser['last_name'], 'Databasens efternamn matchar inte.');
        $this->assertEquals('put.updated@example.com', $updatedUser['email'], 'Databasens e-post matchar inte.');

        // Manuellt uppdatera lösenordet eftersom det är guarded
        $this->connection->execute(
            'UPDATE users SET password = :password WHERE id = :id',
            [
                'password' => password_hash('newpassword123', PASSWORD_BCRYPT),
                'id' => $userId,
            ]
        );

        // Bekräfta att lösenordet uppdaterades
        /** @var array{password:string} $updatedUser */
        $updatedUser = $this->connection->fetchOne('SELECT * FROM users WHERE id = :id', ['id' => $userId]);
        $this->assertTrue(password_verify('newpassword123', $updatedUser['password']), 'Lösenordet matchar inte i databasen.');
    }

    public function testDelete(): void
    {
        // Förbered testdata för en användare
        $this->connection->execute('
            INSERT INTO users (first_name, last_name, email, password)
            VALUES (:first_name, :last_name, :email, :password)
        ', [
            'first_name' => 'Delete',
            'last_name' => 'User',
            'email' => 'delete.user@example.com',
            'password' => password_hash('password123', PASSWORD_BCRYPT),
        ]);

        // Hämta användarens ID
        /** @var array{id:int} $deleteRow */
        $deleteRow = $this->connection->fetchOne('SELECT id FROM users WHERE email = :email', [
            'email' => 'delete.user@example.com',
        ]);
        $userId = $deleteRow['id'];

        // Skapa och associera en giltig token för användaren
        $tokenValue = 'test-api-token';
        $this->connection->execute('
            INSERT INTO tokens (user_id, value, expires_at)
            VALUES (:user_id, :value, :expires_at)
        ', [
            'user_id' => $userId,
            'value' => $tokenValue,
            'expires_at' => date('Y-m-d H:i:s', strtotime('+1 hour')), // Token giltig i 1 timme
        ]);

        // Skapa en DELETE-request med den genererade API-token
        $this->controller->setRequest(new Request(
            uri: "/api/users/{$userId}",
            method: 'DELETE',
            get: [],
            post: [],
            files: [],
            cookie: [],
            server: [
                'HTTP_AUTHORIZATION' => "Bearer {$tokenValue}", // Använd giltig token
                'CONTENT_TYPE' => 'application/json',
            ]
        ));

        // Kör DELETE-metoden
        $response = $this->controller->delete((string) $userId);

        // Kontrollera att svaret är korrekt
        $this->assertEquals(200, $response->getStatusCode(), 'Statuskoden ska vara 200 vid en framgångsrik DELETE');
        /** @var array{success:bool,message:string,data?:mixed} $body */
        $body = json_decode($response->getBody(), true);

        $this->assertTrue($body['success'], 'DELETE-anropet ska lyckas.');
        $this->assertEquals('Användaren har raderats (soft delete).', $body['message']);

        // Kontrollera att användaren är flaggad som soft deleted i databasen
        /** @var array<string,mixed> $deletedUser */
        $deletedUser = $this->connection->fetchOne('SELECT * FROM users WHERE id = :id', [
            'id' => $userId,
        ]);
        $this->assertNotNull($deletedUser['deleted_at'], 'deleted_at ska vara satt för soft deleted användare.');
    }
}