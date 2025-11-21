<?php

declare(strict_types=1);

namespace Database\Query;

use Exception;
use Generator;
use InvalidArgumentException;
use PDO;
use PHPUnit\Framework\TestCase;
use Radix\Database\Connection;
use Radix\Database\QueryBuilder\QueryBuilder;
use RuntimeException;
use TypeError;

class QueryBuilderTest extends TestCase
{
    private Connection $connection;

    protected function setUp(): void
    {
        parent::setUp();

        // Använd SQLite i minnet för testerna
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->connection = new Connection($pdo);
    }

    public function testNestedWhere(): void
    {
        $query = (new QueryBuilder())
            ->setConnection($this->connection)
            ->from('users')
            ->where('status', '=', 'active')
            ->where(function (QueryBuilder $q): void {
                $q->where('age', '>=', 18)
                  ->where('country', '=', 'Sweden');
            })
            ->where('deleted_at', 'IS', null);

        $this->assertEquals(
            'SELECT * FROM `users` WHERE `status` = ? AND (`age` >= ? AND `country` = ?) AND `deleted_at` IS NULL',
            $query->toSql()
        );

        $this->assertEquals(
            ['active', 18, 'Sweden'],
            $query->getBindings()
        );
    }

    public function testSimpleSelectQuery(): void
    {
        $query = (new QueryBuilder())
            ->setConnection($this->connection)
            ->from('users');

        $this->assertEquals(
            'SELECT * FROM `users`',
            $query->toSql(),
            'QueryBuilder should generate a simple SELECT query.'
        );
    }

    public function testSelectWithAlias(): void
    {
        $query = (new QueryBuilder())
            ->setConnection($this->connection)
            ->from('users AS u');

        $this->assertEquals(
            'SELECT * FROM `users` AS `u`',
            $query->toSql(),
            'QueryBuilder should handle table aliases correctly in a SELECT query.'
        );
    }

    public function testWithSoftDeletesRemovesDeletedAtFilter(): void
    {
        // Setup QueryBuilder och aktivera withSoftDeletes
        $query = (new QueryBuilder())
            ->setConnection($this->connection)
            ->from('users')
            ->withSoftDeletes() // Aktivera soft deletes
            ->where('email', '=', 'test@example.com');

        // Kontrollera att 'deleted_at IS NULL' inte inkluderas i SQL-frågan
        $this->assertEquals(
            'SELECT * FROM `users` WHERE `email` = ?',
            $query->toSql(),
            'QueryBuilder should not include deleted_at filter when withSoftDeletes is active.'
        );

        // Kontrollera att bindningarna endast innehåller parametern för email
        $this->assertEquals(
            ['test@example.com'],
            $query->getBindings(),
            'QueryBuilder should bind the WHERE clause values correctly.'
        );
    }

    public function testSelectWithWhereClause(): void
    {
        $query = (new QueryBuilder())
            ->setConnection($this->connection)
            ->from('users')
            ->where('id', '=', 1);

        $this->assertEquals(
            'SELECT * FROM `users` WHERE `id` = ?',
            $query->toSql(),
            'QueryBuilder should generate a SELECT query with WHERE clause.'
        );

        $this->assertEquals(
            [1],
            $query->getBindings(),
            'QueryBuilder should bind the WHERE clause values correctly.'
        );
    }

    public function testJoinQuery(): void
    {
        $query = (new QueryBuilder())
            ->setConnection($this->connection)
            ->from('users')
            ->join('profiles', 'users.id', '=', 'profiles.user_id');

        $this->assertEquals(
            'SELECT * FROM `users` INNER JOIN `profiles` ON `users`.`id` = `profiles`.`user_id`',
            $query->toSql(),
            'QueryBuilder should generate a SELECT query with JOIN clause.'
        );
    }

    public function testComplexQueryWithPagination(): void
    {
        $query = (new QueryBuilder())
            ->setConnection($this->connection)
            ->select(['users.id', 'users.name'])
            ->from('users')
            ->where('status', '=', 'active')
            ->orderBy('users.name')
            ->limit(10)
            ->offset(20);

        $this->assertEquals(
            'SELECT `users`.`id`, `users`.`name` FROM `users` WHERE `status` = ? ORDER BY `users`.`name` ASC LIMIT 10 OFFSET 20',
            $query->toSql(),
            'QueryBuilder should generate a SELECT query with WHERE, ORDER BY, LIMIT, and OFFSET clauses.'
        );

        $this->assertEquals(
            ['active'],
            $query->getBindings(),
            'QueryBuilder should bind the WHERE clause values correctly for a complex query.'
        );
    }

    public function testSearchQuery(): void
    {
        $query = (new QueryBuilder())
            ->setConnection($this->connection)
            ->select(['users.id', 'users.name'])
            ->from('users')
            ->whereNull('deleted_at')
            ->where('users.name', 'LIKE', '%John%')
            ->orWhere('users.email', 'LIKE', '%john@example.com%')
            ->limit(10)
            ->offset(0);

        $this->assertEquals(
            'SELECT `users`.`id`, `users`.`name` FROM `users` WHERE `deleted_at` IS NULL AND `users`.`name` LIKE ? OR `users`.`email` LIKE ? LIMIT 10 OFFSET 0',
            $query->toSql(),
            'QueryBuilder should generate a SELECT query with WHERE, OR WHERE, LIMIT, and OFFSET clauses.'
        );

        $this->assertEquals(
            ['%John%', '%john@example.com%'],
            $query->getBindings(),
            'QueryBuilder should bind the search query values correctly.'
        );
    }

    public function testComplexConditions(): void
    {
        $query = (new QueryBuilder())
            ->setConnection($this->connection)
            ->from('users')
            ->where('age', '>=', 18)
            ->orWhere('country', 'LIKE', 'Sweden')
            ->whereNotNull('joined_at');

        $this->assertEquals(
            "SELECT * FROM `users` WHERE `age` >= ? OR `country` LIKE ? AND `joined_at` IS NOT NULL",
            $query->toSql()
        );

        $this->assertEquals(
            [18, 'Sweden'],
            $query->getBindings()
        );
    }

