# Radix System med ORM
<!-- START doctoc generated TOC please keep comment here to allow auto update -->
<!-- DON'T EDIT THIS SECTION, INSTEAD RE-RUN doctoc TO UPDATE -->
<!-- doctoc will insert TOC here -->

- [Installation](#installation)
- [Snabbstart](#snabbstart)
- [API-översikt](#api-%C3%B6versikt)
  - [Select och alias](#select-och-alias)
  - [From](#from)
  - [Where/Filter](#wherefilter)
  - [Subqueries (WHERE EXISTS/IN)](#subqueries-where-existsin)
  - [Joins](#joins)
  - [Group/Having/Order](#grouphavingorder)
  - [Union](#union)
  - [Pagination och sök](#pagination-och-s%C3%B6k)
  - [Snabba hämtningar](#snabba-h%C3%A4mtningar)
  - [Mutationer](#mutationer)
  - [Soft deletes](#soft-deletes)
  - [Eager loading och aggregat](#eager-loading-och-aggregat)
  - [Debugging](#debugging)
- [Traits-översikt](#traits-%C3%B6versikt)
- [Design: Bindnings-buckets](#design-bindnings-buckets)
- [Säkerhet och validering](#s%C3%A4kerhet-och-validering)
- [Testning](#testning)
- [Vanliga frågor](#vanliga-fr%C3%A5gor)
- [Licens](#licens)

<!-- END doctoc generated TOC please keep comment here to allow auto update -->

Radix System med ORM erbjuder en lättvikts QueryBuilder med säkra bindnings-buckets och enkel modell-hydrering. Stöd för WHERE/JOINS/UNION/AGGREGAT, pagination/sök, soft deletes, eager loading och mutationer (insert/update/delete/upsert).

## Installation
- Kräver PHP 8.3+, PDO (MySQL/SQLite m.fl.)
- Installera dependencies via Composer och konfigurera databaskoppling i app-container/DatabaseManager.
- Kör migrationer via ditt migrationssystem.

## Snabbstart

php use App\Models\User;
// Hämta användare (returnerar Collection av modeller) $users = User::query() ->select(['id','name']) ->where('status', '=', 'active') ->orderBy('name') ->limit(10)->offset(0) ->get();
// Enskilt värde $email = User::query()->where('id', '=', 1)->value('email');
// Lista/assoc-lista emails = User::query()->where('status','=','active')->pluck('email');emailsById = User::query()->pluck('email', 'id');
// Arbeta med Collection names =users->pluck('name')->values()->toArray(); firstActive =users->first();

## API-översikt
- Alla metoder returnerar samma builderinstans (chainable).
- toSql() returnerar SQL med placeholders.
- getBindings() returnerar bindningar i rätt ordning.
- get() hydratiserar till Model-exemplar och returnerar en Collection.
- Relationers get(): many-relationer returnerar array, one-relationer returnerar Model|null (bakåtkompatibelt).

### Select och alias

php User::query()->select(['id','users.name','users.name AS user_name']); User::query()->selectRaw('COUNT(id) AS total');

sub = User::query()->select(['COUNT(*) as c'])->from('orders')->where('orders.user_id','=',10); User::query()->from('users')->selectSub(sub, 'order_count');

### From

php User::query()->from('users AS u'); User::query()->fromRaw('(SELECT * FROM users WHERE active = 1) AS u');

### Where/Filter

php $q = User::query()->from('users') ->where('status', '=', 'active') ->orWhere('role', '=', 'moderator') ->whereIn('id', [1,2,3]) ->whereNotIn('role', ['admin','editor']) ->whereBetween('age', [18,30]) ->whereNotBetween('score', [50,80]) ->whereColumn('users.country_id', '=', 'countries.id') ->whereNull('deleted_at') ->whereNotNull('joined_at') ->whereRaw('(`first_name` LIKE ? OR `last_name` LIKE ?)', ['%ma%','%ma%']) ->whereLike('email', '%@example.com');

### Subqueries (WHERE EXISTS/IN)

php sub = User::query()->select(['id'])->from('payments')->where('amount','>',100); User::query()->from('users')->whereExists(sub); User::query()->from('users')->where('id', 'IN', $sub);

### Joins

php User::query()->from('users') ->join('profiles', 'users.id', '=', 'profiles.user_id') ->leftJoin('orders', 'users.id', '=', 'orders.user_id') ->rightJoin('roles', 'users.role_id', '=', 'roles.id') ->fullJoin('addresses', 'users.id', '=', 'addresses.user_id') ->joinRaw('INNER JOIN `teams` ON `teams`.`id` = `users`.`team_id` AND `teams`.`active` = ?', [1]);

sub = User::query()->select(['id','user_id'])->from('orders')->where('status','=','completed'); User::query()->from('users')->joinSub(sub, 'completed_orders', 'users.id', '=', 'completed_orders.user_id');

### Group/Having/Order

php User::query()->from('users') ->groupBy('role') ->having('total', '>', 10) // om du har "COUNT(_) AS total" i select ->havingRaw('COUNT(_) > ?', [5]) ->orderBy('name', 'ASC') ->orderByRaw('FIELD(role, "admin","editor","user")');

### Union

php q1 = User::query()->select(['id','name'])->from('users')->where('status','=','active');q2 = User::query()->select(['id','name'])->from('archived_users')->where('status','=','active');

q1->union(q2); // UNION q1->unionAll(q2); // UNION ALL

### Pagination och sök

php // Klassisk pagination $result = User::query() ->where('status','=','active') ->paginate(perPage: 10, currentPage: 2); // ['data' => array, 'pagination' => [...]]

// Sök (LIKE i flera kolumner + pagination) $search = User::query() ->search('ma', ['first_name','last_name','email'], perPage: 10, currentPage: 1); // ['data'=>array,'search'=>[...]]

// Enkel pagination utan total $simple = User::query()->simplePaginate(10, 1);

### Snabba hämtningar

php // Första värdet i första raden (eller null) $email = User::query()->where('id','=',1)->value('email');

// Lista/assoc av kolumn emails = User::query()->pluck('email');emailsById = User::query()->pluck('email', 'id');

### Mutationer

php // Insert User::query()->from('users')->insert([ 'name' => 'John Doe', 'email' => 'john@example.com', ])->execute();

// Update User::query()->from('users')->where('id','=',1) ->update(['name' => 'Jane Doe', 'email' => 'jane@example.com']) ->execute();

// Delete (kräver WHERE) User::query()->from('users')->where('id','=',1)->delete()->execute();

// Insert or ignore User::query()->from('users')->insertOrIgnore(['email'=>'dup@example.com'])->execute();

// Upsert User::query()->from('users') ->upsert(['email' => 'a@example.com', 'name' => 'A'], uniqueBy: ['email']) ->execute();

### Soft deletes

php // Default (om modellen använder soft deletes): filtrerar bort deleted_at != null User::query()->whereNull('deleted_at');

// Visa även soft-deletade User::query()->withSoftDeletes();

// Endast soft-deletade (alias) User::query()->onlyTrashed(); // alias till getOnlySoftDeleted()

// Endast icke soft-deletade (explicit) User::query()->withoutTrashed();

### Eager loading och aggregat

php // Eager load User::query()->with(['profile', 'posts']);

// Med constraints User::query()->with(['posts' => function (\Radix\Database\QueryBuilder\QueryBuilder q) {q->where('published', '=', 1); }]);

// withCount / withSum / withAvg / withMin / withMax / withAggregate User::query()->withCount('posts')->withSum('posts','views','posts_views');

### Debugging

php q = User::query()->where('name','LIKE','%John%');sql = q->toSql(); // SELECT ... WHERE `name` LIKE ?bindings = $q->getBindings(); // ['%John%'] // debugSql() interpolerar bindningar till läsbar sträng (endast utveckling)

## Traits-översikt
Följande traits i QueryBuilder (framework/src/Database/QueryBuilder/Concerns) modulariserar funktionaliteten:
- Bindings, BuildsWhere, Joins, Ordering, CompilesSelect, CompilesMutations, Unions, Pagination,
  SoftDeletes, EagerLoad, WithCount, WithAggregate, WithCtes, Windows, Wrapping, Functions,
  Locks, Transactions, CaseExpressions, InsertSelect, JsonFunctions, GroupingSets.

Observera: Inte alla funktioner kanske stöds av varje databasdialekt; använd dialektens capability där relevant.

## Design: Bindnings-buckets
- Bindningar hanteras i separata buckets: select, join, where, having, order, union, mutation.
- compileAllBindings() körs i toSql()/compileMutationSql() för att platta ihop bindningar i rätt ordning:
  - mutation (SET/VALUES) först (viktigt för UPDATE), därefter select/join/where/having/order/union.
- Fördel: inga krockar när SQL byggs i flera steg (t.ex. subqueries, joinRaw, havingRaw).

## Säkerhet och validering
- where() validerar operatorer.
- delete() utan WHERE kastar RuntimeException.
- wrapColumn/alias säkrar quoting; använd selectRaw/whereRaw/havingRaw/joinRaw med medföljande bindningar när du behöver fri SQL.

## Testning
- PHPUnit: kör vendor/bin/phpunit.
- PHPStan: kör vendor/bin/phpstan analyse.
- Samtliga core-tester för QueryBuilder ska passera (inkl. de nya för whereNotIn/between/exists/column/orderByRaw/havingRaw/rightJoin/joinRaw/unionAll/selectSub/value/pluck/soft-delete-aliaser).
- Collection: se tester under framework/tests/Collection för exempel på API och integration (map/filter/first/pluck/values m.m.).

## Vanliga frågor
- Varför ser jag backticks runt alias (AS `alias`)? Builder wrappar alias konsekvent för att undvika krockar med reserverade ord. Testa mot DB-dialekten – MySQL/SQLite accepterar detta.
- Varför returnerar get() en Collection nu? För ett rikare, kedjbart API. Behöver du array i t.ex. pagination/search-respons, konverteras Collection till array automatiskt.
- Påverkas relationer av Collection? Nej. Relationernas returtyper är oförändrade (arrays för “many”, Model|null för “one”) för bakåtkompatibilitet.

## Licens
MIT (eller den licens du använder för projektet).

