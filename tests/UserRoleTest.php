<?php

declare(strict_types=1);

namespace Radix\Tests;

use App\Models\User;
use InvalidArgumentException;
use PDO;
use PHPUnit\Framework\TestCase;
use Radix\Container\ApplicationContainer;
use Radix\Container\Container;
use Radix\Enums\Role;
use ReflectionClass;

class UserRoleTest extends TestCase
{
    private int $emailSeq = 0;

    protected function setUp(): void
    {
        parent::setUp();

        ApplicationContainer::reset();
        $container = new Container();

        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Skapa tabell (SQLite)
        $pdo->exec("
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                first_name TEXT NOT NULL,
                last_name TEXT NOT NULL,
                email TEXT NOT NULL UNIQUE,
                password TEXT NOT NULL,
                avatar TEXT NOT NULL DEFAULT '/images/graphics/avatar.png',
                role TEXT NOT NULL DEFAULT 'user',
                created_at TEXT NULL,
                updated_at TEXT NULL,
                deleted_at TEXT NULL
            );
        ");

        // Registrera PDO i containern
        $container->addShared(PDO::class, fn() => $pdo);
        $container->add('Psr\Container\ContainerInterface', fn() => $container);

        ApplicationContainer::set($container);
    }

    private function makeUser(string $role = 'user'): User
    {
        $pdo = ApplicationContainer::get()->get(PDO::class);

        if (!$pdo instanceof PDO) {
            $this->fail('Container must return a PDO instance for ' . PDO::class);
        }

        // unik e-post per anrop
        $this->emailSeq++;
        $email = "test{$this->emailSeq}@example.com";

        $stmt = $pdo->prepare("
            INSERT INTO users (first_name, last_name, email, password, avatar, role, created_at, updated_at, deleted_at)
            VALUES (:first_name, :last_name, :email, :password, :avatar, :role, NULL, NULL, NULL)
        ");
        $stmt->execute([
            ':first_name' => 'Test',
            ':last_name'  => 'User',
            ':email'      => $email,
            ':password'   => password_hash('secret123', PASSWORD_DEFAULT),
            ':avatar'     => '/images/graphics/avatar.png',
            ':role'       => $role,
        ]);
        $id = (int) $pdo->lastInsertId();

        $u = new User();
        $ref = new ReflectionClass($u);
        $p = $ref->getProperty('attributes');
        $p->setAccessible(true);
        $p->setValue($u, [
            'id' => $id,
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => $email,
            'avatar' => '/images/graphics/avatar.png',
            'password' => password_hash('secret123', PASSWORD_DEFAULT),
        ]);

        return $u;
    }

    public function testHasAnyRoleMixedTypesAndEdges(): void
    {
        $user = $this->makeUser('user');

        // Blandning av enum och sträng, inkl. irrelevanta roller
        $this->assertTrue($user->hasAnyRole('guest', Role::User, 'editor'));
        $this->assertFalse($user->hasAnyRole('guest', 'editor', 'viewer'));

        // Edge: inga roller angivna
        $this->assertFalse($user->hasAnyRole());

        // Edge: null-liknande värden ska ignoreras (castas bort i PHP variadics)
        // Vi testar med endast irrelevanta strängar
        $this->assertFalse($user->hasAnyRole(''));
        $this->assertFalse($user->hasAnyRole(' ', '  '));
    }

    public function testSetRolePersistsWithSaveAndReload(): void
    {
        // Skapa user med role=user
        $user = $this->makeUser('user');

        // Verifiera initialt
        $this->assertTrue($user->isUser());
        $this->assertFalse($user->isAdmin());

        // Ändra roll och spara (simulerar update i DB)
        $user->setRole('admin');
        $this->assertTrue($user->isAdmin(), 'Objektet i minnet ska spegla ändringen direkt.');
        $this->assertTrue($user->save(), 'Save ska lyckas och uppdatera DB.');

        // Ladda om från DB och verifiera att rollen är persisterad
        $id = $user->getAttribute('id');
        if (!is_int($id) && !is_string($id)) {
            $this->fail('User id must be int|string for Model::find().');
        }

        /** @var int|string $id */
        $reloaded = \App\Models\User::find($id);
        $this->assertNotNull($reloaded, 'Reloaded user ska finnas.');
        $this->assertTrue($reloaded->isAdmin(), 'Reloaded user ska ha admin.');
        $this->assertFalse($reloaded->isUser());
    }

    public function testSetRoleWithEnumPersistsWithSaveAndReload(): void
    {
        // Skapa user med role=admin
        $user = $this->makeUser('admin');

        $this->assertTrue($user->isAdmin());
        $this->assertFalse($user->isUser());

        // Ändra med enum
        $user->setRole(\Radix\Enums\Role::User);
        $this->assertTrue($user->isUser(), 'Objektet i minnet ska spegla ändringen direkt.');
        $this->assertTrue($user->save(), 'Save ska lyckas och uppdatera DB.');

        // Ladda om och verifiera
        $id = $user->getAttribute('id');
        if (!is_int($id) && !is_string($id)) {
            $this->fail('User id must be int|string for Model::find().');
        }

        /** @var int|string $id */
        $reloaded = \App\Models\User::find($id);
        $this->assertNotNull($reloaded);
        $this->assertTrue($reloaded->isUser());
        $this->assertFalse($reloaded->isAdmin());
    }

    public function testHasRoleWithString(): void
    {
        $user = $this->makeUser('admin');
        $this->assertTrue($user->hasRole('admin'));
        $this->assertFalse($user->hasRole('user'));
    }

    public function testHasRoleWithEnum(): void
    {
        $user = $this->makeUser('user');
        $this->assertTrue($user->hasRole(Role::User));
        $this->assertFalse($user->hasRole(Role::Admin));
    }

    public function testIsAdminAndIsUserHelpers(): void
    {
        $admin = $this->makeUser('admin');
        $user  = $this->makeUser('user');

        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($admin->isUser());

        $this->assertTrue($user->isUser());
        $this->assertFalse($user->isAdmin());
    }

    public function testHasAnyRole(): void
    {
        $user = $this->makeUser('user');
        $this->assertTrue($user->hasAnyRole('guest', 'user', 'editor'));
        $this->assertTrue($user->hasAnyRole(Role::User, 'editor'));
        $this->assertFalse($user->hasAnyRole('guest', 'editor'));
    }

    public function testHasAtLeastRoleHierarchy(): void
    {
        $admin = $this->makeUser('admin');
        $user  = $this->makeUser('user');

        $this->assertTrue($admin->hasAtLeast('user'));
        $this->assertTrue($admin->hasAtLeast(Role::Admin));
        $this->assertTrue($user->hasAtLeast('user'));
        $this->assertFalse($user->hasAtLeast('admin'));
    }

    public function testSetRoleWithString(): void
    {
        $user = $this->makeUser('user');
        $user->setRole('admin');

        $this->assertTrue($user->isAdmin());
        $this->assertFalse($user->isUser());
    }

    public function testSetRoleWithEnum(): void
    {
        $user = $this->makeUser('admin');
        $user->setRole(Role::User);

        $this->assertTrue($user->isUser());
        $this->assertFalse($user->isAdmin());
    }

    public function testRoleEnumAccessor(): void
    {
        $user = $this->makeUser('admin');
        $this->assertSame(Role::Admin, $user->roleEnum());

        $user2 = $this->makeUser('user');
        $this->assertSame(Role::User, $user2->roleEnum());
    }

    public function testSetRoleWithInvalidValueThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        // Vi måste även verifiera att undantagsmeddelandet innehåller den felaktiga rollen.
        // Detta dödar mutanten som tar bort . $roleLabel från throw-satsen.
        $this->expectExceptionMessage('Ogiltig roll: superadmin');

        $user = $this->makeUser('user');
        $user->setRole('superadmin');
    }

