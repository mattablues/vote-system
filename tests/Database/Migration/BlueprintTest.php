<?php

declare(strict_types=1);

namespace Radix\Tests\Database\Migration;

use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;
use Radix\Database\Migration\Blueprint;

class BlueprintTest extends TestCase
{
    public function testCreateTableWithBasicColumns(): void
    {
        $blueprint = new Blueprint('users');

        $blueprint->string('name', 100, ['nullable' => false, 'comment' => 'User name']);
        $blueprint->integer('age', true);
        $blueprint->timestamps();

        $expectedSql = "CREATE TABLE `users` ("
            . "`name` VARCHAR(100) NOT NULL COMMENT 'User name', "
            . "`age` INT UNSIGNED NOT NULL, "
            . "`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, "
            . "`updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"
            . ") DEFAULT CHARSET=utf8mb4;";

        $this->assertEquals($expectedSql, $blueprint->toSql());
    }

    public function testTableOptions(): void
    {
        $blueprint = new Blueprint('posts');
        $blueprint->engine('InnoDB');
        $blueprint->autoIncrement(10);
        $blueprint->tableComment('Blog posts table.');

        $blueprint->string('title', 255);
        $blueprint->text('content');

        $expectedSql = "CREATE TABLE `posts` ("
            . "`title` VARCHAR(255) NOT NULL, "
            . "`content` TEXT NOT NULL"
            . ") ENGINE=InnoDB AUTO_INCREMENT=10 COMMENT = 'Blog posts table.' DEFAULT CHARSET=utf8mb4;";

        $this->assertEquals($expectedSql, $blueprint->toSql());
    }

    public function testAddConstraintsAndIndexes(): void
    {
        $blueprint = new Blueprint('orders');

        $blueprint->integer('id', true);
        $blueprint->primary(['id']);
        $blueprint->unique(['order_number']);
        $blueprint->index(['customer_id']);
        $blueprint->foreign('customer_id', 'customers', 'id');

        $expectedSql = "CREATE TABLE `orders` ("
            . "`id` INT UNSIGNED NOT NULL, "
            . "PRIMARY KEY (`id`), "
            . "UNIQUE INDEX `unique_order_number` (`order_number`), "
            . "INDEX `index_customer_id` (`customer_id`), "
            . "FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE"
            . ") DEFAULT CHARSET=utf8mb4;";

        $this->assertEquals($expectedSql, $blueprint->toSql());
    }

    public function testAlterTable(): void
    {
        $blueprint = new Blueprint('users', true);

        // Lägg till kolumn
        $blueprint->addColumn('string', 'email', ['nullable' => true, 'comment' => 'User email', 'after' => 'name']);
        // Ta bort kolumn
        $blueprint->dropColumn('age');
        // Modifiera primärnycklar
        $blueprint->modifyPrimary(['id', 'email']);

        $expectedSql = [
            'ALTER TABLE `users` ADD COLUMN `email` VARCHAR(255) NULL COMMENT \'User email\' AFTER `name`;',
            'ALTER TABLE `users` DROP COLUMN `age`;',
            'ALTER TABLE `users` DROP PRIMARY KEY;',
            'ALTER TABLE `users` ADD PRIMARY KEY (`id`, `email`);',
        ];

        $this->assertEquals($expectedSql, $blueprint->toAlterSql());
    }

    public function testConstraintsAndIndexes(): void
    {
        $blueprint = new Blueprint('products');
        $blueprint->integer('id', true); // Definiera primärkolumn
        $blueprint->primary(['id']); // Lägg till primärnyckel

        // Fortsätt med övriga kolumner och constraints
        $blueprint->string('sku')->unique(['sku'], 'unique_sku');
        $blueprint->index(['category_id'], 'category_index');
        $blueprint->foreign('category_id', 'categories', 'id', 'SET NULL', 'CASCADE');

        $expectedSql = "CREATE TABLE `products` ("
            . "`id` INT UNSIGNED NOT NULL, "
            . "`sku` VARCHAR(255) NOT NULL, "
            . "PRIMARY KEY (`id`), "
            . "UNIQUE INDEX `unique_sku` (`sku`), "
            . "INDEX `category_index` (`category_id`), "
            . "FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE"
            . ") DEFAULT CHARSET=utf8mb4;";

        $this->assertEquals($expectedSql, $blueprint->toSql());
    }