    public function testInsertQuery(): void
    {
        $query = (new QueryBuilder())
            ->setConnection($this->connection)
            ->from('users')
            ->insert(['name' => 'John Doe', 'email' => 'john@example.com']);

        $this->assertEquals(
            "INSERT INTO `users` (`name`, `email`) VALUES (?, ?)",
            $query->toSql()
        );

        $this->assertEquals(
            ['John Doe', 'john@example.com'],
            $query->getBindings()
        );
    }

    public function testUpdateQuery(): void
    {
        $query = (new QueryBuilder())
            ->setConnection($this->connection)
            ->from('users')
            ->where('id', '=', 1)
            ->update(['name' => 'Jane Doe', 'email' => 'jane@example.com']);

        $this->assertEquals(
            "UPDATE `users` SET `name` = ?, `email` = ? WHERE `id` = ?",
            $query->toSql(),
            'QueryBuilder ska generera korrekt UPDATE-syntax.'
        );

        $this->assertEquals(
            ['Jane Doe', 'jane@example.com', 1],
            $query->getBindings(),
            'QueryBuilder ska korrekt hantera bindningsvärden för UPDATE med WHERE-villkor.'
        );
    }

    public function testDeleteQuery(): void
    {
        $query = (new QueryBuilder())
            ->setConnection($this->connection)
            ->from('users')
            ->where('id', '=', 1)
            ->delete();

        $this->assertEquals(
            "DELETE FROM `users` WHERE `id` = ?",
            $query->toSql()
        );

        $this->assertEquals(
            [1],
            $query->getBindings()
        );
    }

    public function testDeleteQueryWithDuplicateBindings(): void
    {
        $query = (new QueryBuilder())
            ->setConnection($this->connection)
            ->from('users')
            ->where('id', '=', 1)
            ->where('id', '=', 1) // Dubblett
            ->delete();

        $this->assertEquals(
            'DELETE FROM `users` WHERE `id` = ? AND `id` = ?',
            $query->toSql()
        );

        $this->assertEquals(
            [1, 1],
            $query->getBindings(),
            'QueryBuilder ska hantera multiples bindningar korrekt, utan dubbelfiltering.'
        );
    }

    public function testUnionQueries(): void
    {
        $query1 = (new QueryBuilder())
            ->setConnection($this->connection)
            ->select(['id', 'name'])
            ->from('users')
            ->where('status', '=', 'active');

        $query2 = (new QueryBuilder())
            ->setConnection($this->connection)
            ->select(['id', 'name'])
            ->from('archived_users')
            ->where('status', '=', 'active');

        $unionQuery = $query1->union($query2);

        $this->assertEquals(
            'SELECT `id`, `name` FROM `users` WHERE `status` = ? UNION SELECT `id`, `name` FROM `archived_users` WHERE `status` = ?',
            $unionQuery->toSql(),
            'QueryBuilder should generate correct UNION syntax.'
        );

        $this->assertEquals(
            ['active', 'active'],
            $unionQuery->getBindings(),
            'QueryBuilder should merge bindings correctly for UNION queries.'
        );
    }

    public function testSubqueries(): void
    {
        $subQuery = (new QueryBuilder())
            ->setConnection($this->connection)
            ->select(['id'])
            ->from('payments')
            ->where('amount', '>', 100);

        $mainQuery = (new QueryBuilder())
            ->setConnection($this->connection)
            ->select(['name'])
            ->from('users')
            ->where('id', 'IN', $subQuery)
            ->where('status', '=', 'active');

        $this->assertEquals(
            'SELECT `name` FROM `users` WHERE `id` IN (SELECT `id` FROM `payments` WHERE `amount` > ?) AND `status` = ?',
            $mainQuery->toSql(),
            'QueryBuilder should handle subqueries correctly.'
        );

        $this->assertEquals(
            [100, 'active'],
            $mainQuery->getBindings(),
            'QueryBuilder should merge bindings correctly for subqueries.'
        );
    }

    public function testUnionAll(): void
    {
        $query1 = (new QueryBuilder())
            ->setConnection($this->connection)
            ->select(['id', 'name'])
            ->from('users')
            ->where('status', '=', 'active');

        $query2 = (new QueryBuilder())
            ->select(['id', 'name'])
            ->from('archived_users')
            ->where('status', '=', 'inactive');

        $unionAllQuery = $query1->union($query2, true);

        $this->assertEquals(
            'SELECT `id`, `name` FROM `users` WHERE `status` = ? UNION ALL SELECT `id`, `name` FROM `archived_users` WHERE `status` = ?',
            $unionAllQuery->toSql(),
            'QueryBuilder ska generera korrekt UNION ALL syntax.'
        );

        $this->assertEquals(
            ['active', 'inactive'],
            $unionAllQuery->getBindings(),
            'QueryBuilder ska hantera bindningar korrekt för UNION ALL.'
        );
    }

    public function testMultipleUnions(): void
    {
        $query1 = (new QueryBuilder())
            ->setConnection($this->connection)
            ->select(['id', 'name'])
            ->from('users')
            ->where('status', '=', 'active');

        $query2 = (new QueryBuilder())
            ->setConnection($this->connection)
            ->select(['id', 'name'])
            ->from('archived_users')
            ->where('status', '=', 'inactive');

        $query3 = (new QueryBuilder())
            ->setConnection($this->connection)
            ->select(['id', 'name'])
            ->from('deleted_users')
            ->where('status', '=', 'deleted');

        $unionQuery = $query1->union($query2)->union($query3);

        $this->assertEquals(
            'SELECT `id`, `name` FROM `users` WHERE `status` = ? UNION SELECT `id`, `name` FROM `archived_users` WHERE `status` = ? UNION SELECT `id`, `name` FROM `deleted_users` WHERE `status` = ?',
            $unionQuery->toSql(),
            'QueryBuilder ska stödja flera union-frågor.'
        );

        $this->assertEquals(
            ['active', 'inactive', 'deleted'],
            $unionQuery->getBindings(),
            'QueryBuilder ska korrekt hantera bindningar för flera union-frågor.'
        );
    }