    public function testRoleLevels(): void
    {
        $this->assertSame(10, Role::User->level());
        $this->assertSame(20, Role::Support->level());
        $this->assertSame(30, Role::Editor->level());
        $this->assertSame(40, Role::Moderator->level());
        $this->assertSame(50, Role::Admin->level());
    }

    public function testHasRoleForNewRoles(): void
    {
        $user = $this->makeUser('editor');
        $this->assertTrue($user->hasRole('editor'));
        $this->assertFalse($user->hasRole('support'));
        $this->assertFalse($user->hasRole('moderator'));
        $this->assertFalse($user->hasRole('admin'));

        $user2 = $this->makeUser('support');
        $this->assertTrue($user2->hasRole(Role::Support));
        $this->assertFalse($user2->hasRole(Role::Editor));
    }

    public function testHasAnyRoleForNewRoles(): void
    {
        $u = $this->makeUser('support');
        $this->assertTrue($u->hasAnyRole('guest', 'support', 'editor'));
        $this->assertTrue($u->hasAnyRole(Role::Support, 'editor'));
        $this->assertFalse($u->hasAnyRole('guest', 'editor', 'moderator'));
    }

    public function testHasAtLeastHierarchyExpanded(): void
    {
        $user = $this->makeUser('user');
        $support = $this->makeUser('support');
        $editor = $this->makeUser('editor');
        $moderator = $this->makeUser('moderator');
        $admin = $this->makeUser('admin');

        // user
        $this->assertTrue($user->hasAtLeast('user'));
        $this->assertFalse($user->hasAtLeast('support'));
        $this->assertFalse($user->hasAtLeast('editor'));
        $this->assertFalse($user->hasAtLeast('moderator'));
        $this->assertFalse($user->hasAtLeast('admin'));

        // support
        $this->assertTrue($support->hasAtLeast('user'));
        $this->assertTrue($support->hasAtLeast('support'));
        $this->assertFalse($support->hasAtLeast('editor'));
        $this->assertFalse($support->hasAtLeast('moderator'));
        $this->assertFalse($support->hasAtLeast('admin'));

        // editor
        $this->assertTrue($editor->hasAtLeast('user'));
        $this->assertTrue($editor->hasAtLeast('support'));
        $this->assertTrue($editor->hasAtLeast('editor'));
        $this->assertFalse($editor->hasAtLeast('moderator'));
        $this->assertFalse($editor->hasAtLeast('admin'));

        // moderator
        $this->assertTrue($moderator->hasAtLeast('user'));
        $this->assertTrue($moderator->hasAtLeast('support'));
        $this->assertTrue($moderator->hasAtLeast('editor'));
        $this->assertTrue($moderator->hasAtLeast('moderator'));
        $this->assertFalse($moderator->hasAtLeast('admin'));

        // admin
        $this->assertTrue($admin->hasAtLeast('user'));
        $this->assertTrue($admin->hasAtLeast('support'));
        $this->assertTrue($admin->hasAtLeast('editor'));
        $this->assertTrue($admin->hasAtLeast('moderator'));
        $this->assertTrue($admin->hasAtLeast('admin'));
    }