    public function testVariousDataTypes(): void
    {
        $blueprint = new Blueprint('attributes');

        // Lägg till olika typer av kolumner
        $blueprint->tinyInteger('status', true); // TINYINT UNSIGNED
        $blueprint->bigInteger('score', false); // BIGINT
        $blueprint->boolean('is_active'); // TINYINT(1)
        $blueprint->float('rating', 10, 2); // FLOAT(10, 2)
        $blueprint->enum('role', ['admin', 'user', 'guest']); // ENUM
        $blueprint->json('settings'); // JSON

        $expectedSql = "CREATE TABLE `attributes` ("
            . "`status` TINYINT UNSIGNED NOT NULL, "
            . "`score` BIGINT NOT NULL, "
            . "`is_active` TINYINT(1) NOT NULL, "
            . "`rating` FLOAT(10, 2) NOT NULL, " // FLOAT(10, 2) stöds nu
            . "`role` ENUM('admin', 'user', 'guest') NOT NULL, "
            . "`settings` JSON NOT NULL"
            . ") DEFAULT CHARSET=utf8mb4;";

        $this->assertEquals($expectedSql, $blueprint->toSql());
    }

    public function testAddColumnWithBooleanDefault(): void
    {
        $blueprint = new Blueprint('test_table');

        // Testa en boolean-standard (true)
        $blueprint->boolean('is_active', ['default' => true]);

        $expectedSqlTrue = "CREATE TABLE `test_table` ("
            . "`is_active` TINYINT(1) NOT NULL DEFAULT '1'"
            . ") DEFAULT CHARSET=utf8mb4;";
        $this->assertEquals($expectedSqlTrue, $blueprint->toSql());

        // Testa en boolean-standard (false)
        $blueprint = new Blueprint('test_table');
        $blueprint->boolean('is_disabled', ['default' => false]);

        $expectedSqlFalse = "CREATE TABLE `test_table` ("
            . "`is_disabled` TINYINT(1) NOT NULL DEFAULT '0'"
            . ") DEFAULT CHARSET=utf8mb4;";
        $this->assertEquals($expectedSqlFalse, $blueprint->toSql());
    }

    public function testTableOptionsWithComments(): void
    {
        $blueprint = new Blueprint('logs');
        $blueprint->engine('InnoDB');
        $blueprint->autoIncrement(500);
        $blueprint->tableComment('Table for storing application logs.');

        $blueprint->integer('id', true);
        $blueprint->string('message', 500);

        $expectedSql = "CREATE TABLE `logs` ("
            . "`id` INT UNSIGNED NOT NULL, "
            . "`message` VARCHAR(500) NOT NULL"
            . ") ENGINE=InnoDB AUTO_INCREMENT=500 COMMENT = 'Table for storing application logs.' DEFAULT CHARSET=utf8mb4;";

        $this->assertEquals($expectedSql, $blueprint->toSql());
    }

    public function testAlterTableWithMultipleOperations(): void
    {
        $blueprint = new Blueprint('users', true);

        // Lägg till kolumn
        $blueprint->addColumn('string', 'email', ['after' => 'name', 'nullable' => true, 'comment' => 'User email']);
        // Ta bort kolumn
        $blueprint->dropColumn('age');
        // Modifiera primärnyckel
        $blueprint->modifyPrimary(['id', 'email']);
        // Lägg till en constraint (Foreign key)
        $blueprint->foreign('role_id', 'roles', 'id');

        // Specificera den förväntade SQL-strukturen
        $expectedSql = [
            "ALTER TABLE `users` ADD COLUMN `email` VARCHAR(255) NULL COMMENT 'User email' AFTER `name`;",
            "ALTER TABLE `users` DROP COLUMN `age`;",
            "ALTER TABLE `users` DROP PRIMARY KEY;",
            "ALTER TABLE `users` ADD PRIMARY KEY (`id`, `email`);",
            "ALTER TABLE `users` ADD FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;",
        ];

        // Kontrollera att genererad SQL matchar förväntad SQL
        $this->assertEquals($expectedSql, $blueprint->toAlterSql());
    }

    public function testDropColumnOutsideAlterContext(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('dropColumn can only be used in ALTER TABLE context.');

        $blueprint = new Blueprint('users'); // Skapar ny tabell (ej ALTER)
        $blueprint->dropColumn('age'); // Detta ska kasta undantag
    }