    public function testUnionWithSubqueries(): void
    {
        $subQuery1 = (new QueryBuilder())
            ->setConnection($this->connection)
            ->select(['id'])
            ->from('payments')
            ->where('amount', '>', 100);

        $query1 = (new QueryBuilder())
            ->setConnection($this->connection)
            ->select(['id', 'name'])
            ->from('users')
            ->where('id', 'IN', $subQuery1);

        $query2 = (new QueryBuilder())
            ->setConnection($this->connection)
            ->select(['id', 'name'])
            ->from('archived_users')
            ->where('status', '=', 'inactive');

        $unionQuery = $query1->union($query2);

        $this->assertEquals(
            'SELECT `id`, `name` FROM `users` WHERE `id` IN (SELECT `id` FROM `payments` WHERE `amount` > ?) UNION SELECT `id`, `name` FROM `archived_users` WHERE `status` = ?',
            $unionQuery->toSql(),
            'QueryBuilder ska hantera unioner korrekt med subqueries.'
        );

        $this->assertEquals(
            [100, 'inactive'],
            $unionQuery->getBindings(),
            'QueryBuilder ska hantera bindningar korrekt för unioner med subqueries.'
        );
    }

    public function testUnionWithInvalidType(): void
    {
        $this->expectException(TypeError::class);

        $query = (new QueryBuilder())
            ->setConnection($this->connection)
            ->select(['id', 'name'])->from('users');
        /** @var mixed $invalid */
        $invalid = 123; // avsiktligt fel typ
        // @phpstan-ignore-next-line Avsiktligt fel typ för att testa TypeError
        $query->union($invalid);
    }

    public function testUnionWithoutBindings(): void
    {
        $query1 = (new QueryBuilder())
            ->setConnection($this->connection)
            ->select(['id', 'name'])
            ->from('users');

        $query2 = (new QueryBuilder())
            ->setConnection($this->connection)
            ->select(['id', 'name'])
            ->from('archived_users');

        $unionQuery = $query1->union($query2);

        $this->assertEquals(
            'SELECT `id`, `name` FROM `users` UNION SELECT `id`, `name` FROM `archived_users`',
            $unionQuery->toSql(),
            'QueryBuilder ska generera korrekt UNION utan bindningsvärden.'
        );

        $this->assertEquals(
            [],
            $unionQuery->getBindings(),
            'QueryBuilder ska hantera tomma bindningar korrekt för unioner.'
        );
    }

    public function testAggregateFunctions(): void
    {
        $query = (new QueryBuilder())
            ->setConnection($this->connection)
            ->from('users')
            ->count('*', 'total_users')
            ->max('age', 'oldest_user')
            ->avg('age', 'average_age');

        $this->assertEquals(
            'SELECT COUNT(*) AS `total_users`, MAX(`age`) AS `oldest_user`, AVG(`age`) AS `average_age` FROM `users`',
            $query->toSql(),
            'QueryBuilder ska generera korrekt SQL med flera aggregatfunktioner.'
        );

        $this->assertEquals([], $query->getBindings(), 'QueryBuilder ska ha noll bindningar för rena aggregatfunktioner.');
    }

    public function testSelectRaw(): void
    {
        $query = (new QueryBuilder())
            ->setConnection($this->connection)
            ->selectRaw("COUNT(id) AS total, NOW() AS current_time")
            ->from('users');

        $this->assertEquals(
            'SELECT COUNT(id) AS total, NOW() AS current_time FROM `users`',
            $query->toSql(),
            'QueryBuilder ska hantera raw SQL expressions korrekt i SELECT.'
        );
    }

    public function testAliasHandling(): void
    {
        $query = (new QueryBuilder())
            ->setConnection($this->connection)
            ->select(['u.id AS user_id', 'u.name AS user_name'])
            ->from('users AS u')
            ->where('u.status', '=', 'active');

        $this->assertEquals(
            'SELECT `u`.`id` AS `user_id`, `u`.`name` AS `user_name` FROM `users` AS `u` WHERE `u`.`status` = ?',
            $query->toSql(),
            'QueryBuilder ska korrekt hantera alias till tabeller och kolumner.'
        );

        $this->assertEquals(['active'], $query->getBindings(), 'Bindningarna ska vara korrekt extraherade vid aliasering.');
    }

    public function testJoins(): void
    {
        $query = (new QueryBuilder())
            ->setConnection($this->connection)
            ->from('users')
            ->join('profiles', 'users.id', '=', 'profiles.user_id')
            ->leftJoin('orders', 'users.id', '=', 'orders.user_id')
            ->fullJoin('addresses', 'users.id', '=', 'addresses.user_id');

        $this->assertEquals(
            'SELECT * FROM `users` INNER JOIN `profiles` ON `users`.`id` = `profiles`.`user_id` LEFT JOIN `orders` ON `users`.`id` = `orders`.`user_id` FULL OUTER JOIN `addresses` ON `users`.`id` = `addresses`.`user_id`',
            $query->toSql(),
            'QueryBuilder ska supporta olika typer av JOIN-klausuler.'
        );
    }

    public function testGroupByAndHaving(): void
    {
        $query = (new QueryBuilder())
            ->setConnection($this->connection)
            ->select(['role', 'COUNT(*) AS total_employees'])
            ->from('users')
            ->groupBy('role')
            ->having('total_employees', '>', 10);

        $this->assertEquals(
            'SELECT `role`, COUNT(*) AS `total_employees` FROM `users` GROUP BY `role` HAVING `total_employees` > ?',
            $query->toSql(),
            'QueryBuilder ska generera korrekt SQL med GROUP BY och HAVING.'
        );

        $this->assertEquals([10], $query->getBindings());
    }