    public function testIsHelpersForNewRoles(): void
    {
        $support = $this->makeUser('support');
        $editor = $this->makeUser('editor');
        $moderator = $this->makeUser('moderator');

        // Helpers i User: isSupport(), isEditor(), isModerator()
        $this->assertTrue($editor->isEditor());
        $this->assertFalse($editor->isAdmin());
        $this->assertFalse($editor->isUser());

        // Direkt assertions (metoderna finns i User)
        $this->assertTrue($support->isSupport());
        $this->assertFalse($support->isEditor());
        $this->assertTrue($moderator->isModerator());
        $this->assertFalse($moderator->isAdmin());
    }

    public function testSetRolePersistenceForEachNewRole(): void
    {
        $user = $this->makeUser('user');

        foreach (['support', 'editor', 'moderator', 'admin', 'user'] as $role) {
            $user->setRole($role);
            $this->assertTrue($user->save());

            $id = $user->getAttribute('id');
            if (!is_int($id) && !is_string($id)) {
                $this->fail('User id must be int|string for Model::find().');
            }

            /** @var int|string $id */
            $reloaded = \App\Models\User::find($id);
            $this->assertNotNull($reloaded);
            $this->assertTrue($reloaded->hasRole($role), "Reloaded user ska ha rollen $role");
        }
    }

    public function testHasAtLeastReturnsFalseForInvalidInputs(): void
    {
        $user = $this->makeUser('user');

        // Testa med en ogiltig roll (target blir null)
        // Detta dödar mutanten eftersom den försöker utvärdera nivåjämförelsen istället för att avbryta tidigt
        $this->assertFalse($user->hasAtLeast('non_existent_role'));

        // Testa med en användare som har en ogiltig roll sparad (current blir null)
        // Vi använder makeUser för att undvika problem med oinitierad Model (som new User() orsakade)
        $userWithInvalidRole = $this->makeUser('some_weird_role');
        $this->assertFalse($userWithInvalidRole->hasAtLeast('user'));
    }