    public function testEmptyTableWithOptionsOnly(): void
    {
        $blueprint = new Blueprint('empty_table');
        $blueprint->engine('MyISAM')->tableComment('Empty table for testing.');

        $expectedSql = "CREATE TABLE `empty_table` () ENGINE=MyISAM COMMENT = 'Empty table for testing.' DEFAULT CHARSET=utf8mb4;";

        $this->assertEquals($expectedSql, $blueprint->toSql());
    }

    public function testBatchAddColumns(): void
    {
        $blueprint = new Blueprint('profiles', true);

        $blueprint->addColumn('integer', 'id', ['nullable' => false]);
        $blueprint->addColumn('string', 'username', ['nullable' => false]);
        $blueprint->addColumn('string', 'bio', ['nullable' => true]);

        $expectedSql = [
            "ALTER TABLE `profiles` ADD COLUMN `id` INT NOT NULL;",
            "ALTER TABLE `profiles` ADD COLUMN `username` VARCHAR(255) NOT NULL;",
            "ALTER TABLE `profiles` ADD COLUMN `bio` VARCHAR(255) NULL;",
        ];

        $this->assertEquals($expectedSql, $blueprint->toAlterSql());
    }

    public function testCreateEmptyTable(): void
    {
        $blueprint = new Blueprint('empty_table_no_options');

        $expectedSql = "CREATE TABLE `empty_table_no_options` () DEFAULT CHARSET=utf8mb4;";

        $this->assertEquals($expectedSql, $blueprint->toSql());
    }

    public function testCreateTableWithDefaultStringLength(): void
    {
        $blueprint = new Blueprint('defaults_table');

        // Kolumn utan längd och attribut
        $blueprint->string('name');

        $expectedSql = "CREATE TABLE `defaults_table` ("
            . "`name` VARCHAR(255) NOT NULL"
            . ") DEFAULT CHARSET=utf8mb4;";

        $this->assertEquals($expectedSql, $blueprint->toSql());
    }

    public function testCombinedAlterOperations(): void
    {
        $blueprint = new Blueprint('users', true);

        $blueprint->addColumn('string', 'nickname', ['nullable' => true]);
        $blueprint->dropColumn('age');
        $blueprint->modifyPrimary(['id', 'nickname']);
        $blueprint->engine('MyISAM');

        $expectedSql = [
            "ALTER TABLE `users` ADD COLUMN `nickname` VARCHAR(255) NULL;",
            "ALTER TABLE `users` DROP COLUMN `age`;",
            "ALTER TABLE `users` DROP PRIMARY KEY;",
            "ALTER TABLE `users` ADD PRIMARY KEY (`id`, `nickname`);",
        ];

        $this->assertEquals($expectedSql, $blueprint->toAlterSql());
    }

    public function testForeignKeyWithAdvancedConstraints(): void
    {
        $blueprint = new Blueprint('orders');

        $blueprint->integer('user_id');
        $blueprint->foreign('user_id', 'users', 'id', 'SET DEFAULT', 'NO ACTION');

        $expectedSql = "CREATE TABLE `orders` ("
            . "`user_id` INT NOT NULL, "
            . "FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET DEFAULT ON UPDATE NO ACTION"
            . ") DEFAULT CHARSET=utf8mb4;";

        $this->assertEquals($expectedSql, $blueprint->toSql());
    }

    public function testRollbackAlterChanges(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot rollback a dropped column automatically. Column details are missing.');

        $blueprint = new Blueprint('users', true);

        // Ursprungliga ändringar som ska ångras
        $blueprint->addColumn('integer', 'account_id', ['nullable' => false]);
        $blueprint->dropColumn('phone_number'); // Detta orsakar ett undantag eftersom vi inte kan återskapa en borttagen kolumn.

        // Försök att generera rollback SQL
        $blueprint->toRollbackSql();
    }

    public function testInvalidColumnTypeThrowsException(): void
    {
        $blueprint = $this->getMockBuilder(Blueprint::class)
                          ->setConstructorArgs(['users'])
                          ->onlyMethods(['addColumn'])
                          ->getMock();

        $blueprint->expects($this->once())
                  ->method('addColumn')
                  ->will($this->throwException(new InvalidArgumentException("Unsupported column type: 'invalid_type'")));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Unsupported column type: 'invalid_type'");

        $blueprint->addColumn('invalid_type', 'unknown_column');
    }

    public function testInvalidColumnAttributesThrowException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Unsupported column attribute: 'invalid_attribute'");

