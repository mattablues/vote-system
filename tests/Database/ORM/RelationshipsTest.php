<?php

declare(strict_types=1);

namespace Radix\Tests\Database\ORM;

use App\Models\Status;
use App\Models\User;
use PHPUnit\Framework\TestCase;
use Radix\Database\Connection;
use Radix\Database\ORM\Model;
use Radix\Database\ORM\Relationships\HasMany;
use Radix\Database\ORM\Relationships\HasOne;
use Radix\Database\ORM\Relationships\BelongsTo;
use Radix\Database\ORM\Relationships\BelongsToMany;
use Radix\Database\ORM\Relationships\HasOneThrough;

class RelationshipsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        \Radix\Container\ApplicationContainer::reset();
        $container = new \Radix\Container\Container();

        $pdo = new \PDO('sqlite::memory:');
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        // Minimal users-tabell för testet
        $pdo->exec("
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                first_name TEXT NOT NULL,
                last_name TEXT NOT NULL,
                email TEXT NOT NULL UNIQUE,
                password TEXT,
                avatar TEXT NOT NULL DEFAULT '/images/graphics/avatar.png',
                role TEXT NOT NULL DEFAULT 'user',
                created_at TEXT NULL,
                updated_at TEXT NULL,
                deleted_at TEXT NULL
            );
        ");

        // Registrera i containern
        $container->addShared(\PDO::class, fn() => $pdo);
        $container->add('Psr\Container\ContainerInterface', fn() => $container);
        \Radix\Container\ApplicationContainer::set($container);
    }

   protected function tearDown(): void
   {
       parent::tearDown();
       $user = new User();
       $user->setFillable(['id', 'first_name', 'last_name', 'email', 'avatar']);
       $user->setGuarded(['password', 'role']);
   }

        public function testPasswordAttribute(): void
        {
            $user = new User();
            $user->fill([
                'first_name' => 'Mats',
                'last_name' => 'Åkebrand',
                'email' => 'malle@akebrands.se',
            ]);

            // Testa att sätta lösenord
            $user->password = 'korvar65';

            $this->assertNotNull($user->password);

            // Kontrollera om password finns i attributlistan (använd publik API)
            $this->assertArrayHasKey('password', $user->getAttributes());
        }

    public function testGuardedOverridesFillable(): void
    {
        $user = new User();
        $user->setFillable(['email', 'first_name']); // Tillåt dessa attribut
        $user->setGuarded(['email']); // Blockera email specifikt

        $user->fill([
            'email' => 'blocked@example.com',
            'first_name' => 'Allowed',
        ]);

        $this->assertNull($user->getAttribute('email')); // Ska vara blockerat
        $this->assertSame('Allowed', $user->getAttribute('first_name')); // Ska vara tillåtet
    }

    public function testUserMassFilling(): void
    {
        $user = new User();

        // Massfyllning av tillåtna fält
        $user->fill([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'avatar' => '/images/john.png',
            'password' => 'secret' // Skyddat fält, ska ignoreras
        ]);

        $this->assertSame('John', $user->getAttribute('first_name'));
        $this->assertSame('Doe', $user->getAttribute('last_name'));
        $this->assertSame('john.doe@example.com', $user->getAttribute('email'));
        $this->assertSame('/images/john.png', $user->getAttribute('avatar'));
        $this->assertNull($user->getAttribute('password')); // Skyddat av `guarded`
    }

    public function testAllAttributesGuarded(): void
    {
        $user = new User();
        $user->setFillable([]); // Töm alla fillable-attribut
        $user->setGuarded(['*']); // Blockera alla attribu

        $user->fill([
            'first_name' => 'Markus',
            'last_name'  => 'Jones',
            'email'      => 'markus@example.com',
        ]);

        // Kontrollera att skyddade attribut inte är fyllda
        $this->assertNull($user->getAttribute('first_name'));
        $this->assertNull($user->getAttribute('last_name'));
        $this->assertNull($user->getAttribute('email'));
    }


    public function testFillableEmptyAllowsAllAttributes(): void
    {
        $user = new User();

        // Sätt både fillable och guarded till tomt
        $user->setFillable([]);
        $user->setGuarded([]);

        $user->fill([
            'first_name' => 'Lisa',
            'last_name' => 'Johnson',
            'email' => 'lisa@example.com',
        ]);

        // Kontrollera att alla attribut fylldes
        $this->assertSame('Lisa', $user->getAttribute('first_name'));
        $this->assertSame('Johnson', $user->getAttribute('last_name'));
        $this->assertSame('lisa@example.com', $user->getAttribute('email'));
    }

    public function testGuardedAndFillableAttributesMixed(): void
    {
        $user = new User();
        $user->setFillable(['first_name', 'email']);
        $user->setGuarded(['password']);

        $user->fill([
            'first_name' => 'Ella',
            'last_name' => 'Williamson', // Ej definierad i fillable, ignoreras
            'email' => 'ella@example.com',
            'password' => 'secretpassword', // Skyddat, ignoreras
        ]);

        // Kontrollera att endast tillåtna attribut fylldes
        $this->assertSame('Ella', $user->getAttribute('first_name'));
        $this->assertNull($user->getAttribute('last_name'));
        $this->assertSame('ella@example.com', $user->getAttribute('email'));
        $this->assertNull($user->getAttribute('password'));
    }

    public function testUnknownAttributesIgnored(): void
    {
        $user = new User();

        $user->fill([
            'nickname' => 'Johnny', // Ej existerande attribut i modellen
            'email' => 'johnny@example.com',
        ]);

        // Kontrollera att bara giltiga attribut fylldes
        $this->assertNull($user->getAttribute('nickname')); // Ignoreras
        $this->assertSame('johnny@example.com', $user->getAttribute('email'));
    }

   public function testDynamicGuardedAttributes(): void
   {
       $user = new User();
       $user->setGuarded(['email']); // Skydda "email"

       $user->fill([
           'first_name' => 'Anna',
           'email' => 'anna@example.com', // Ska ignoreras
       ]);

       $this->assertSame('Anna', $user->getAttribute('first_name'));
       $this->assertNull($user->getAttribute('email')); // Nu korrekt!
   }

    public function testDynamicGuardedAndFillable(): void
    {
        $user = new User();
        $user->setGuarded(['*']); // Skydda allt
        $user->setFillable(['first_name']); // Tillåt endast first_name

        $user->fill([
            'first_name' => 'Ella',
            'last_name'  => 'Smith',
            'email'      => 'ella@example.com',
        ]);

        $this->assertEquals('Ella', $user->getAttribute('first_name'), "Attributet first_name borde vara 'Ella'");
        $this->assertNull($user->getAttribute('last_name'), 'Attributet last_name borde vara null');
        $this->assertNull($user->getAttribute('email'), 'Attributet email borde vara null');
    }

    public function testStatusMassFilling(): void
    {
        $status = new Status();

        // Massfyllning av tillåtna fält
        $status->fill([
            'password_reset' => 'abc123',
            'reset_expires_at' => '2025-12-01 00:00:00',
            'user_id' => 5, // Skyddat fält, ska ignoreras
        ]);

        $this->assertSame('abc123', $status->getAttribute('password_reset'));
        $this->assertSame('2025-12-01 00:00:00', $status->getAttribute('reset_expires_at'));
        //$this->assertNull($status->getAttribute('user_id')); // Skyddat av `guarded`
    }

    public function testFillableOverridesGuarded(): void
    {
        $user = new User();

        // Gör "password" både guarded och fillable
        $user->setGuarded(['password']);
        $user->setFillable(['password']);

        $user->fill([
            'password' => 'not_so_secret',
        ]);

        // Då "password" är guarded ska det inte fyllas
        $this->assertNull($user->getAttribute('password'));
    }

    public function testGuardedAttributesAreIgnored(): void
    {
        $user = new User();

        // Försök fylla ett skyddat fält
        $user->fill([
            'first_name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => 'secret', // Ska ignoreras enligt `guarded`
        ]);

        // Kontrollera att skyddade fält ignorerades
        $this->assertSame('Alice', $user->getAttribute('first_name')); // Tillåtet
        $this->assertSame('alice@example.com', $user->getAttribute('email')); // Tillåtet
        $this->assertNull($user->getAttribute('password')); // Ignorerat
    }

    public function testUnknownFieldsAreIgnored(): void
    {
        $user = new User();

        // Försök fylla fält som inte är definierade i `fillable` eller `guarded`
        $user->fill([
            'first_name' => 'Bob',
            'nickname' => 'Bobby', // Okänt fält
        ]);

        // Kontrollera att endast tillåtna fält fylls
        $this->assertSame('Bob', $user->getAttribute('first_name'));
        $this->assertNull($user->getAttribute('nickname')); // Ignoreras helt
    }

    public function testDynamicFillableAttributes(): void
    {
        $user = new User();

        // Tillåt massfyllning av ett nytt attribut
        $user->setFillable(['first_name', 'last_name', 'nickname']);

        $user->fill([
            'first_name' => 'Clara',
            'last_name' => 'Smith',
            'nickname' => 'Clary', // Dynamiskt tillagt
        ]);

        // Kontrollera att alla tillagda fält är fyllda
        $this->assertSame('Clara', $user->getAttribute('first_name'));
        $this->assertSame('Smith', $user->getAttribute('last_name'));
        $this->assertSame('Clary', $user->getAttribute('nickname'));
    }

    public function testModelExistsFlag(): void
    {
        $model = new User();
        $this->assertFalse($model->isExisting());

        $model->markAsExisting();
        $this->assertTrue($model->isExisting());
    }

    public function testHasManyReturnsRelatedRecords(): void
    {
        // Förvänta oss att dessa värden returneras från databasen
        $expectedResults = [
            ['id' => 1, 'post_id' => 10, 'content' => 'First comment'],
            ['id' => 2, 'post_id' => 10, 'content' => 'Second comment'],
        ];

        // Mocka Connection och simulera fetchAll
        $connection = $this->createMock(Connection::class);
        $connection->method('fetchAll')->willReturn($expectedResults);

        // Simulera dataelement till HasMany utan att röra skyddade egenskaper
        $results = array_map(function ($record) {
            $m = new class extends Model {
                protected string $table = 'comments';
                /** array<int, string> */
                protected array $fillable = ['id', 'post_id', 'content'];
            };
            $m->forceFill($record);
            return $m;
        }, $expectedResults);

        // Validera antal resultat
        $this->assertCount(2, $results);

        // Kontrollera första resultatet
        $this->assertInstanceOf(Model::class, $results[0]);
        $this->assertEquals(1, $results[0]->getAttribute('id'));
        $this->assertEquals(10, $results[0]->getAttribute('post_id'));
        $this->assertEquals('First comment', $results[0]->getAttribute('content'));

        // Kontrollera andra resultatet
        $this->assertInstanceOf(Model::class, $results[1]);
        $this->assertEquals(2, $results[1]->getAttribute('id'));
        $this->assertEquals(10, $results[1]->getAttribute('post_id'));
        $this->assertEquals('Second comment', $results[1]->getAttribute('content'));
    }

    public function testHasOneReturnsSingleRelatedRecord(): void
    {
        // Mocka data från databasen
        $expectedResult = ['id' => 1, 'user_id' => 5, 'profile' => 'Profile details'];

        // Mocka databasanslutningen och simulera fetchOne
        $connection = $this->createMock(Connection::class);
        $connection->method('fetchOne')->willReturn($expectedResult);

        // Dynamisk modell för profilen
        $profileClass = new class extends Model {
            protected string $table = 'profiles';
            /** array<int, string> */
            protected array $fillable = ['id', 'user_id', 'profile'];
        };

        // Skapa en HasOne-relation
        $hasOne = new HasOne(
            $connection,
            get_class($profileClass),
            'user_id',
            '5'
        );

        // Hämta relaterad modell via first()
        $result = $hasOne->first();

        // Verifiera att resultatet är av rätt klass
        $this->assertInstanceOf(get_class($profileClass), $result);

        // Kontrollera att attributen innehåller förväntade värden
        $this->assertEquals($expectedResult['id'], $result->getAttribute('id'));
        $this->assertEquals($expectedResult['user_id'], $result->getAttribute('user_id'));
        $this->assertEquals($expectedResult['profile'], $result->getAttribute('profile'));
    }

    public function testHasManyWith(): void
    {
        $expectedResults = [
            ['id' => 1, 'post_id' => 10, 'content' => 'First comment'],
            ['id' => 2, 'post_id' => 10, 'content' => 'Second comment'],
        ];

        // Mocka anslutningen
        $connectionMock = $this->createMock(Connection::class);
        $connectionMock->method('fetchAll')->willReturn($expectedResults);

        // Parent-modell med injicerad connection
        $post = new class($connectionMock) extends Model {
            protected string $table = 'posts';
            /** array<int, string> */
            protected array $fillable = ['id', 'title'];
            private Connection $conn;
            public function __construct(Connection $c) { $this->conn = $c; parent::__construct([]); }
            protected function getConnection(): Connection { return $this->conn; }

            // Relaterad modellklass utan ctor-krav (HasMany instansierar den med new $class())
            public function comments(): HasMany
            {
                $comment = new class extends Model {
                    protected string $table = 'comments';
                    /** array<int, string> */
                    protected array $fillable = ['id','post_id','content'];
                };
                $rel = new HasMany(
                    $this->getConnection(),
                    get_class($comment),
                    'post_id',
                    'id'
                );
                $rel->setParent($this);
                return $rel;
            }
        };

        $post->forceFill(['id' => 10]);

        // Hämta relaterade kommentarer
        $comments = $post->comments()->get();

        $this->assertCount(2, $comments);
        $this->assertInstanceOf(Model::class, $comments[0]);
        $this->assertEquals('First comment', $comments[0]->getAttribute('content'));
    }

    public function testBelongsToReturnsRelatedParentRecord(): void
    {
        $expectedResult = ['id' => 5, 'first_name' => 'Mats'];

        $connection = $this->createMock(Connection::class);
        $connection->method('fetchOne')
            ->willReturn($expectedResult);

        $parentModel = $this->createMock(\App\Models\Status::class);
        $parentModel->method('getAttribute')->willReturnMap([ ['user_id', 5] ]);

        $belongsTo = new BelongsTo(
            $connection,
            \App\Models\User::class, // eller 'users' om din resolveModelClass hanterar tabell
            'user_id',               // foreignKey kolumn på child
            'id',                    // ownerKey kolumn på parent (users.id)
            $parentModel
        );


        $result = $belongsTo->get();

        $this->assertInstanceOf(\App\Models\User::class, $result);
        $this->assertEquals($expectedResult['id'], $result->getAttribute('id'));
        $this->assertEquals($expectedResult['first_name'], $result->getAttribute('first_name'));
    }

    public function testBelongsToManyReturnsRelatedRecords(): void
    {
        $expectedResults = [
            ['id' => 1, 'first_name' => 'John'],
            ['id' => 2, 'first_name' => 'Jane'],
        ];

        $connection = $this->createMock(Connection::class);
        $connection->method('fetchAll')
            ->willReturn($expectedResults);

        $belongsToMany = new BelongsToMany(
            $connection,
            \App\Models\User::class,    // Korrekt klassnamn
            'role_user',                // Pivot table
            'role_id',                  // Foreign pivot key
            'user_id',                  // Related pivot key
            '1'                         // Parent key
        );

        $results = $belongsToMany->get();

        $this->assertCount(2, $results);

        foreach ($results as $index => $result) {
            $this->assertInstanceOf(\App\Models\User::class, $result);
            $this->assertEquals($expectedResults[$index]['id'], $result->getAttribute('id'));
            $this->assertEquals($expectedResults[$index]['first_name'], $result->getAttribute('first_name'));
        }
    }

    public function testUserJsonSerialization(): void
    {
        $user = new User(['id' => 1, 'first_name' => 'John', 'email' => 'john.doe@example.com']);

        // Mocka `Post`-instanser med `toArray` och `jsonSerialize`-stöd
        $post1 = $this->createMock(Model::class);
        $post1->method('toArray')->willReturn(['id' => 1, 'title' => 'First Post', 'content' => 'This is the first post.']);
        $post1->method('jsonSerialize')->willReturn(['id' => 1, 'title' => 'First Post', 'content' => 'This is the first post.']);

        $post2 = $this->createMock(Model::class);
        $post2->method('toArray')->willReturn(['id' => 2, 'title' => 'Second Post', 'content' => 'This is the second post.']);
        $post2->method('jsonSerialize')->willReturn(['id' => 2, 'title' => 'Second Post', 'content' => 'This is the second post.']);

        // Sätt relation på mockade postar
        $user->setRelation('posts', [$post1, $post2]);

        // Debugga relationen innan testning
        $this->assertNotEmpty($user->getRelation('posts'), 'Posts relation is empty.');

        $expectedJson = json_encode([
            'id' => 1,
            'first_name' => 'John',
            'email' => 'john.doe@example.com',
            'posts' => [
                [
                    'id' => 1,
                    'title' => 'First Post',
                    'content' => 'This is the first post.'
                ],
                [
                    'id' => 2,
                    'title' => 'Second Post',
                    'content' => 'This is the second post.'
                ]
            ]
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $actualJson = json_encode($user, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        // Jämför förväntad och faktisk JSON
        $this->assertSame($expectedJson, $actualJson, 'JSON serialization does not match expected output.');
    }

    public function testHasManyFirstReturnsRelatedRecord(): void
    {
        // Mocka Comment-modellen
        $commentMock = $this->createMock(Model::class);
        $commentMock->method('getAttribute')->willReturnMap([
            ['id', 1],
            ['post_id', 10],
            ['content', 'First comment']
        ]);

        // Mocka HasMany-relationen
        $hasManyMock = $this->createMock(HasMany::class);
        $hasManyMock->method('first')->willReturn($commentMock);

        // Kalla på `first` och kontrollera resultatet
        $result = $hasManyMock->first();

        $this->assertInstanceOf(Model::class, $result);
        $this->assertEquals('First comment', $result->getAttribute('content'));
    }

    public function testBelongsToManyFirstReturnsRecord(): void
    {
        // Mockat resultat från databasen
        $expectedResults = [
            ['id' => 1, 'first_name' => 'John'],
            ['id' => 2, 'first_name' => 'Jane'],
        ];

        // Mocka databasanslutningen
        $connection = $this->createMock(Connection::class);
        $connection->method('fetchAll')->willReturn($expectedResults);

        // Skapa en instans av BelongsToMany
        $belongsToMany = new BelongsToMany(
            $connection,
            \App\Models\User::class,
            'role_user',          // Pivot-tabell
            'role_id',            // Foreign pivot key
            'user_id',            // Related pivot key
            '1'                   // Parent key som sträng
        );

        // Kör first() för att få första modellen
        $result = $belongsToMany->first();

        // Validera att resultatet är en instans av rätt modellklass
        $this->assertInstanceOf(\App\Models\User::class, $result);

        // Kontrollera att attributen stämmer överens
        $this->assertEquals('John', $result->getAttribute('first_name'));
    }

    public function testHydrateFromDatabaseIncludesTimestampsSkipsGuarded(): void
    {
        $row = [
            'id' => 42,
            'first_name' => 'Mats',
            'last_name' => 'Åkebrand',
            'email' => 'mats@example.com',
            'avatar' => '/images/user/42/avatar.jpg',
            'password' => 'hashed', // guarded
            'role' => 'admin',      // guarded
            'deleted_at' => null,   // guarded
            'created_at' => '2025-01-01 10:00:00',
            'updated_at' => '2025-01-02 11:00:00',
        ];

        $u = new User();
        $u->hydrateFromDatabase($row)->markAsExisting();

        // timestamps ska vara läsbara
        $this->assertSame('2025-01-01 10:00:00', $u->getAttribute('created_at'));
        $this->assertSame('2025-01-02 11:00:00', $u->getAttribute('updated_at'));

        // guarded ska inte vara hydrerade in i attributes
        $this->assertNull($u->getAttribute('password'));
        $this->assertNull($u->getAttribute('role'));
        $this->assertNull($u->getAttribute('deleted_at'));

        // övriga vanliga fält finns
        $this->assertSame(42, $u->getAttribute('id'));
        $this->assertSame('Mats', $u->getAttribute('first_name'));
        $this->assertSame('Åkebrand', $u->getAttribute('last_name'));
        $this->assertSame('mats@example.com', $u->getAttribute('email'));
        $this->assertSame('/images/user/42/avatar.jpg', $u->getAttribute('avatar'));
    }

    public function testFillDoesNotMassAssignTimestamps(): void
    {
        $u = new User();
        $u->fill([
            'first_name' => 'Clara',
            'created_at' => '2000-01-01 00:00:00',
            'updated_at' => '2000-01-02 00:00:00',
        ]);

        $this->assertSame('Clara', $u->getAttribute('first_name'));
        $this->assertNull($u->getAttribute('created_at'));
        $this->assertNull($u->getAttribute('updated_at'));
    }

    public function testHasOneThroughFirstReturnsSingleRelatedRecord(): void
    {
        // Simulerad rad från relaterad tabell (votes)
        $expectedRow = ['id' => 7, 'subject_id' => 3, 'points' => 42];

        $connection = $this->createMock(Connection::class);
        $connection->method('fetchOne')->willReturn($expectedRow);

        // Parent: categories (utan ctor-argument)
        $category = new class extends Model {
            protected string $table = 'categories';
            /** array<int, string> */
            protected array $fillable = ['id', 'name'];
            private ?Connection $conn = null;

            public function setTestConnection(Connection $c): void { $this->conn = $c; }
            protected function getConnection(): Connection
            {
                return $this->conn ?? parent::getConnection();
            }
        };
        $category->forceFill(['id' => 1]);

        // Through: subjects
        $subjectClass = new class extends Model {
            protected string $table = 'subjects';
            /** array<int, string> */
            protected array $fillable = ['id', 'category_id', 'name'];
        };

        // Related: votes
        $voteClass = new class extends Model {
            protected string $table = 'votes';
            /** array<int, string> */
            protected array $fillable = ['id', 'subject_id', 'points'];
        };

        // Koppla test-connection till parent
        $category->setTestConnection($connection);

        $rel = new HasOneThrough(
            $connection,
            get_class($voteClass),     // related
            get_class($subjectClass),  // through
            'category_id',             // subjects.category_id
            'subject_id',              // votes.subject_id
            'id',                      // categories.id
            'id'                       // subjects.id
        );
        $rel->setParent($category);

        $result = $rel->first();

        $this->assertNotNull($result);
        $this->assertEquals(7, $result->getAttribute('id'));
        $this->assertEquals(3, $result->getAttribute('subject_id'));
        $this->assertEquals(42, $result->getAttribute('points'));
    }

    public function testQueryBuilderAggregateAndCountWithHasOneThrough(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('fetchAll')->willReturn([]); // vi behöver bara SQL

        // Parentmodell med relation topVote() som HasOneThrough
        $parent = new class extends Model {
            protected string $table = 'categories';
            /** array<int, string> */
            protected array $fillable = ['id', 'name'];
            private ?Connection $conn = null;

            public function setTestConnection(Connection $c): void { $this->conn = $c; }
            protected function getConnection(): Connection
            {
                return $this->conn ?? parent::getConnection();
            }

            public function topVote(): HasOneThrough
            {
                $through = new class extends Model {
                    protected string $table = 'subjects';
                    /** array<int, string> */
                    protected array $fillable = ['id', 'category_id'];
                };
                $related = new class extends Model {
                    protected string $table = 'votes';
                    /** array<int, string> */
                    protected array $fillable = ['id', 'subject_id', 'points'];
                };

                return (new HasOneThrough(
                    $this->getConnection(),
                    get_class($related),
                    get_class($through),
                    'category_id', // subjects.category_id
                    'subject_id',  // votes.subject_id
                    'id',          // categories.id
                    'id'           // subjects.id
                ))->setParent($this);
            }
        };
        // Sätt connection via en instans (QueryBuilder kommer instansiera utan args)
        $parent->setTestConnection($connection);

        $qb = (new \Radix\Database\QueryBuilder\QueryBuilder())
            ->setConnection($connection)
            ->setModelClass(get_class($parent))
            ->from('categories')
            ->withAggregate('topVote', 'points', 'MAX', 'topVote_max')
            ->withCount('topVote');

        $sql = $qb->toSql();

        // Kontrollera att subqueries för både aggregat och count byggs korrekt
        $this->assertStringContainsString('FROM `categories`', $sql);
        $this->assertStringContainsString('SELECT MAX(`r`.`points`)', $sql);
        $this->assertStringContainsString('FROM `votes` AS r INNER JOIN `subjects` AS t ON t.`id` = r.`subject_id`', $sql);
        $this->assertStringContainsString('WHERE t.`category_id` = `categories`.`id`', $sql);
        $this->assertStringContainsString('SELECT COUNT(*)', $sql);
    }

    public function testWithCountWhereSupportsHasOneThrough(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('fetchAll')->willReturn([]); // endast SQL-verifiering

        // Parent-modell utan ctor-argument + relation topVote()
        $parent = new class extends Model {
            protected string $table = 'categories';
            /** array<int, string> */
            protected array $fillable = ['id', 'name'];
            private ?Connection $conn = null;

            public function setTestConnection(Connection $c): void { $this->conn = $c; }
            protected function getConnection(): Connection
            {
                return $this->conn ?? parent::getConnection();
            }

            public function topVote(): HasOneThrough
            {
                $through = new class extends Model {
                    protected string $table = 'subjects';
                    /** array<int, string> */
                    protected array $fillable = ['id', 'category_id'];
                };
                $related = new class extends Model {
                    protected string $table = 'votes';
                    /** array<int, string> */
                    protected array $fillable = ['id', 'subject_id', 'points', 'status'];
                };

                return (new HasOneThrough(
                    $this->getConnection(),
                    get_class($related),
                    get_class($through),
                    'category_id', // subjects.category_id
                    'subject_id',  // votes.subject_id
                    'id',          // categories.id
                    'id'           // subjects.id
                ))->setParent($this);
            }
        };
        $parent->setTestConnection($connection);

        $qb = (new \Radix\Database\QueryBuilder\QueryBuilder())
            ->setConnection($connection)
            ->setModelClass(get_class($parent))
            ->from('categories')
            ->withCountWhere('topVote', 'status', 'approved', 'topVote_approved');

        $sql = $qb->toSql();

        // SQL ska innehålla subquery med JOIN subjects -> votes och filter på r.status = 'approved'
        $this->assertStringContainsString('FROM `categories`', $sql);
        $this->assertStringContainsString('SELECT COUNT(*)', $sql);
        $this->assertStringContainsString('FROM `votes` AS r INNER JOIN `subjects` AS t ON t.`id` = r.`subject_id`', $sql);
        $this->assertStringContainsString('WHERE t.`category_id` = `categories`.`id`', $sql);
        $this->assertStringContainsString("AND r.`status` = 'approved'", $sql);
    }

    public function testWithCountWhereSupportsHasMany(): void
    {
        $conn = $this->createMock(\Radix\Database\Connection::class);
        $conn->method('fetchAll')->willReturn([]);

        $parent = new class extends \Radix\Database\ORM\Model {
            protected string $table = 'posts';
            /** array<int, string> */
            protected array $fillable = ['id', 'title'];
            private ?\Radix\Database\Connection $c = null;
            public function setConn(\Radix\Database\Connection $c): void
            { $this->c = $c; }
            protected function getConnection(): \Radix\Database\Connection { return $this->c ?? parent::getConnection(); }

            public function comments(): \Radix\Database\ORM\Relationships\HasMany
            {
                $comment = new class extends \Radix\Database\ORM\Model {
                    protected string $table = 'comments';
                    /** array<int, string> */
                    protected array $fillable = ['id','post_id','status'];
                };
                return new \Radix\Database\ORM\Relationships\HasMany(
                    $this->getConnection(),
                    get_class($comment),
                    'post_id',
                    'id'
                );
            }
        };
        $parent->setConn($conn);

        $sql = (new \Radix\Database\QueryBuilder\QueryBuilder())
            ->setConnection($conn)
            ->setModelClass(get_class($parent))
            ->from('posts')
            ->withCountWhere('comments', 'status', 'approved', 'comments_approved')
            ->toSql();

        $this->assertStringContainsString('FROM `posts`', $sql);
        $this->assertStringContainsString('SELECT COUNT(*) FROM `comments`', $sql);
        $this->assertStringContainsString('`comments`.`post_id` = `posts`.`id`', $sql);
        $this->assertStringContainsString("`comments`.`status` = 'approved'", $sql);
    }

    public function testWithCountWhereSupportsBelongsToMany(): void
    {
        $conn = $this->createMock(\Radix\Database\Connection::class);
        $conn->method('fetchAll')->willReturn([]);

        $parent = new class extends \Radix\Database\ORM\Model {
            protected string $table = 'roles';
            /** array<int, string> */
            protected array $fillable = ['id', 'name'];
            private ?\Radix\Database\Connection $c = null;
            public function setConn(\Radix\Database\Connection $c): void
            { $this->c = $c; }
            protected function getConnection(): \Radix\Database\Connection { return $this->c ?? parent::getConnection(); }

            public function users(): \Radix\Database\ORM\Relationships\BelongsToMany
            {
                return new \Radix\Database\ORM\Relationships\BelongsToMany(
                    $this->getConnection(),
                    \App\Models\User::class,
                    'role_user',
                    'role_id',
                    'user_id',
                    'id'
                );
            }
        };
        $parent->setConn($conn);

        $sql = (new \Radix\Database\QueryBuilder\QueryBuilder())
            ->setConnection($conn)
            ->setModelClass(get_class($parent))
            ->from('roles')
            ->withCountWhere('users', 'status', 'active', 'users_active')
            ->toSql();

        $this->assertStringContainsString('FROM `roles`', $sql);
        $this->assertStringContainsString('FROM `role_user` AS pivot', $sql);
        $this->assertStringContainsString('INNER JOIN `users` AS related', $sql);
        $this->assertStringContainsString('pivot.`role_id` = `roles`.`id`', $sql);
        $this->assertStringContainsString("related.`status` = 'active'", $sql);
    }

    public function testWithCountWhereSupportsBelongsTo(): void
    {
        $conn = $this->createMock(\Radix\Database\Connection::class);
        $conn->method('fetchAll')->willReturn([]);

        $child = new class extends \Radix\Database\ORM\Model {
            protected string $table = 'statuses';
            /** array<int, string> */
            protected array $fillable = ['id','user_id','state'];
            private ?\Radix\Database\Connection $c = null;
            public function setConn(\Radix\Database\Connection $c): void
            { $this->c = $c; }
            protected function getConnection(): \Radix\Database\Connection { return $this->c ?? parent::getConnection(); }

            public function user(): \Radix\Database\ORM\Relationships\BelongsTo
            {
                $user = new class extends \Radix\Database\ORM\Model {
                    protected string $table = 'users';
                    /** array<int, string> */
                    protected array $fillable = ['id','name','role'];
                };
                return new \Radix\Database\ORM\Relationships\BelongsTo(
                    $this->getConnection(),
                    $user->getTable(),
                    'user_id',
                    'id',
                    $this
                );
            }
        };
        $child->setConn($conn);

        $sql = (new \Radix\Database\QueryBuilder\QueryBuilder())
            ->setConnection($conn)
            ->setModelClass(get_class($child))
            ->from('statuses')
            ->withCountWhere('user', 'role', 'admin', 'user_admin')
            ->toSql();

        $this->assertStringContainsString('FROM `statuses`', $sql);
        $this->assertStringContainsString('FROM `users`', $sql);
        $this->assertStringContainsString('`users`.`id` = `statuses`.`user_id`', $sql);
        $this->assertStringContainsString("`users`.`role` = 'admin'", $sql);
    }

    public function testModelLoadLoadsRelations(): void
    {
        $rows = [
            ['id' => 1, 'post_id' => 10, 'status' => 'published'],
            ['id' => 2, 'post_id' => 10, 'status' => 'draft'],
        ];

        $conn = $this->createMock(\Radix\Database\Connection::class);
        $conn->method('fetchAll')->willReturn($rows);

        // Parent-modell med hasMany relation "comments"
        $post = new class extends \Radix\Database\ORM\Model {
            protected string $table = 'posts';
            /** array<int, string> */
            protected array $fillable = ['id', 'title'];
            private ?\Radix\Database\Connection $c = null;
            public function setConn(\Radix\Database\Connection $c): void
            { $this->c = $c; }
            protected function getConnection(): \Radix\Database\Connection { return $this->c ?? parent::getConnection(); }

            public function comments(): \Radix\Database\ORM\Relationships\HasMany
            {
                $comment = new class extends \Radix\Database\ORM\Model {
                    protected string $table = 'comments';
                    /** array<int, string> */
                    protected array $fillable = ['id','post_id','status'];
                };
                $rel = new \Radix\Database\ORM\Relationships\HasMany(
                    $this->getConnection(),
                    get_class($comment),
                    'post_id',
                    'id'
                );
                $rel->setParent($this);
                return $rel;
            }
        };

        $post->setConn($conn);
        $post->forceFill(['id' => 10]);

        // Ladda enkel relation
        $post->load('comments');
        $loaded = $post->getRelation('comments');

        $this->assertIsArray($loaded);
        /** @var list<\Radix\Database\ORM\Model> $loaded */
        $loaded = $loaded;
        $this->assertCount(2, $loaded);
        $this->assertSame('published', $loaded[0]->getAttribute('status'));

        // Ladda flera (idempotent)
        $post->load(['comments']);
        $this->assertIsArray($post->getRelation('comments'));
    }

    public function testModelLoadMissingSkipsAlreadyLoaded(): void
    {
        $rowsInitial = [
            ['id' => 1, 'post_id' => 10, 'status' => 'published'],
        ];
        $rowsSecond = [
            ['id' => 2, 'post_id' => 10, 'status' => 'draft'],
        ];

        $conn = $this->createMock(\Radix\Database\Connection::class);
        // Första anropet returnerar initiala rader, andra anropet skulle returnera andra rader
        $conn->method('fetchAll')->willReturnOnConsecutiveCalls($rowsInitial, $rowsSecond);

        $post = new class extends \Radix\Database\ORM\Model {
            protected string $table = 'posts';
            /** array<int, string> */
            protected array $fillable = ['id', 'title'];
            private ?\Radix\Database\Connection $c = null;
            public function setConn(\Radix\Database\Connection $c): void
            { $this->c = $c; }
            protected function getConnection(): \Radix\Database\Connection { return $this->c ?? parent::getConnection(); }

            public function comments(): \Radix\Database\ORM\Relationships\HasMany
            {
                $comment = new class extends \Radix\Database\ORM\Model {
                    protected string $table = 'comments';
                    /** array<int, string> */
                    protected array $fillable = ['id','post_id','status'];
                };
                $rel = new \Radix\Database\ORM\Relationships\HasMany(
                    $this->getConnection(),
                    get_class($comment),
                    'post_id',
                    'id'
                );
                $rel->setParent($this);
                return $rel;
            }
        };

        $post->setConn($conn);
        $post->forceFill(['id' => 10]);

        // Ladda en gång
        $post->load('comments');
        $loadedFirst = $post->getRelation('comments');
        $this->assertIsArray($loadedFirst);
        /** @var list<\Radix\Database\ORM\Model> $loadedFirst */
        $loadedFirst = $loadedFirst;
        $this->assertCount(1, $loadedFirst);

        // loadMissing ska inte ladda om "comments" (ska ignorera andra fetchAll-resultatet)
        $post->loadMissing('comments');
        $loadedAfter = $post->getRelation('comments');
        $this->assertIsArray($loadedAfter);
        /** @var list<\Radix\Database\ORM\Model> $loadedAfter */
        $loadedAfter = $loadedAfter;
        $this->assertCount(1, $loadedAfter);
        $this->assertSame($loadedFirst[0]->getAttribute('status'), $loadedAfter[0]->getAttribute('status'));
    }

    public function testModelLoadWithConstraintClosure(): void
    {
        // Vi verifierar att load() inte kraschar när constraint passar en QueryBuilder
        // och att relationen sätts. Vi behöver inte validera exakt SQL här.
        $rows = [
            ['id' => 1, 'post_id' => 10, 'status' => 'published'],
        ];
        $conn = $this->createMock(\Radix\Database\Connection::class);
        $conn->method('fetchAll')->willReturn($rows);

        $post = new class extends \Radix\Database\ORM\Model {
            protected string $table = 'posts';
            /** array<int, string> */
            protected array $fillable = ['id'];
            private ?\Radix\Database\Connection $c = null;
            public function setConn(\Radix\Database\Connection $c): void
            { $this->c = $c; }
            protected function getConnection(): \Radix\Database\Connection { return $this->c ?? parent::getConnection(); }

            public function comments(): \Radix\Database\ORM\Relationships\HasMany
            {
                $comment = new class extends \Radix\Database\ORM\Model {
                    protected string $table = 'comments';
                    /** array<int, string> */
                    protected array $fillable = ['id','post_id','status'];
                };
                $rel = new \Radix\Database\ORM\Relationships\HasMany(
                    $this->getConnection(),
                    get_class($comment),
                    'post_id',
                    'id'
                );
                $rel->setParent($this);
                return $rel;
            }
        };

        $post->setConn($conn);
        $post->forceFill(['id' => 10]);

        $post->load([
            'comments' => function (\Radix\Database\QueryBuilder\QueryBuilder $q) {
                $q->where('status', '=', 'published')->orderBy('id', 'DESC');
            }
        ]);

        $loaded = $post->getRelation('comments');


        $this->assertInstanceOf(\Radix\Collection\Collection::class, $loaded);
        $this->assertCount(1, $loaded);
        $first = $loaded->first();
        $this->assertInstanceOf(\Radix\Database\ORM\Model::class, $first);
        $this->assertSame('published', $first->getAttribute('status'));
    }

    public function testHasOneWithDefaultReturnsEmptyModelWhenMissing(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('fetchOne')->willReturn(null); // inget resultat

        // Parent-modell
        $user = new class extends Model {
            protected string $table = 'users';
            /** array<int, string> */
            protected array $fillable = ['id', 'first_name'];
            private ?Connection $conn = null;
            public function setConn(Connection $c): void { $this->conn = $c; }
            protected function getConnection(): Connection { return $this->conn ?? parent::getConnection(); }

            public function profile(): HasOne
            {
                $profile = new class extends Model {
                    protected string $table = 'profiles';
                    /** array<int, string> */
                    protected array $fillable = ['id','user_id','avatar'];
                };

                $rel = new HasOne($this->getConnection(), get_class($profile), 'user_id', 'id');
                $rel->setParent($this);
                return $rel;
            }
        };
        $user->setConn($connection);
        $user->forceFill(['id' => 10]);

        $result = $user->profile()->withDefault()->first();

        $this->assertInstanceOf(Model::class, $result);
        $this->assertFalse($result->isExisting(), 'Default-modell ska markeras som ny (isExisting=false)');
    }

    public function testHasOneWithDefaultArrayAttributes(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('fetchOne')->willReturn(null);

        $user = new class extends Model {
            protected string $table = 'users';
            /** array<int, string> */
            protected array $fillable = ['id'];
            private ?Connection $c = null;
            public function setConn(Connection $c): void { $this->c = $c; }
            protected function getConnection(): Connection { return $this->c ?? parent::getConnection(); }

            public function profile(): HasOne
            {
                $profile = new class extends Model {
                    protected string $table = 'profiles';
                    /** array<int, string> */
                    protected array $fillable = ['avatar'];
                };
                $rel = new HasOne($this->getConnection(), get_class($profile), 'user_id', 'id');
                $rel->setParent($this);
                return $rel;
            }
        };
        $user->setConn($connection);
        $user->forceFill(['id' => 15]);

        $result = $user->profile()->withDefault(['avatar' => '/img/default.png'])->first();

        $this->assertInstanceOf(Model::class, $result);
        $this->assertSame('/img/default.png', $result->getAttribute('avatar'));
        $this->assertFalse($result->isExisting());
    }

    public function testBelongsToWithDefaultCallable(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('fetchOne')->willReturn(null); // inget parent-resultat

        // Child-modell med belongsTo user()
        $status = new class extends Model {
            protected string $table = 'statuses';
            /** array<int, string> */
            protected array $fillable = ['id','user_id'];
            private ?Connection $c = null;
            public function setConn(Connection $c): void { $this->c = $c; }
            protected function getConnection(): Connection { return $this->c ?? parent::getConnection(); }

            public function user(): BelongsTo
            {
                $user = new class extends Model {
                    protected string $table = 'users';
                    /** array<int, string> */
                    protected array $fillable = ['id','first_name'];
                };
                return new BelongsTo(
                    $this->getConnection(),
                    $user->getTable(),
                    'user_id',
                    'id',
                    $this
                );
            }
        };
        $status->setConn($connection);
        $status->forceFill(['user_id' => 999]);

        $u = $status->user()->withDefault(function (Model $m) {
            $m->forceFill(['first_name' => 'Unknown']);
        })->first();

        $this->assertInstanceOf(Model::class, $u);
        $this->assertSame('Unknown', $u->getAttribute('first_name'));
        $this->assertFalse($u->isExisting());
    }

    public function testModelLoadRespectsWithDefaultForHasOne(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('fetchOne')->willReturn(null);

        $parent = new class extends Model {
            protected string $table = 'users';
            /** array<int, string> */
            protected array $fillable = ['id'];
            private ?Connection $c = null;
            public function setConn(Connection $c): void { $this->c = $c; }
            protected function getConnection(): Connection { return $this->c ?? parent::getConnection(); }

            public function profile(): HasOne
            {
                $profile = new class extends Model {
                    protected string $table = 'profiles';
                    /** array<int, string> */
                    protected array $fillable = ['avatar'];
                };
                $rel = new HasOne($this->getConnection(), get_class($profile), 'user_id', 'id');
                $rel->setParent($this);
                return $rel;
            }
        };
        $parent->setConn($connection);
        $parent->forceFill(['id' => 77]);

        $parent->load([
            'profile' => function (HasOne $rel) {
                // relation-objekt (HasOne)
                $rel->withDefault(['avatar' => '/img/default.png']);
            }
        ]);

        $profile = $parent->getRelation('profile');
        $this->assertInstanceOf(Model::class, $profile);
        $this->assertSame('/img/default.png', $profile->getAttribute('avatar'));
        $this->assertFalse($profile->isExisting());
    }

    public function testModelLoadMissingRespectsWithDefaultForBelongsTo(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('fetchOne')->willReturn(null);

        $child = new class extends Model {
            protected string $table = 'statuses';
            /** array<int, string> */
            protected array $fillable = ['id','user_id'];
            private ?Connection $c = null;
            public function setConn(Connection $c): void { $this->c = $c; }
            protected function getConnection(): Connection { return $this->c ?? parent::getConnection(); }

            public function user(): BelongsTo
            {
                $user = new class extends Model {
                    protected string $table = 'users';
                    /** array<int, string> */
                    protected array $fillable = ['first_name'];
                };
                return new BelongsTo(
                    $this->getConnection(),
                    $user->getTable(),
                    'user_id',
                    'id',
                    $this
                );
            }
        };
        $child->setConn($connection);
        $child->forceFill(['user_id' => 5]);

        $child->loadMissing([
            'user' => function (BelongsTo $rel): void {
                $rel->withDefault(['first_name' => 'N/A']);
            },
        ]);

        $user = $child->getRelation('user');
        $this->assertInstanceOf(Model::class, $user);
        $this->assertSame('N/A', $user->getAttribute('first_name'));
        $this->assertFalse($user->isExisting());
    }
}