    public function testHasRoleReturnsFalseWhenUserHasNoRole(): void
    {
        // Skapa en användare som har en ogiltig roll sparad i databasen
        // Detta gör att roleEnum() returnerar null
        $user = $this->makeUser('invalid_role_string');

        // Anropa hasRole med en giltig roll
        // Originalkoden ska hantera null-current säkert och returnera false
        // Mutanten (utan null-safe operator) kommer att krascha här
        $this->assertFalse($user->hasRole('user'));
        $this->assertFalse($user->hasRole(Role::Admin));
    }

    public function testGetEmailAttributeIsPublic(): void
    {
        $user = new User();
        // Direkt anrop verifierar att metoden är public.
        // Mutanten PublicVisibility (som gör den protected) kommer att orsaka ett fatal error här.
        $this->assertSame('test@example.com', $user->getEmailAttribute('test@example.com'));
    }

    public function testSetEmailAttributeTrimsWhitespace(): void
    {
        $user = new User();

        // Sätt e-post med blanksteg
        $user->setEmailAttribute('  test@example.com  ');

        // Kontrollera att blanksteg har trimmats bort
        $this->assertSame('test@example.com', $user->getAttribute('email'));
    }

    public function testSetEmailAttributeHandlesMultibyteCharacters(): void
    {
        $user = new User();

        // Sätt e-post med multibyte-tecken i versaler
        // strtolower hanterar inte ÅÄÖ korrekt, men mb_strtolower gör det
        $user->setEmailAttribute('ÅÄÖ@EXAMPLE.COM');

        // Verifiera att det har konverterats korrekt till små bokstäver
        $this->assertSame('åäö@example.com', $user->getAttribute('email'));
    }

    public function testGetLastNameAttributeIsPublic(): void
    {
        $user = new User();
        // Direkt anrop verifierar att metoden är public.
        // Mutanten PublicVisibility (som gör den protected) kommer att orsaka ett fatal error här.
        $this->assertSame('Doe', $user->getLastNameAttribute('Doe'));
    }

    public function testGetFirstNameAttributeIsPublic(): void
    {
        $user = new User();
        // Direkt anrop verifierar att metoden är public.
        $this->assertSame('John', $user->getFirstNameAttribute('John'));
    }

    public function testSetLastNameAttributeIsPublic(): void
    {
        $user = new User();
        // Direkt anrop verifierar att metoden är public.
        // Mutanten PublicVisibility (som gör den protected) kommer att orsaka ett fatal error här.
        $user->setLastNameAttribute('Doe');
        $this->assertSame('Doe', $user->getAttribute('last_name'));
    }

    public function testSetFirstNameAttributeIsPublic(): void
    {
        $user = new User();
        // Direkt anrop verifierar att metoden är public.
        $user->setFirstNameAttribute('John');
        $this->assertSame('John', $user->getAttribute('first_name'));
    }

    public function testSetPasswordAttributeIsPublic(): void
    {
        $user = new User();
        // Direkt anrop verifierar att metoden är public.
        // Mutanten PublicVisibility (som gör den protected) kommer att orsaka ett fatal error här.
        $user->setPasswordAttribute('secret123');

        // Verifiera också att lösenordet har blivit hashat
        // Detta hjälper till att döda LogicalNot-mutanten som skulle spara klartext om det inte redan var hashat
        $hashedPassword = $user->getAttribute('password');

        // Försäkra oss om att det är en sträng för PHPStan och testlogik
        $this->assertIsString($hashedPassword);
        $this->assertNotSame('secret123', $hashedPassword);
        $this->assertTrue(password_verify('secret123', $hashedPassword));
    }

    public function testSetPasswordAttributeDoesNotRehashHashedPassword(): void
    {
        $user = new User();
        $alreadyHashed = password_hash('secret123', PASSWORD_DEFAULT);

        // Sätt ett redan hashat lösenord
        $user->setPasswordAttribute($alreadyHashed);

        // Verifiera att det sparades exakt som det var (ingen dubbel-hashning)
        $this->assertSame($alreadyHashed, $user->getAttribute('password'));
    }
}
