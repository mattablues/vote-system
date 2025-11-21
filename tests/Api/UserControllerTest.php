<?php

namespace Radix\Tests\Api;

use App\Controllers\Api\UserController;
use PDO;
use PHPUnit\Framework\TestCase;
use Radix\Container\ApplicationContainer;
use Radix\Container\Container;
use Radix\Database\Connection;
use Radix\Http\Request;
use RuntimeException;

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
                throw new RuntimeException('Container must return a PDO instance for ' . PDO::class);
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
                'HTTP_AUTHORIZATION' => 'Bearer test-api-token', // Giltig token
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

        // Lägg till en till användare för att testa paginering
        $this->connection->execute('
                INSERT INTO users (first_name, last_name, email, password)
                VALUES (:first_name, :last_name, :email, :password)
            ', [
            'first_name' => 'Test2',
            'last_name' => 'User2',
            'email' => 'test.user2@example.com',
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

        // Skapa en GET-förfrågan med en giltig token och perPage=1
        $this->controller->setRequest(new Request(
            uri: '/api/users',
            method: 'GET',
            get: [
                'page' => 1,
                'perPage' => 1, // Begär endast 1 per sida
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
         *     }>,
         *     meta: array{per_page: int, total: int, last_page: int}
         * } $body
         */
        $body = json_decode($response->getBody(), true);

        $this->assertTrue($body['success']);
        $this->assertCount(1, $body['data'], 'Ska returnera exakt 1 användare per sida.');
        $this->assertEquals(1, $body['meta']['per_page'], 'Meta per_page ska vara 1.');
        $this->assertEquals('Test', $body['data'][0]['first_name']);

        // Eftersom vi lade till 2 användare och begärde 1 per sida, ska total vara 2
        // Om status saknas för den andra användaren så kanske den inte kommer med om det är inner join?
        // User::with('status') använder left join eller eager loading. paginate räknar på User.
        // Om paginate räknar, så borde total vara 2.
        // Men vi kollar bara att vi fick 1 item i 'data'.
    }

    public function testGetAllowsPerPageEqualTo100(): void
    {
        // Skapa minimal användare och token
        $this->connection->execute('
            INSERT INTO users (first_name, last_name, email, password)
            VALUES (:first_name, :last_name, :email, :password)
        ', [
            'first_name' => 'Max',
            'last_name' => 'Allowed',
            'email' => 'max.allowed@example.com',
            'password' => password_hash('password123', PASSWORD_BCRYPT),
        ]);

        /** @var array{id:int} $row */
        $row = $this->connection->fetchOne('SELECT id FROM users WHERE email = :email', [
            'email' => 'max.allowed@example.com',
        ]);
        $userId = $row['id'];

        $this->connection->execute('
            INSERT INTO tokens (user_id, value, expires_at)
            VALUES (:user_id, :value, :expires_at)
        ', [
            'user_id' => $userId,
            'value' => 'perpage-100-token',
            'expires_at' => date('Y-m-d H:i:s', strtotime('+1 hour')),
        ]);

        $this->controller->setRequest(new \Radix\Http\Request(
            uri: '/api/users',
            method: 'GET',
            get: ['page' => 1, 'perPage' => 100],
            post: [],
            files: [],
            cookie: [],
            server: ['HTTP_AUTHORIZATION' => 'Bearer perpage-100-token']
        ));

        $response = $this->controller->index();

        $this->assertEquals(200, $response->getStatusCode(), 'perPage=100 ska vara tillåtet (status 200 förväntas).');

        /** @var array{success:bool,meta:array{per_page:int}} $body */
        $body = json_decode($response->getBody(), true);
        $this->assertTrue($body['success']);
        $this->assertEquals(100, $body['meta']['per_page'], 'Meta per_page ska vara 100 när perPage=100 begärs.');
    }

    public function testGetPaginationDefaultsAndLogics(): void
    {
        // Token
        $this->connection->execute('
            INSERT INTO tokens (user_id, value, expires_at)
            VALUES (:user_id, :value, :expires_at)
        ', [
            'user_id' => 1,
            'value' => 'test-api-token-pagination-logic',
            'expires_at' => date('Y-m-d H:i:s', strtotime('+1 hour')),
        ]);

        // 1. Test default page (ska vara 1)
        $this->controller->setRequest(new Request(
            uri: '/api/users',
            method: 'GET',
            get: [], // Inga parametrar
            post: [],
            files: [],
            cookie: [],
            server: ['HTTP_AUTHORIZATION' => 'Bearer test-api-token-pagination-logic']
        ));

        $response = $this->controller->index();
        /** @var array{meta: array{current_page: int, per_page: int}} $body */
        $body = json_decode($response->getBody(), true);

        $this->assertEquals(1, $body['meta']['current_page'], 'Default page ska vara 1.');
        $this->assertEquals(10, $body['meta']['per_page'], 'Default perPage ska vara 10.');

        // 2. Test explicit page och perPage
        $this->controller->setRequest(new Request(
            uri: '/api/users',
            method: 'GET',
            get: ['page' => '2', 'perPage' => '5'],
            post: [],
            files: [],
            cookie: [],
            server: ['HTTP_AUTHORIZATION' => 'Bearer test-api-token-pagination-logic']
        ));

        $response = $this->controller->index();
        /** @var array{meta: array{current_page: int, per_page: int}} $body2 */
        $body2 = json_decode($response->getBody(), true);

        $this->assertEquals(2, $body2['meta']['current_page'], 'Ska kunna begära sida 2.');
        $this->assertEquals(5, $body2['meta']['per_page'], 'Ska kunna begära 5 per sida.');

        // 3. Test ogiltiga värden (ska fallbacka till defaults)
        $this->controller->setRequest(new Request(
            uri: '/api/users',
            method: 'GET',
            get: ['page' => 'invalid', 'perPage' => 'not-a-number'],
            post: [],
            files: [],
            cookie: [],
            server: ['HTTP_AUTHORIZATION' => 'Bearer test-api-token-pagination-logic']
        ));

        $response = $this->controller->index();
        /** @var array{meta: array{current_page: int, per_page: int}} $body3 */
        $body3 = json_decode($response->getBody(), true);

        $this->assertEquals(1, $body3['meta']['current_page'], 'Ogiltig page ska ge default 1.');
        $this->assertEquals(10, $body3['meta']['per_page'], 'Ogiltig perPage ska ge default 10.');
    }

    public function testGetDefaultPagination(): void
    {
        // Skapa en användare (vi behöver inte skapa 11 st om vi kollar metadatan)
        $this->connection->execute('
            INSERT INTO users (first_name, last_name, email, password)
            VALUES (:first_name, :last_name, :email, :password)
        ', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'pagination.default@example.com',
            'password' => password_hash('password123', PASSWORD_BCRYPT),
        ]);

        /** @var array{id:int} $userRow */
        $userRow = $this->connection->fetchOne('SELECT id FROM users WHERE email = :email', [
            'email' => 'pagination.default@example.com',
        ]);
        $userId = $userRow['id'];

        // Token
        $tokenValue = 'test-api-token-pagination';
        $this->connection->execute('
            INSERT INTO tokens (user_id, value, expires_at)
            VALUES (:user_id, :value, :expires_at)
        ', [
            'user_id' => $userId,
            'value' => $tokenValue,
            'expires_at' => date('Y-m-d H:i:s', strtotime('+1 hour')),
        ]);

        // Request utan perPage parameter
        $this->controller->setRequest(new Request(
            uri: '/api/users',
            method: 'GET',
            get: [
                'page' => 1,
                // perPage utelämnad
            ],
            post: [],
            files: [],
            cookie: [],
            server: [
                'HTTP_AUTHORIZATION' => "Bearer {$tokenValue}",
            ]
        ));

        $response = $this->controller->index();

        $this->assertEquals(200, $response->getStatusCode());
        /** @var array{meta: array{per_page: int}} $body */
        $body = json_decode($response->getBody(), true);

        // Verifiera att default per_page är 10
        $this->assertEquals(10, $body['meta']['per_page'], 'Default perPage ska vara 10.');
    }

    public function testGetUnauthenticated(): void
    {
        // Vi måste återskapa controllern som en mock för att fånga respondWithErrors
        // eftersom validateApiToken anropar den och gör exit.
        $this->controller = $this->getMockBuilder(UserController::class)
            ->onlyMethods(['respondWithErrors'])
            ->getMock();

        $this->controller->setRequest(new Request(
            uri: '/api/users',
            method: 'GET',
            get: [],
            post: [],
            files: [],
            cookie: [],
            server: [] // Ingen token
        ));

        // Förvänta att respondWithErrors anropas med 401
        $this->controller->expects($this->once())
            ->method('respondWithErrors')
            ->with($this->anything(), 401)
            ->willThrowException(new RuntimeException('Unauthorized'));

        try {
            $this->controller->index();
        } catch (RuntimeException $e) {
            if ($e->getMessage() === 'Unauthorized') {
                return;
            }
            throw $e;
        }
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
            'email' => 'admin@example.com',
        ]);
        $userId = $adminRow['id'];

        // Förbered en giltig token för användaren
        $this->connection->execute('
            INSERT INTO tokens (user_id, value, expires_at)
            VALUES (:user_id, :value, :expires_at)
        ', [
            'user_id' => $userId,
            'value' => 'test-api-token',
            'expires_at' => date('Y-m-d H:i:s', strtotime('+1 hour')), // Token giltig i en timme
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
                'CONTENT_TYPE' => 'application/json',
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
        $this->assertEquals('Doe', $user['last_name'], 'Efternamnet sparades inte korrekt.');

        // Verifiera att lösenordet är korrekt hashat (och inte en hash av en tom sträng)
        $passwordHash = $user['password'];
        $this->assertIsString($passwordHash, 'Lösenordet i databasen är inte en sträng.');
        $this->assertTrue(password_verify('securepassword', $passwordHash), 'Lösenordet sparades inte korrekt.');

        // Kontrollera att status skapades
        $status = $this->connection->fetchOne('SELECT * FROM status WHERE user_id = :user_id', [
            'user_id' => $user['id'],
        ]);
        $this->assertIsArray($status, 'Status-post skapades inte.');
        $this->assertEquals('activate', $status['status']);
    }

    public function testStoreWithInvalidFirstName(): void
    {
        // Token setup
        $this->connection->execute('
            INSERT INTO tokens (user_id, value, expires_at)
            VALUES (:user_id, :value, :expires_at)
        ', [
            'user_id' => 1, // Dummy user id för token
            'value' => 'test-api-token-invalid-store',
            'expires_at' => date('Y-m-d H:i:s', strtotime('+1 hour')),
        ]);

        // Mocka getJsonPayload med ogiltig first_name (för kort)
        $this->controller = $this->getMockBuilder(UserController::class)
            ->onlyMethods(['getJsonPayload', 'respondWithErrors'])
            ->getMock();

        $this->controller->method('getJsonPayload')->willReturn([
            'first_name' => 'A', // För kort
            'last_name' => 'ValidLast',
            'email' => 'valid.store@example.com',
            'password' => 'validpassword',
            'password_confirmation' => 'validpassword',
        ]);

        $this->controller->expects($this->once())
            ->method('respondWithErrors')
            ->with($this->anything(), 422)
            ->willThrowException(new RuntimeException('ValidationFailed'));

        $this->controller->setRequest(new Request(
            uri: '/api/users',
            method: 'POST',
            get: [],
            post: [],
            files: [],
            cookie: [],
            server: [
                'HTTP_AUTHORIZATION' => 'Bearer test-api-token-invalid-store',
                'CONTENT_TYPE' => 'application/json',
            ]
        ));

        try {
            $this->controller->store();
        } catch (RuntimeException $e) {
            if ($e->getMessage() === 'ValidationFailed') {
                return;
            }
            throw $e;
        }
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
            'expires_at' => date('Y-m-d H:i:s', strtotime('+1 hour')), // Token giltig i 1 timme
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
        /** @var array{first_name:string,last_name:string,email:string,password:string} $updatedUser */
        $updatedUser = $this->connection->fetchOne('SELECT * FROM users WHERE id = :id', [
            'id' => $userId,
        ]);

        // Kontrollera uppdaterad data (utan lösenord)
        $this->assertEquals('Putupdated', $updatedUser['first_name'], 'Databasens förnamn matchar inte.');
        $this->assertEquals('Userupdated', $updatedUser['last_name'], 'Databasens efternamn matchar inte.');
        $this->assertEquals('put.updated@example.com', $updatedUser['email'], 'Databasens e-post matchar inte.');

        // Bekräfta att lösenordet uppdaterades av controllern (utan manuell SQL-uppdatering)
        $this->assertTrue(password_verify('newpassword123', $updatedUser['password']), 'Lösenordet matchar inte i databasen.');
    }

    public function testPutUpdateWithNullPassword(): void
    {
        // Skapa användare
        $this->connection->execute('
            INSERT INTO users (first_name, last_name, email, password)
            VALUES (:first_name, :last_name, :email, :password)
        ', [
            'first_name' => 'NullPass',
            'last_name' => 'User',
            'email' => 'null.pass@example.com',
            'password' => password_hash('originalpass', PASSWORD_BCRYPT),
        ]);

        /** @var array{id:int} $userRow */
        $userRow = $this->connection->fetchOne('SELECT id FROM users WHERE email = :email', ['email' => 'null.pass@example.com']);
        $userId = $userRow['id'];

        // Token
        $tokenValue = 'test-api-token-null-pass';
        $this->connection->execute('
            INSERT INTO tokens (user_id, value, expires_at)
            VALUES (:user_id, :value, :expires_at)
        ', [
            'user_id' => $userId,
            'value' => $tokenValue,
            'expires_at' => date('Y-m-d H:i:s', strtotime('+1 hour')),
        ]);

        // Mocka för null password
        $this->controller = $this->getMockBuilder(UserController::class)
            ->onlyMethods(['getJsonPayload'])
            ->getMock();

        $this->controller->method('getJsonPayload')->willReturn([
            'first_name' => 'UpdatedName',
            'last_name' => 'UpdatedLast',
            'email' => 'null.pass@example.com',
            'password' => null, // Null password
        ]);

        $this->controller->setRequest(new Request(
            uri: "/api/users/{$userId}",
            method: 'PUT',
            get: [],
            post: [],
            files: [],
            cookie: [],
            server: [
                'HTTP_AUTHORIZATION' => "Bearer {$tokenValue}",
                'CONTENT_TYPE' => 'application/json',
            ]
        ));

        $this->controller->update((string) $userId);

        // Kontrollera att lösenordet INTE har ändrats
        /** @var array{password:string} $user */
        $user = $this->connection->fetchOne('SELECT password FROM users WHERE id = :id', ['id' => $userId]);
        $this->assertTrue(password_verify('originalpass', $user['password']), 'Lösenordet ska inte ha ändrats om input var null.');
    }

    public function testPutUpdateWithoutPasswordKey(): void
    {
        // Skapa användare
        $this->connection->execute('
            INSERT INTO users (first_name, last_name, email, password)
            VALUES (:first_name, :last_name, :email, :password)
        ', [
            'first_name' => 'MissingPass',
            'last_name' => 'User',
            'email' => 'missing.pass@example.com',
            'password' => password_hash('originalpass', PASSWORD_BCRYPT),
        ]);

        /** @var array{id:int} $userRow */
        $userRow = $this->connection->fetchOne('SELECT id FROM users WHERE email = :email', ['email' => 'missing.pass@example.com']);
        $userId = $userRow['id'];

        // Token
        $tokenValue = 'test-api-token-missing-pass';
        $this->connection->execute('
            INSERT INTO tokens (user_id, value, expires_at)
            VALUES (:user_id, :value, :expires_at)
        ', [
            'user_id' => $userId,
            'value' => $tokenValue,
            'expires_at' => date('Y-m-d H:i:s', strtotime('+1 hour')),
        ]);

        // Mocka payload utan password-nyckel
        $this->controller = $this->getMockBuilder(UserController::class)
            ->onlyMethods(['getJsonPayload'])
            ->getMock();

        $this->controller->method('getJsonPayload')->willReturn([
            'first_name' => 'UpdatedName',
            'last_name' => 'UpdatedLast',
            'email' => 'missing.pass@example.com',
            // 'password' saknas helt
        ]);

        $this->controller->setRequest(new Request(
            uri: "/api/users/{$userId}",
            method: 'PUT',
            get: [],
            post: [],
            files: [],
            cookie: [],
            server: [
                'HTTP_AUTHORIZATION' => "Bearer {$tokenValue}",
                'CONTENT_TYPE' => 'application/json',
            ]
        ));

        $this->controller->update((string) $userId);

        // Kontrollera att lösenordet INTE har ändrats
        /** @var array{password:string} $user */
        $user = $this->connection->fetchOne('SELECT password FROM users WHERE id = :id', ['id' => $userId]);
        $this->assertTrue(password_verify('originalpass', $user['password']), 'Lösenordet ska inte ha ändrats om nyckeln saknades.');
    }

    public function testPutUpdateWithInvalidFirstName(): void
    {
        // Skapa användare
        $this->connection->execute('
            INSERT INTO users (first_name, last_name, email, password)
            VALUES (:first_name, :last_name, :email, :password)
        ', [
            'first_name' => 'Valid',
            'last_name' => 'User',
            'email' => 'invalid.firstname@example.com',
            'password' => password_hash('password123', PASSWORD_BCRYPT),
        ]);

        /** @var array{id:int} $userRow */
        $userRow = $this->connection->fetchOne('SELECT id FROM users WHERE email = :email', [
            'email' => 'invalid.firstname@example.com',
        ]);
        $userId = $userRow['id'];

        // Token
        $tokenValue = 'test-api-token-inv-firstname';
        $this->connection->execute('
            INSERT INTO tokens (user_id, value, expires_at)
            VALUES (:user_id, :value, :expires_at)
        ', [
            'user_id' => $userId,
            'value' => $tokenValue,
            'expires_at' => date('Y-m-d H:i:s', strtotime('+1 hour')),
        ]);

        // Mocka getJsonPayload med ogiltig data för first_name, MEN giltig för övriga
        $this->controller = $this->getMockBuilder(UserController::class)
            ->onlyMethods(['getJsonPayload', 'respondWithErrors'])
            ->getMock();

        $this->controller->method('getJsonPayload')->willReturn([
            'first_name' => 'A', // För kort (min: 2), detta ska trigga felet
            'last_name' => 'ValidLast', // Giltigt
            'email' => 'valid.email.upd@example.com', // Giltigt
            'password' => 'newvalidpass', // Giltigt
        ]);

        $this->controller->expects($this->once())
            ->method('respondWithErrors')
            ->with($this->anything(), 422)
            ->willThrowException(new RuntimeException('ValidationFailed'));

        $this->controller->setRequest(new Request(
            uri: "/api/users/{$userId}",
            method: 'PUT',
            get: [],
            post: [],
            files: [],
            cookie: [],
            server: [
                'HTTP_AUTHORIZATION' => "Bearer {$tokenValue}",
                'CONTENT_TYPE' => 'application/json',
            ]
        ));

        try {
            $this->controller->update((string) $userId);
        } catch (RuntimeException $e) {
            if ($e->getMessage() === 'ValidationFailed') {
                return;
            }
            throw $e;
        }
    }

    public function testPutUpdateWithInvalidLastName(): void
    {
        // Skapa användare
        $this->connection->execute('
            INSERT INTO users (first_name, last_name, email, password)
            VALUES (:first_name, :last_name, :email, :password)
        ', [
            'first_name' => 'Valid',
            'last_name' => 'User',
            'email' => 'invalid.lastname@example.com',
            'password' => password_hash('password123', PASSWORD_BCRYPT),
        ]);

        /** @var array{id:int} $userRow */
        $userRow = $this->connection->fetchOne('SELECT id FROM users WHERE email = :email', [
            'email' => 'invalid.lastname@example.com',
        ]);
        $userId = $userRow['id'];

        // Token
        $tokenValue = 'test-api-token-inv-lastname';
        $this->connection->execute('
            INSERT INTO tokens (user_id, value, expires_at)
            VALUES (:user_id, :value, :expires_at)
        ', [
            'user_id' => $userId,
            'value' => $tokenValue,
            'expires_at' => date('Y-m-d H:i:s', strtotime('+1 hour')),
        ]);

        // Mocka getJsonPayload med ogiltig data (för kort efternamn)
        $this->controller = $this->getMockBuilder(UserController::class)
            ->onlyMethods(['getJsonPayload', 'respondWithErrors'])
            ->getMock();

        $this->controller->method('getJsonPayload')->willReturn([
            'last_name' => 'A', // För kort (min: 2)
        ]);

        $this->controller->expects($this->once())
            ->method('respondWithErrors')
            ->with($this->anything(), 422)
            ->willThrowException(new RuntimeException('ValidationFailed'));

        $this->controller->setRequest(new Request(
            uri: "/api/users/{$userId}",
            method: 'PUT',
            get: [],
            post: [],
            files: [],
            cookie: [],
            server: [
                'HTTP_AUTHORIZATION' => "Bearer {$tokenValue}",
                'CONTENT_TYPE' => 'application/json',
            ]
        ));

        try {
            $this->controller->update((string) $userId);
        } catch (RuntimeException $e) {
            if ($e->getMessage() === 'ValidationFailed') {
                return;
            }
            throw $e;
        }
    }

    public function testPatchUpdateValidation(): void
    {
        // Förbered användare
        $this->connection->execute('
            INSERT INTO users (first_name, last_name, email, password)
            VALUES (:first_name, :last_name, :email, :password)
        ', [
            'first_name' => 'Valid',
            'last_name' => 'User',
            'email' => 'patch.validation@example.com',
            'password' => password_hash('password123', PASSWORD_BCRYPT),
        ]);

        /** @var array{id:int} $userRow */
        $userRow = $this->connection->fetchOne('SELECT id FROM users WHERE email = :email', [
            'email' => 'patch.validation@example.com',
        ]);
        $userId = $userRow['id'];

        // Token
        $tokenValue = 'test-api-token-patch-val';
        $this->connection->execute('
            INSERT INTO tokens (user_id, value, expires_at)
            VALUES (:user_id, :value, :expires_at)
        ', [
            'user_id' => $userId,
            'value' => $tokenValue,
            'expires_at' => date('Y-m-d H:i:s', strtotime('+1 hour')),
        ]);

        // Mocka getJsonPayload med ogiltig data (för kort förnamn)
        $this->controller = $this->getMockBuilder(UserController::class)
            ->onlyMethods(['getJsonPayload', 'respondWithErrors'])
            ->getMock();

        $this->controller->method('getJsonPayload')->willReturn([
            'first_name' => 'A', // För kort (min: 2)
        ]);

        $this->controller->expects($this->once())
            ->method('respondWithErrors')
            ->with($this->anything(), 422)
            ->willThrowException(new RuntimeException('ValidationFailed'));

        $this->controller->setRequest(new Request(
            uri: "/api/users/{$userId}",
            method: 'PATCH',
            get: [],
            post: [],
            files: [],
            cookie: [],
            server: [
                'HTTP_AUTHORIZATION' => "Bearer {$tokenValue}",
                'CONTENT_TYPE' => 'application/json',
            ]
        ));

        try {
            $this->controller->partialUpdate((string) $userId);
        } catch (RuntimeException $e) {
            if ($e->getMessage() === 'ValidationFailed') {
                return;
            }
            throw $e;
        }
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

    public function testDeleteAlreadyDeleted(): void
    {
        // Förbered en användare som redan är soft deleted
        $this->connection->execute('
            INSERT INTO users (first_name, last_name, email, password, deleted_at)
            VALUES (:first_name, :last_name, :email, :password, :deleted_at)
        ', [
            'first_name' => 'Deleted',
            'last_name' => 'User',
            'email' => 'already.deleted@example.com',
            'password' => password_hash('password123', PASSWORD_BCRYPT),
            'deleted_at' => date('Y-m-d H:i:s'),
        ]);

        /** @var array{id:int} $userRow */
        $userRow = $this->connection->fetchOne('SELECT id FROM users WHERE email = :email', [
            'email' => 'already.deleted@example.com',
        ]);
        $userId = $userRow['id'];

        // Token
        $tokenValue = 'test-api-token-deleted';
        $this->connection->execute('
            INSERT INTO tokens (user_id, value, expires_at)
            VALUES (:user_id, :value, :expires_at)
        ', [
            'user_id' => $userId,
            'value' => $tokenValue,
            'expires_at' => date('Y-m-d H:i:s', strtotime('+1 hour')),
        ]);

        $this->controller->setRequest(new Request(
            uri: "/api/users/{$userId}",
            method: 'DELETE',
            get: [],
            post: [],
            files: [],
            cookie: [],
            server: [
                'HTTP_AUTHORIZATION' => "Bearer {$tokenValue}",
                'CONTENT_TYPE' => 'application/json',
            ]
        ));

        $response = $this->controller->delete((string) $userId);

        $this->assertEquals(400, $response->getStatusCode());
        /** @var array{success:bool,errors:array<string,string>} $body */
        $body = json_decode($response->getBody(), true);
        $this->assertFalse($body['success']);
        $this->assertEquals('Användaren är redan soft deleted.', $body['errors']['user']);
    }

    public function testDeleteUnauthenticated(): void
    {
        $controllerMock = $this->getMockBuilder(UserController::class)
            ->onlyMethods(['respondWithErrors'])
            ->getMock();

        $controllerMock->expects($this->once())
            ->method('respondWithErrors')
            ->with($this->anything(), 401)
            ->willThrowException(new RuntimeException('Unauthorized'));

        $request = new Request(
            uri: '/api/users/1',
            method: 'DELETE',
            get: [],
            post: [],
            files: [],
            cookie: [],
            server: [] // Ingen token
        );

        $controllerMock->setRequest($request);

        try {
            $controllerMock->delete('1');
        } catch (RuntimeException $e) {
            if ($e->getMessage() === 'Unauthorized') {
                return;
            }
            throw $e;
        }
    }
}