    public function testEmptyWhereClause(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new QueryBuilder())
            ->setConnection($this->connection)
            ->from('users')->where('', '=', 'active');
    }

    public function testInsertWithEmptyData(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new QueryBuilder())->from('users')->insert([]);
    }

    public function testTransactionRollback(): void
    {
        // Skapa en mockad Connection
        $mockConnection = $this->createMock(\Radix\Database\Connection::class);

        // Förvänta att transaktionsmetoderna kallas i rätt ordning
        $mockConnection->expects($this->once())
            ->method('beginTransaction');

        $mockConnection->expects($this->once())
            ->method('rollbackTransaction');

        $mockConnection->expects($this->never())
            ->method('commitTransaction'); // commit ska inte anropas vid rollback

        // Skapa en QueryBuilder och sätt mockad Connection
        $query = (new QueryBuilder())->setConnection($mockConnection);

        // Verifiera att undantag kastas (för rollback)
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Simulated error");

        // Anropa metoden och simulera ett misslyckande
        $query->transaction(function () {
            throw new Exception("Simulated error");
        });
    }

    public function testDistinctQuery(): void
    {
        $query = (new QueryBuilder())
            ->setConnection($this->connection)
            ->distinct()
            ->from('users')
            ->select(['name', 'email']);

        $this->assertEquals(
            'SELECT DISTINCT `name`, `email` FROM `users`',
            $query->toSql(),
            'QueryBuilder should generate a SQL query with DISTINCT keyword.'
        );
    }

    public function testConcatColumns(): void
    {
        $query = (new QueryBuilder())
            ->setConnection($this->connection)
            ->from('users')
            ->concat(['first_name', "' '", 'last_name'], 'full_name');

        $this->assertEquals(
            'SELECT CONCAT(`first_name`, \' \', `last_name`) AS `full_name` FROM `users`',
            $query->toSql(),
            'QueryBuilder should generate a SQL query using CONCAT for columns.'
        );
    }

    public function testWhereIn(): void
    {
        $query = (new QueryBuilder())
            ->setConnection($this->connection)
            ->from('users')
            ->whereIn('id', [1, 2, 3]);

        $this->assertEquals(
            'SELECT * FROM `users` WHERE `id` IN (?, ?, ?)',
            $query->toSql(),
            'QueryBuilder should generate a SQL query with WHERE IN clause.'
        );

        $this->assertEquals(
            [1, 2, 3],
            $query->getBindings(),
            'QueryBuilder should bind values correctly for WHERE IN clause.'
        );
    }

    public function testSubqueryInWhere(): void
    {
        $subQuery = (new QueryBuilder())
            ->setConnection($this->connection)
            ->select(['id'])
            ->from('payments')
            ->where('amount', '>', 100);

        $mainQuery = (new QueryBuilder())
            ->setConnection($this->connection)
            ->from('users')
            ->where('id', 'IN', $subQuery);

        $this->assertEquals(
            'SELECT * FROM `users` WHERE `id` IN (SELECT `id` FROM `payments` WHERE `amount` > ?)',
            $mainQuery->toSql(),
            'QueryBuilder should generate subquery in WHERE clause correctly.'
        );

        $this->assertEquals(
            [100],
            $mainQuery->getBindings(),
            'QueryBuilder should bind subquery values correctly.'
        );
    }

    public function testLimitAndOffset(): void
    {
        $query = (new QueryBuilder())
            ->setConnection($this->connection)
            ->from('users')
            ->where('status', '=', 'active')
            ->orderBy('name')
            ->limit(10)
            ->offset(20);

        $this->assertEquals(
            'SELECT * FROM `users` WHERE `status` = ? ORDER BY `name` ASC LIMIT 10 OFFSET 20',
            $query->toSql(),
            'QueryBuilder should generate correct LIMIT and OFFSET clauses.'
        );

        $this->assertEquals(
            ['active'],
            $query->getBindings(),
            'QueryBuilder should bind values correctly for paginated queries.'
        );
    }

    public function testDeleteWithoutWhereThrowsException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("DELETE operation requires a WHERE clause.");

        $query = (new QueryBuilder())->from('users')->delete();
    }

    public function testWrapColumn(): void
    {
        $query = (new QueryBuilder())->setConnection($this->connection);
        $this->assertEquals('`users`.`id`', $query->testWrapColumn('users.id'));
        $this->assertEquals('`name`', $query->testWrapColumn('name'));
        $this->assertEquals('COUNT(*)', $query->testWrapColumn('COUNT(*)'));
    }

    public function testWrapAlias(): void
    {
        $query = (new QueryBuilder())->setConnection($this->connection);
        $this->assertEquals('`user_count`', $query->testWrapAlias('user_count'));
        $this->assertEquals('`count`', $query->testWrapAlias('count'));
    }

    public function testUpperFunction(): void
    {
        $query = (new QueryBuilder())
            ->setConnection($this->connection)
            ->from('users')
            ->upper('name', 'upper_name');

        $this->assertEquals(
            'SELECT UPPER(`name`) AS `upper_name` FROM `users`',
            $query->toSql(),
            'QueryBuilder should generate correct SQL for UPPER function.'
        );
    }

    public function testYearFunction(): void
    {
        $query = (new QueryBuilder())
            ->setConnection($this->connection)
            ->from('orders')
            ->year('created_at', 'order_year');

        $this->assertEquals(
            'SELECT YEAR(`created_at`) AS `order_year` FROM `orders`',
            $query->toSql(),
            'QueryBuilder should generate correct SQL for YEAR function.'
        );
    }

    public function testMonthFunction(): void
    {
        $query = (new QueryBuilder())
            ->setConnection($this->connection)
            ->from('orders')
            ->month('created_at', 'order_month');

        $this->assertEquals(
            'SELECT MONTH(`created_at`) AS `order_month` FROM `orders`',
            $query->toSql(),
            'QueryBuilder should generate correct SQL for MONTH function.'
        );
    }

    public function testDateFunction(): void
    {
        $query = (new QueryBuilder())
            ->setConnection($this->connection)
            ->from('orders')
            ->date('created_at', 'order_date');

        $this->assertEquals(
            'SELECT DATE(`created_at`) AS `order_date` FROM `orders`',
            $query->toSql(),
            'QueryBuilder should generate correct SQL for DATE function.'
        );
    }

    public function testJoinSubQuery(): void
    {
        $subQuery = (new QueryBuilder())
            ->setConnection($this->connection)
            ->select(['id', 'user_id'])
            ->from('orders')
            ->where('status', '=', 'completed');

        $query = (new QueryBuilder())
            ->setConnection($this->connection)
            ->from('users')
            ->joinSub($subQuery, 'completed_orders', 'users.id', '=', 'completed_orders.user_id');

        $this->assertEquals(
            'SELECT * FROM `users` INNER JOIN (SELECT `id`, `user_id` FROM `orders` WHERE `status` = ?) AS `completed_orders` ON `users`.`id` = `completed_orders`.`user_id`',
            $query->toSql(),
            'QueryBuilder should generate correct SQL for joinSub with a subquery.'
        );

        $this->assertEquals(
            ['completed'],
            $query->getBindings(),
            'QueryBuilder should bind values correctly for joinSub.'
        );
    }

    public function testWhereLike(): void
    {
        $query = (new QueryBuilder())
            ->setConnection($this->connection)
            ->from('users')
            ->whereLike('name', '%John%');

        $this->assertEquals(
            'SELECT * FROM `users` WHERE `name` LIKE ?',
            $query->toSql(),
            'QueryBuilder ska generera en SQL-sökfråga med WHERE LIKE-klausul.'
        );

        $this->assertEquals(
            ['%John%'],
            $query->getBindings(),
            'QueryBuilder ska korrekt binda värden för WHERE LIKE-klausul.'
        );
    }

    public function testWhereLikeWithMultipleConditions(): void
    {
        $query = (new QueryBuilder())
            ->setConnection($this->connection)
            ->from('users')
            ->whereLike('email', 'john%@example.com')
            ->orWhere('status', '=', 'active');

        $this->assertEquals(
            'SELECT * FROM `users` WHERE `email` LIKE ? OR `status` = ?',
            $query->toSql(),
            'QueryBuilder ska korrekt kombinera WHERE LIKE och OR.'
        );

        $this->assertEquals(
            ['john%@example.com', 'active'],
            $query->getBindings(),
            'QueryBuilder ska korrekt hantera bindningar med flera villkor.'
        );
    }

    public function testWhereNotInBetweenColumnExists(): void
    {
        $sub = (new QueryBuilder())
            ->setConnection($this->connection)
            ->select(['id'])
            ->from('payments')
            ->where('amount', '>', 100);

        $query = (new QueryBuilder())
            ->setConnection($this->connection)
            ->from('users')
            ->whereNotIn('role', ['admin', 'editor'])
            ->whereBetween('age', [18, 30])
            ->whereNotBetween('score', [50, 80])
            ->whereColumn('users.country_id', '=', 'countries.id')
            ->whereExists($sub);

        $this->assertEquals(
            'SELECT * FROM `users` WHERE `role` NOT IN (?, ?) AND `age` BETWEEN ? AND ? AND `score` NOT BETWEEN ? AND ? AND `users`.`country_id` = `countries`.`id` AND EXISTS (SELECT `id` FROM `payments` WHERE `amount` > ?)',
            $query->toSql()
        );
        $this->assertEquals(
            ['admin', 'editor', 18, 30, 50, 80, 100],
            $query->getBindings()
        );
    }

    public function testWhereRaw(): void
    {
        $query = (new QueryBuilder())
            ->setConnection($this->connection)
            ->from('users')
            ->whereRaw('(`first_name` LIKE ? OR `last_name` LIKE ?)', ['%ma%', '%ma%']);

        $this->assertEquals(
            'SELECT * FROM `users` WHERE ((`first_name` LIKE ? OR `last_name` LIKE ?))',
            $query->toSql()
        );
        $this->assertEquals(['%ma%', '%ma%'], $query->getBindings());
    }

    public function testOrderByRawAndHavingRaw(): void
    {
        $query = (new QueryBuilder())
            ->setConnection($this->connection)
            ->select(['role', 'COUNT(*) AS total'])
            ->from('users')
            ->groupBy('role')
            ->havingRaw('COUNT(*) > ?', [5])
            ->orderByRaw('FIELD(role, "admin","editor","user")');

        $this->assertEquals(
            'SELECT `role`, COUNT(*) AS `total` FROM `users` GROUP BY `role` HAVING COUNT(*) > ? ORDER BY FIELD(role, "admin","editor","user")',
            $query->toSql()
        );
        $this->assertEquals([5], $query->getBindings());
    }

    public function testRightJoinAndJoinRaw(): void
    {
        $query = (new QueryBuilder())
            ->setConnection($this->connection)
            ->from('users')
            ->rightJoin('profiles', 'users.id', '=', 'profiles.user_id')
            ->joinRaw('INNER JOIN `roles` ON `roles`.`id` = `users`.`role_id` AND `roles`.`name` = ?', ['admin']);

        $this->assertEquals(
            'SELECT * FROM `users` RIGHT JOIN `profiles` ON `users`.`id` = `profiles`.`user_id` INNER JOIN `roles` ON `roles`.`id` = `users`.`role_id` AND `roles`.`name` = ?',
            $query->toSql()
        );
        $this->assertEquals(['admin'], $query->getBindings());
    }

    public function testUnionAllWrapper(): void
    {
        $q1 = (new QueryBuilder())
            ->setConnection($this->connection)
            ->select(['id'])
            ->from('users')
            ->where('status', '=', 'active');

        $q2 = (new QueryBuilder())
            ->setConnection($this->connection)
            ->select(['id'])
            ->from('archived_users')
            ->where('status', '=', 'active');

        $union = $q1->unionAll($q2);

        $this->assertEquals(
            'SELECT `id` FROM `users` WHERE `status` = ? UNION ALL SELECT `id` FROM `archived_users` WHERE `status` = ?',
            $union->toSql()
        );
        $this->assertEquals(['active', 'active'], $union->getBindings());
    }

    public function testSelectSub(): void
    {
        $sub = (new QueryBuilder())
            ->setConnection($this->connection)
            ->select(['COUNT(*) as c'])
            ->from('orders')
            ->where('orders.user_id', '=', 10);

        $query = (new QueryBuilder())
            ->setConnection($this->connection)
            ->from('users')
            ->selectSub($sub, 'order_count');

        $this->assertEquals(
            'SELECT (SELECT COUNT(*) AS `c` FROM `orders` WHERE `orders`.`user_id` = ?) AS `order_count` FROM `users`',
            $query->toSql()
        );
        $this->assertEquals([10], $query->getBindings());
    }

    public function testValueAndPluck(): void
    {
        // value(): vi kontrollerar endast genererad SQL före fetch, via debugSql-logik är svår utan stub.
        $qValue = (new QueryBuilder())
            ->setConnection($this->connection)
            ->from('users')
            ->where('id', '=', 1);
        // Bygg SQL för value (select+limit)
        $qValue->select(['email'])->limit(1);
        $this->assertEquals(
            'SELECT `email` FROM `users` WHERE `id` = ? LIMIT 1',
            $qValue->toSql()
        );
        $this->assertEquals([1], $qValue->getBindings());

        $qPluck = (new QueryBuilder())
            ->setConnection($this->connection)
            ->from('users')
            ->where('status', '=', 'active')
            ->select(['email']);
        $this->assertEquals(
            'SELECT `email` FROM `users` WHERE `status` = ?',
            $qPluck->toSql()
        );
        $this->assertEquals(['active'], $qPluck->getBindings());
    }

    public function testOnlyAndWithoutTrashed(): void
    {
        $q1 = (new QueryBuilder())
            ->setConnection($this->connection)
            ->from('users')
            ->onlyTrashed();

        $this->assertEquals(
            'SELECT * FROM `users` WHERE `deleted_at` IS NOT NULL',
            $q1->toSql()
        );

        $q2 = (new QueryBuilder())
            ->setConnection($this->connection)
            ->from('users')
            ->withoutTrashed();

        $this->assertEquals(
            'SELECT * FROM `users` WHERE `deleted_at` IS NULL',
            $q2->toSql()
        );
    }

    public function testWithCteSimpleSelect(): void
    {
        $sub = (new QueryBuilder())
            ->setConnection($this->connection)
            ->select(['id', 'name'])
            ->from('users')
            ->where('status', '=', 'active');

        $q = (new QueryBuilder())
            ->setConnection($this->connection)
            ->from('active_users') // huvuddelen ska läsa från CTE-alias
            ->withCte('active_users', $sub)
            ->select(['name'])
            ->orderBy('name');

        $this->assertEquals(
            'WITH `active_users` AS (SELECT `id`, `name` FROM `users` WHERE `status` = ?) SELECT `name` FROM `active_users` ORDER BY `name` ASC',
            $q->toSql()
        );
        $this->assertEquals(['active'], $q->getBindings());
    }

    public function testWithCteMultiple(): void
    {
        $totals = (new QueryBuilder())
            ->setConnection($this->connection)
            ->select(['user_id', 'SUM(amount) AS total'])
            ->from('payments')
            ->groupBy('user_id');

        $users = (new QueryBuilder())
            ->setConnection($this->connection)
            ->select(['id', 'email'])
            ->from('users');

        $q = (new QueryBuilder())
            ->setConnection($this->connection)
            ->withCte('totals', $totals)
            ->withCte('u', $users)
            ->from('u')
            ->select(['u.email', 'totals.total'])
            ->join('totals', 'u.id', '=', 'totals.user_id');

        $this->assertEquals(
            'WITH `totals` AS (SELECT `user_id`, SUM(amount) AS `total` FROM `payments` GROUP BY `user_id`), `u` AS (SELECT `id`, `email` FROM `users`) SELECT `u`.`email`, `totals`.`total` FROM `u` INNER JOIN `totals` ON `u`.`id` = `totals`.`user_id`',
            $q->toSql()
        );
        $this->assertEquals([], $q->getBindings());
    }

    public function testWithRecursiveCte(): void
    {
        $anchor = (new QueryBuilder())
            ->setConnection($this->connection)
            ->select(['id', 'parent_id', 'name', '0 AS depth'])
            ->from('categories')
            ->where('id', '=', 123);

        $recursive = (new QueryBuilder())
            ->setConnection($this->connection)
            ->select(['c.id', 'c.parent_id', 'c.name', 'p.depth + 1'])
            ->from('categories AS c')
            ->join('parents AS p', 'p.id', '=', 'c.parent_id');

        $q = (new QueryBuilder())
            ->setConnection($this->connection)
            ->withRecursive('parents', $anchor, $recursive, ['id', 'parent_id', 'name', 'depth'])
            ->from('parents')
            ->select(['id', 'name', 'depth'])
            ->orderBy('depth');

        $expected = 'WITH RECURSIVE `parents` (`id`, `parent_id`, `name`, `depth`) AS (SELECT `id`, `parent_id`, `name`, 0 AS `depth` FROM `categories` WHERE `id` = ? UNION ALL SELECT `c`.`id`, `c`.`parent_id`, `c`.`name`, p.depth + 1 FROM `categories` AS `c` INNER JOIN parents AS p ON `p`.`id` = `c`.`parent_id`) SELECT `id`, `name`, `depth` FROM `parents` ORDER BY `depth` ASC';
        $this->assertEquals($expected, $q->toSql());
        $this->assertEquals([123], $q->getBindings());
    }

    public function testSelectForUpdate(): void
    {
        $q = (new QueryBuilder())
            ->setConnection($this->connection)
            ->from('users')
            ->where('id', '=', 1)
            ->forUpdate();

        $this->assertEquals(
            'SELECT * FROM `users` WHERE `id` = ? FOR UPDATE',
            $q->toSql()
        );
        $this->assertEquals([1], $q->getBindings());
    }

    public function testSelectLockInShareModeWithLimit(): void
    {
        $q = (new QueryBuilder())
            ->setConnection($this->connection)
            ->select(['name'])
            ->from('users')
            ->where('status', '=', 'active')
            ->orderBy('name')
            ->limit(10)
            ->lockInShareMode();

        $this->assertEquals(
            'SELECT `name` FROM `users` WHERE `status` = ? ORDER BY `name` ASC LIMIT 10 LOCK IN SHARE MODE',
            $q->toSql()
        );
        $this->assertEquals(['active'], $q->getBindings());
    }

    public function testRowNumberWindow(): void
    {
        $q = (new QueryBuilder())
            ->setConnection($this->connection)
            ->from('posts')
            ->select([]) // säkerställ att '*' inte används
            ->rowNumber('row_num', [], [['created_at', 'DESC']]);

        $this->assertEquals(
            'SELECT ROW_NUMBER() OVER (ORDER BY `created_at` DESC) AS `row_num` FROM `posts`',
            $q->toSql()
        );
    }

    public function testRankPartitionedWindow(): void
    {
        $q = (new QueryBuilder())
            ->setConnection($this->connection)
            ->from('scores')
            ->select(['user_id'])
            ->rank('r', ['user_id'], [['score', 'DESC']]);

        $this->assertEquals(
            'SELECT `user_id`, RANK() OVER (PARTITION BY `user_id` ORDER BY `score` DESC) AS `r` FROM `scores`',
            $q->toSql()
        );
    }

    public function testSumOverRunningTotal(): void
    {
        $q = (new QueryBuilder())
            ->setConnection($this->connection)
            ->from('payments')
            ->select(['user_id', 'created_at'])
            ->sumOver('amount', 'running_total', ['user_id'], [['created_at', 'ASC']]);

        $this->assertEquals(
            'SELECT `user_id`, `created_at`, SUM(`amount`) OVER (PARTITION BY `user_id` ORDER BY `created_at` ASC) AS `running_total` FROM `payments`',
            $q->toSql()
        );
    }

    public function testWindowRaw(): void
    {
        $q = (new QueryBuilder())
            ->setConnection($this->connection)
            ->from('leaderboard')
            ->select(['id'])
            ->windowRaw('NTILE(4) OVER (ORDER BY `score` DESC)', 'quart');

        $this->assertEquals(
            'SELECT `id`, NTILE(4) OVER (ORDER BY `score` DESC) AS `quart` FROM `leaderboard`',
            $q->toSql()
        );
    }

    public function testCteWithUpdateMutation(): void
    {
        $cte = (new QueryBuilder())
            ->setConnection($this->connection)
            ->select(['id'])
            ->from('users')
            ->where('email', 'LIKE', '%@example.com');

        $q = (new QueryBuilder())
            ->setConnection($this->connection)
            ->from('users')
            ->withCte('to_fix', $cte)
            ->where(
                'id',
                'IN',
                (new QueryBuilder())
                ->setConnection($this->connection)
                ->select(['id'])
                ->from('to_fix')
            )
            ->update(['status' => 'inactive']);

        $this->assertEquals(
            'WITH `to_fix` AS (SELECT `id` FROM `users` WHERE `email` LIKE ?) UPDATE `users` SET `status` = ? WHERE `id` IN (SELECT `id` FROM `to_fix`)',
            $q->toSql()
        );
        $this->assertEquals(['%@example.com', 'inactive'], $q->getBindings());
    }

    public function testCaseWhenSelectAndOrderByCase(): void
    {
        $q = (new QueryBuilder())
            ->setConnection($this->connection)
            ->from('users')
            ->select([])
            ->caseWhen([
                ['cond' => '`role` = ?', 'bindings' => ['admin'], 'then' => "'A'"],
                ['cond' => '`role` = ?', 'bindings' => ['editor'], 'then' => "'B'"],
            ], "'Z'", 'rnk')
            ->orderByCase('role', ['admin' => 1, 'editor' => 2, 'user' => 3], '9', 'ASC');

        $sql = $q->toSql();
        $this->assertStringContainsString("SELECT CASE WHEN (`role` = ?) THEN 'A' WHEN (`role` = ?) THEN 'B' ELSE 'Z' END AS `rnk` FROM `users`", $sql);
        $this->assertStringContainsString("ORDER BY CASE `role` WHEN ? THEN 1 WHEN ? THEN 2 WHEN ? THEN 3 ELSE '9' END ASC", $sql);
        $this->assertEquals(['admin','editor','admin','editor','user'], $q->getBindings());
    }

    public function testInsertSelect(): void
    {
        $sel = (new QueryBuilder())
            ->setConnection($this->connection)
            ->select(['id', 'email'])
            ->from('users')
            ->where('status', '=', 'active');

        $q = (new QueryBuilder())
            ->setConnection($this->connection)
            ->insertSelect('newsletter', ['user_id', 'email'], $sel);

        $this->assertEquals(
            'INSERT INTO `newsletter` (`user_id`, `email`) SELECT `id`, `email` FROM `users` WHERE `status` = ?',
            $q->toSql()
        );
        $this->assertEquals(['active'], $q->getBindings());
    }

    public function testJsonExtractAndWhereJsonContains(): void
    {
        $q = (new QueryBuilder())
            ->setConnection($this->connection)
            ->from('products')
            ->select([])
            ->jsonExtract('meta', '$.brand', 'brand')
            ->whereJsonContains('tags', 'sale');

        $sql = $q->toSql();

        // Vi kör SQLite i testerna, så vi förväntar sqlite-syntax
        $this->assertStringContainsString(
            'SELECT json_extract(`meta`, ?) AS `brand` FROM `products`',
            $sql
        );
        $this->assertStringContainsString(
            'WHERE `tags` LIKE ?',
            $sql
        );

        // WHERE-bindningen ("%sale%") kommer före SELECT-bindningen ("$.brand")
        $this->assertEquals(
            ['%sale%', '$.brand'],
            $q->getBindings()
        );
    }

    public function testRollupGroupBy(): void
    {
        $q = (new QueryBuilder())
            ->setConnection($this->connection)
            ->from('orders')
            ->select(['customer_id', 'COUNT(*) AS cnt'])
            ->rollup(['customer_id']);

        $this->assertEquals(
            'SELECT `customer_id`, COUNT(*) AS `cnt` FROM `orders` GROUP BY `customer_id` WITH ROLLUP',
            $q->toSql()
        );
    }

    public function testDoesntExist(): void
    {
        $mock = $this->createMock(Connection::class);
        // exists() bygger en COUNT(1)-liknande SELECT 1; vi behöver bara returnera null för att simulera "inget resultat"
        $mock->method('fetchOne')->willReturn(null);

        $q = (new QueryBuilder())
            ->setConnection($mock)
            ->from('users')
            ->where('id', '=', -9999);

        $this->assertTrue($q->doesntExist());
    }

    public function testFirstOrFailThrows(): void
    {
        $mock = $this->createMock(Connection::class);
        // first() gör en SELECT LIMIT 1 och använder fetchAll/fetchOne via dina lager.
        // Mocka så att inga rader hittas.
        $mock->method('fetchAll')->willReturn([]);
        $mock->method('fetchOne')->willReturn(null);

        // Minimal modellklass som uppfyller kraven (extends Model)
        $model = new class extends \Radix\Database\ORM\Model {
            protected string $table = 'users';
            /** @var array<int,string> */
            protected array $fillable = ['id'];
        };

        $q = (new QueryBuilder())
            ->setConnection($mock)
            ->from('users')
            ->setModelClass(get_class($model))
            ->where('id', '=', -9999)
            ->limit(1);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No records found for firstOrFail().');
        $q->firstOrFail();
    }

    public function testWhenAndTap(): void
    {
        $q = (new QueryBuilder())
            ->setConnection($this->connection)
            ->from('users')
            ->when(true, function (QueryBuilder $b) {
                $b->where('active', '=', 1);
            })
            ->tap(function (QueryBuilder $b) {
                $this->assertStringContainsString('users', $b->toSql());
            });

        $this->assertStringContainsString('`active` = ?', $q->toSql());
    }

    public function testOrderByDescLatestOldest(): void
    {
        $q1 = (new QueryBuilder())
            ->setConnection($this->connection)
            ->from('users')
            ->orderByDesc('created_at');
        $this->assertStringContainsString('ORDER BY `created_at` DESC', $q1->toSql());

        $q2 = (new QueryBuilder())
            ->setConnection($this->connection)
            ->from('users')
            ->latest();
        $this->assertStringContainsString('ORDER BY `created_at` DESC', $q2->toSql());

        $q3 = (new QueryBuilder())
            ->setConnection($this->connection)
            ->from('users')
            ->oldest();
        $this->assertStringContainsString('ORDER BY `created_at` ASC', $q3->toSql());
    }

    public function testDebugSqlHelpers(): void
    {
        $q = (new QueryBuilder())
            ->setConnection($this->connection)
            ->from('users')
            ->where('email', '=', 'john@example.com');

        $paramSql = $q->debugSql();
        $interpSql = $q->debugSqlInterpolated();

        $this->assertSame('SELECT * FROM `users` WHERE `email` = ?', $paramSql);
        $this->assertSame("SELECT * FROM `users` WHERE `email` = 'john@example.com'", $interpSql);
    }

    public function testSimplePaginate(): void
    {
        $mock = $this->createMock(Connection::class);

        // Simulera tre rader (den tredje används bara för has_more)
        $mock->method('fetchAll')->willReturn([
            ['id' => 1, 'name' => 'A'],
            ['id' => 2, 'name' => 'B'],
            ['id' => 3, 'name' => 'C'],
        ]);

        // Minimal modellklass för hydrering
        $model = new class extends \Radix\Database\ORM\Model {
            protected string $table = 'users';
            /** @var array<int,string> */
            protected array $fillable = ['id', 'name'];
        };

        $q = (new QueryBuilder())
            ->setConnection($mock)
            ->from('users')
            ->setModelClass(get_class($model));

        $page = $q->simplePaginate(2, 1);

        $this->assertArrayHasKey('data', $page);
        $this->assertIsArray($page['data']);
        $this->assertCount(2, $page['data']); // extra raden ska kapas
        $this->assertArrayHasKey('pagination', $page);
        $this->assertTrue($page['pagination']['has_more']);
    }

    public function testPaginateReturnsArrayData(): void
    {
        $mock = $this->createMock(Connection::class);

        // COUNT(*) as total
        $mock->method('fetchOne')->willReturn(['total' => 3]);

        // Data-hämtning
        $mock->method('fetchAll')->willReturn([
            ['id' => 1, 'name' => 'A'],
            ['id' => 2, 'name' => 'B'],
        ]);

        // Minimal modellklass för hydrering
        $model = new class extends \Radix\Database\ORM\Model {
            protected string $table = 'users';
            /** @var array<int,string> */
            protected array $fillable = ['id', 'name'];
        };

        $q = (new QueryBuilder())
            ->setConnection($mock)
            ->from('users')
            ->setModelClass(get_class($model));

        $page = $q->paginate(2, 1);

        $this->assertArrayHasKey('data', $page);
        $this->assertIsArray($page['data'], 'paginate() ska returnera data som array, ej Collection.');
        $this->assertCount(2, $page['data']);
        $this->assertArrayHasKey('pagination', $page);
        $this->assertSame(3, $page['pagination']['total']);
        $this->assertSame(2, $page['pagination']['per_page']);
        $this->assertSame(1, $page['pagination']['current_page']);
    }

    public function testChunkIterates(): void
    {
        $mock = $this->createMock(Connection::class);

        // Första chunk (size=2): returnera 2 rader
        // Andra chunk: returnera 1 rad -> avsluta
        $mock->method('fetchAll')->willReturnOnConsecutiveCalls(
            [
                ['id' => 1, 'name' => 'A'],
                ['id' => 2, 'name' => 'B'],
            ],
            [
                ['id' => 3, 'name' => 'C'],
            ]
        );

        $model = new class extends \Radix\Database\ORM\Model {
            protected string $table = 'users';
            /** @var array<int,string> */
            protected array $fillable = ['id', 'name'];
        };

        $q = (new QueryBuilder())
            ->setConnection($mock)
            ->from('users')
            ->setModelClass(get_class($model))
            ->orderBy('id'); // deterministisk ordning

        $countCalls = 0;
        $q->chunk(2, function (\Radix\Collection\Collection $chunk, int $page) use (&$countCalls) {
            $this->assertGreaterThan(0, $chunk->count());
            $this->assertSame($countCalls + 1, $page);
            $countCalls++;
        });

        $this->assertSame(2, $countCalls);
    }

    public function testLazyYields(): void
    {
        $mock = $this->createMock(Connection::class);

        // Simulera två batchar: första batchen 2 rader, andra batchen 1 rad, sedan tomt
        $mock->method('fetchAll')->willReturnOnConsecutiveCalls(
            [
                ['id' => 1, 'name' => 'A'],
                ['id' => 2, 'name' => 'B'],
            ],
            [
                ['id' => 3, 'name' => 'C'],
            ],
            [] // tredje anropet stoppar generatorn
        );

        $model = new class extends \Radix\Database\ORM\Model {
            protected string $table = 'users';
            /** @var array<int,string> */
            protected array $fillable = ['id', 'name'];
        };

        $q = (new QueryBuilder())
            ->setConnection($mock)
            ->from('users')
            ->setModelClass(get_class($model))
            ->orderBy('id');

        $gen = $q->lazy(2);
        $this->assertInstanceOf(Generator::class, $gen);

        $collected = [];
        foreach ($gen as $m) {
            $collected[] = $m->getAttribute('name');
        }

        $this->assertSame(['A','B','C'], $collected);
    }
}