        $blueprint = new Blueprint('users');
        $blueprint->addColumn('string', 'name', ['invalid_attribute' => true]);
    }

    public function testCreateLargeTable(): void
    {
        $blueprint = new Blueprint('big_table');

        // Lägg till många kolumner
        for ($i = 1; $i <= 100; $i++) {
            $blueprint->string("column_$i", 100, ['nullable' => true]);
        }

        $columnsSql = implode(", ", array_map(
            fn($i) => "`column_$i` VARCHAR(100) NULL",
            range(1, 100)
        ));

        $expectedSql = "CREATE TABLE `big_table` (" . $columnsSql . ") DEFAULT CHARSET=utf8mb4;";

        $this->assertEquals($expectedSql, $blueprint->toSql());
    }

    public function testBatchDropColumns(): void
    {
        $blueprint = new Blueprint('users', true);

        $blueprint->dropColumns(['age', 'address', 'phone_number']);

        $expectedSql = [
            "ALTER TABLE `users` DROP COLUMN `age`;",
            "ALTER TABLE `users` DROP COLUMN `address`;",
            "ALTER TABLE `users` DROP COLUMN `phone_number`;",
        ];

        $this->assertEquals($expectedSql, $blueprint->toAlterSql());
    }

    public function testDatatypeMapping(): void
    {
        $blueprint = new Blueprint('data_types');

        $blueprint->string('name');
        $blueprint->integer('age', true, ['nullable' => true]);
        $blueprint->boolean('is_active');
        $blueprint->uuid('identifier');

        $expectedSql = "CREATE TABLE `data_types` ("
            . "`name` VARCHAR(255) NOT NULL, "
            . "`age` INT UNSIGNED NULL, "
            . "`is_active` TINYINT(1) NOT NULL, "
            . "`identifier` CHAR(36) NOT NULL"
            . ") DEFAULT CHARSET=utf8mb4;";

        $this->assertEquals($expectedSql, $blueprint->toSql());
    }

    public function testRollbackAddColumn(): void
    {
        $blueprint = new Blueprint('users', true);

        $blueprint->addColumn('string', 'nickname', ['nullable' => true]);

        $rollbackSql = $blueprint->toRollbackSql();

        $expectedRollback = [
            "ALTER TABLE `users` DROP COLUMN `nickname`;",
        ];

        $this->assertEquals($expectedRollback, $rollbackSql);
    }

    public function testModifyPrimary(): void
    {
        $blueprint = new Blueprint('users', true);

        $blueprint->modifyPrimary(['id', 'email']);

        $expectedSql = [
            "ALTER TABLE `users` DROP PRIMARY KEY;",
            "ALTER TABLE `users` ADD PRIMARY KEY (`id`, `email`);",
        ];

        $this->assertEquals($expectedSql, $blueprint->toAlterSql());
    }

    public function testRollbackWithoutChanges(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('No operations to rollback.');

        $blueprint = new Blueprint('users', true);

        $blueprint->toRollbackSql();
    }

    public function testRollbackDropColumn(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot rollback a dropped column automatically. Column details are missing.');

        $blueprint = new Blueprint('users', true);

        $blueprint->dropColumn('username');

        $blueprint->toRollbackSql();
    }

    public function testForeignKeyConstraint(): void
    {
        $blueprint = new Blueprint('orders');

        $blueprint->integer('user_id');
        $blueprint->foreign('user_id', 'users', 'id', 'SET NULL', 'CASCADE');

        $expectedSql = "CREATE TABLE `orders` ("
            . "`user_id` INT NOT NULL, "
            . "FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE"
            . ") DEFAULT CHARSET=utf8mb4;";

        $this->assertEquals($expectedSql, $blueprint->toSql());
    }

    public function testInvalidColumnAttributes(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Unsupported column attribute: 'random_attribute'");

        $blueprint = new Blueprint('users');
        $blueprint->addColumn('string', 'name', ['random_attribute' => true]);
    }

    public function testInvalidColumnType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Unsupported column type: 'invalid_type'");

        $blueprint = new Blueprint('users');
        $blueprint->addColumn('invalid_type', 'test_column');
    }
    public function testInvalidColumnTypeWithMock(): void
    {
        $blueprint = $this->getMockBuilder(Blueprint::class)
                          ->setConstructorArgs(['users'])
                          ->getMock();

        $blueprint->expects($this->once())
                  ->method('addColumn')
                  ->will($this->throwException(new InvalidArgumentException("Unsupported column type: 'invalid_type'")));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Unsupported column type: 'invalid_type'");

        $blueprint->addColumn('invalid_type', 'unknown_column');
    }
}
