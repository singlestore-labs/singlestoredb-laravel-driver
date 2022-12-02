# SingleStore Driver for Laravel <!-- omit in toc -->

[![Latest Stable Version](http://poser.pugx.org/singlestoredb/singlestoredb-laravel/v)](https://packagist.org/packages/singlestoredb/singlestoredb-laravel) [![Total Downloads](http://poser.pugx.org/singlestoredb/singlestoredb-laravel/downloads)](https://packagist.org/packages/singlestoredb/singlestoredb-laravel) [![License](http://poser.pugx.org/singlestoredb/singlestoredb-laravel/license)](https://packagist.org/packages/singlestoredb/singlestoredb-laravel) [![PHP Version Require](http://poser.pugx.org/singlestoredb/singlestoredb-laravel/require/php)](https://packagist.org/packages/singlestoredb/singlestoredb-laravel) [![Github Actions status image](https://github.com/singlestore-labs/singlestoredb-laravel-driver/actions/workflows/tests.yml/badge.svg)](https://github.com/singlestore-labs/singlestoredb-laravel-driver/actions)

This repository contains a SingleStore Driver for Laravel.

This package is currently in a pre-release beta, please use with caution and open any issues that you run into.

- [Install](#install)
- [Usage](#usage)
- [Issues connecting to SingleStore Managed Service](#issues-connecting-to-singlestore-managed-service)
- [Persistent Connections (performance optimization)](#persistent-connections-performance-optimization)
- [PHP Versions before 8.1](#php-versions-before-81)
- [Migrations](#migrations)
  - [Universal Storage Tables (Columnstore)](#universal-storage-tables-columnstore)
  - [Rowstore Tables](#rowstore-tables)
  - [Reference Tables](#reference-tables)
  - [Global Temporary Tables](#global-temporary-tables)
  - [Sparse Columns](#sparse-columns)
  - [Sparse Tables](#sparse-tables)
  - [Shard Keys](#shard-keys)
  - [Sort Keys](#sort-keys)
  - [Unique Keys](#unique-keys)
  - [Hash Keys](#hash-keys)
  - [Series Timestamps](#series-timestamps)
  - [Computed Columns](#computed-columns)
  - [Increment Columns without Primary Key](#increment-columns-without-primary-key)
  - [Full-text search](#full-text-search-using-fulltext-indexes)
- [Testing](#testing)
- [License](#license)
- [Resources](#resources)
- [User agreement](#user-agreement)

## Install

You can install the package via composer:

```shell
composer require singlestoredb/singlestoredb-laravel
```

**This package requires pdo_mysql** to be installed. If you aren't sure check to see if `pdo_mysql` is listed when you run `php -i`.

## Usage

To enable the driver, head to your `config/database.php` file and create a new entry for SingleStore in your `connections`, and update your `default` to point to that new connection:

```php
[
    'default' => env('DB_CONNECTION', 'singlestore'),

    'connections' => [
        'singlestore' => [
            'driver' => 'singlestore',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST'),
            'port' => env('DB_PORT'),
            'database' => env('DB_DATABASE'),
            'username' => env('DB_USERNAME'),
            'password' => env('DB_PASSWORD'),
            'unix_socket' => env('DB_SOCKET'),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
                PDO::ATTR_EMULATE_PREPARES => true,
            ]) : [],
        ],
    ]
]
```

> The SingleStore driver is an extension of the MySQL driver, so you could also just change your `driver` from `mysql` to `singlestore`.

In case you want to store failed jobs in SingleStore, then make sure you also set it as the `database` in your `config/queue.php` file. At which point, you may actually prefer to set `DB_CONNECTION='singlestore'` in your environment variables.

```php
'failed' => [
    'driver' => env('QUEUE_FAILED_DRIVER', 'database-uuids'),
    'database' => env('DB_CONNECTION', 'singlestore'),
    'table' => 'failed_jobs',
],
```

## Issues connecting to SingleStore Managed Service

If you are encountering issues connecting to the SingleStore Managed Service, it may be due to your environment not being able to verify the SSL certificate used to secure connections. You can fix this by downloading and manually specifying the SingleStore certificate file.

* [Download the file here][singlestore-pem]
* In the Laravel SingleStore connection configuration, point the variable `PDO::MYSQL_ATTR_SSL_CA` at `singlestore_bundle.pem`:

```php
'options' => extension_loaded('pdo_mysql') ? array_filter([
    PDO::MYSQL_ATTR_SSL_CA => 'path/to/singlestore_bundle.pem',
    PDO::ATTR_EMULATE_PREPARES => true,
]) : [],
```

## Persistent Connections (performance optimization)

In general, we recommend enabling `PDO::ATTR_PERSISTENT` when connecting to SingleStoreDB. This is because opening new connections to SingleStoreDB is very expensive compared to running many transactional queries. By using `PDO::ATTR_PERSISTENT`, you can greatly improve the performance of transactional workloads.

The only downside to using persistent connections is that you need to ensure that transactions are correctly cleaned up as well as being careful when changing session variables or the context database. [You can read more about this feature in the official documentation on php.net][attr_persistent].

Also, note that SingleStoreDB in it's default configuration can handle very large numbers of idle connections with no performance impact. The default is roughly 100,000 idle connections per aggregator, but that can be set much higher if your server can handle it.

To enable this feature, simply update your options to include `PDO::ATTR_PERSISTENT => true`:

```php
'options' => extension_loaded('pdo_mysql') ? array_filter([
    PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
    PDO::ATTR_EMULATE_PREPARES => true,
    PDO::ATTR_PERSISTENT => true,
]) : [],
```

## PHP Versions before 8.1

In PHP versions before 8.1, the flag `PDO::ATTR_EMULATE_PREPARES` results in a bug by which all attributes returned by MySQL (and
SingleStoreDB) are returned as strings.

For example, a table with a column named `user_id` and a type of `int(10)`, if the row value is `5423` we would
get a string like `"5423"` in PHP.

This is a historic and known bug:

- https://stackoverflow.com/a/58830039/3275796
- https://github.com/php/php-src/blob/7b34db0659dda933b1146a0ff249f25acca1d669/UPGRADING#L130-L134

The best method to solve this is to upgrade to PHP 8.1 or higher. If that's not possible, [Eloquent's attribute casting] is the next best solution. 

## Migrations

This driver provides many SingleStore specific methods for creating or modifying tables. They are listed below. For more information see the [create table] docs on SingleStore.

### Universal Storage Tables (Columnstore)

By default, tables created by this driver will use [SingleStoreDB Universal Storage][columnstore]. Universal Storage leverages both column and row oriented data structures to automatically optimize storage for transactional and analytical workloads. In general, you should use this table type for all tables unless you profile your workload and determine that another table type is better.

To create a table, you can simply use `Schema::create`:

```php
Schema::create('table', function (Blueprint $table) {
    // ... column definitions, indexes, table options
});
```

### Rowstore Tables

To create a [rowstore] table, use the `rowstore` method. Rowstore tables are optimized for low-latency transactional workloads with high concurrency at the expense of memory. In general, we recommend using Universal Storage (see above) and benchmarking your workload before using a rowstore table.

```php
Schema::create('table', function (Blueprint $table) {
    $table->rowstore();

    // ...
});
```

### Reference Tables

To create a [reference table], you may use the `reference` method. Reference tables are fully replicated to every node in the cluster. This means that if you store 1000 rows in a reference table, those 1000 rows will be copied many times. Because of this you should only store small amounts of data in reference tables, and only when you need to reference that data via joins against non-collocated data in other tables. Inserts and updates to reference tables will also run slower due to the high replication overhead.

```php
Schema::create('table', function (Blueprint $table) {
    $table->reference();

    // ...
});
```

### Global Temporary Tables

To create a [global temporary table], you may use the `global` method on the table.

```php
Schema::create('table', function (Blueprint $table) {
    $table->rowstore();
    $table->temporary();
    $table->global();

    // ...
});
```

You may also use either of the following two methods:

```php
// Fluent
$table->rowstore()->temporary()->global();

// As an argument to `temporary`.
$table->temporary($global = true);
```

### Sparse Columns

You can mark particular columns as [sparse] fluently by appending `sparse` to the column's definition. This only applies to Rowstore tables.

```php
Schema::create('table', function (Blueprint $table) {
    $table->rowstore();

    $table->string('name')->nullable()->sparse();
});
```

### Sparse Tables

You can mark particular entire tables as [sparse] fluently by appending `sparse` to the column's definition. This only applies to Rowstore tables.

```php
Schema::create('table', function (Blueprint $table) {
    $table->rowstore();

    $table->string('name');

    $table->sparse();
});
```

### Shard Keys

You can add a [shard key] to your tables using the standalone `shardKey` method, or fluently by appending `shardKey` to the column definition.

```php
Schema::create('table', function (Blueprint $table) {
    $table->string('name');

    $table->shardKey('name');
});

Schema::create('table', function (Blueprint $table) {
    $table->string('name')->shardKey();
});

Schema::create('table', function (Blueprint $table) {
    $table->string('f_name');
    $table->string('l_name');

    $table->shardKey(['f_name', 'l_name']);
});
```

### Sort Keys

You can add a [sort key] to your tables using the standalone `sortKey` method, or fluently by appending `sortKey` to the column definition.

```php
Schema::create('table', function (Blueprint $table) {
    $table->string('name');

    $table->sortKey('name');
});

Schema::create('table', function (Blueprint $table) {
    $table->string('name')->sortKey();
});

Schema::create('table', function (Blueprint $table) {
    $table->string('f_name');
    $table->string('l_name');

    $table->sortKey(['f_name', 'l_name']);
});
```

Sort keys sort in ascending order by default. If you would like to create a sort key which sorts descending you can set the key direction to `desc`.

```php
Schema::create('table', function (Blueprint $table) {
    $table->string('name');

    $table->sortKey('name', 'desc');
});

Schema::create('table', function (Blueprint $table) {
    $table->string('name')->sortKey('desc');
});
```

You may also define the sort key direction per-column using the following syntax:

```php
Schema::create('table', function (Blueprint $table) {
    $table->string('f_name');
    $table->string('l_name');

    $table->sortKey([['f_name', 'asc'], ['l_name', 'desc']]);
});
```

Sometimes you may want to tune [columnstore][columnstore-tuning] per table. You can do it by appending `with` fluently to the `sortKey` definition.

```php
Schema::create('table', function (Blueprint $table) {
    $table->string('name');

    $table->sortKey('name')->with(['columnstore_segment_rows' => 100000]);
});

Schema::create('table', function (Blueprint $table) {
    $table->string('name')->sortKey()->with(['columnstore_segment_rows' => 100000]);
});
```

However, you may want to tune it without setting a column as sort key. You can do that by creating an empty `sortKey` definition:

```php
Schema::create('table', function (Blueprint $table) {
    $table->string('name');

    $table->sortKey()->with(['columnstore_segment_rows' => 100000]);
});
```

### Unique Keys

You can add an `unique key` to your tables using the standalone `unique` method, or fluently by appending `unique` to the column definition.

> **Note**
> SingleStore requires that the shard key is contained within an unique key. This means that in most cases you can't use the fluent api as you will likely need to specify more than one column. This restriction does not apply to reference tables.

```php
Schema::create('table', function (Blueprint $table) {
    $table->string('key');
    $table->string('val');

    $table->shardKey('key');
    $table->unique(['key', 'val']);
});

Schema::create('table', function (Blueprint $table) {
    $table->reference();
    $table->string('name')->unique();
});
```

### Hash Keys

You can add a `hash key` to your tables using the third argument to the `index`
function. Note that by default, indexes on Universal Storage Tables
(Columnstore) are always hash indexes, so a simple `.index(foo)` is usually
sufficient. On Rowstore tables this syntax is needed to create a hash index.

```php
Schema::create('table', function (Blueprint $table) {
    $table->string('name');
    $table->index('name', 'name_idx', 'hash');
});
```

### Series Timestamps
To denote a column as a series timestamp, use the `seriesTimestamp` column modifier.

```php
Schema::create('table', function (Blueprint $table) {
    $table->datetime('created_at')->seriesTimestamp();

    // Or make it sparse
    $table->datetime('deleted_at')->nullable()->seriesTimestamp()->sparse();
});
```

### Computed Columns

SingleStore does not support virtual computed columns. You must use Laravel's [`storedAs`] method to create a [persisted computed column].

```php
Schema::create('test', function (Blueprint $table) {
    $table->integer('a');
    $table->integer('b');
    $table->integer('c')->storedAs('a + b');
});
```

### Increment Columns without Primary Key

Sometimes you may want to set a custom primary key. However if your table has an int `increment` column, Laravel, by default, always sets this column as the primary key. Even if you manually set another one. This behavior can be disabled using the `withoutPrimaryKey` method.

```php
Schema::create('test', function (Blueprint $table) {
    $table->id()->withoutPrimaryKey();
    $table->uuid('uuid');
    
    $table->primary(['id',  'uuid']);
});
```

### Full-text search using FULLTEXT indexes

SingleStoreDB supports full-text search across text columns in a columnstore table using the `FULLTEXT` index type.

Keep in mind that `FULLTEXT` is only supported when using the `utf8_unicode_ci` collation. An exception will be thrown if you try to add the index to a column with an unsupported collation.

```php
Schema::create('test', function (Blueprint $table) {
    $table->id();
    $table->text('first_name')->collation('utf8_unicode_ci');

    $table->fullText(['first_name']);
});
```

## Testing

Execute the tests using PHPUnit
```
./vendor/bin/phpunit
```

To test against an active SingleStore database, create a `.env` file and populate the following variables:

```
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=
DB_HOST=
```

Now when executing your tests, enable the integration tests by running

```shell
HYBRID_INTEGRATION=1 ./vendor/bin/phpunit
```

## License

This library is licensed under the Apache 2.0 License.

## Resources

* [SingleStore](https://singlestore.com)
* [Laravel](https://laravel.com)
* [Full-text search documentation](https://docs.singlestore.com/managed-service/en/reference/sql-reference/data-definition-language-ddl/create-table.html#fulltext-behavior)

## User agreement

SINGLESTORE, INC. ("SINGLESTORE") AGREES TO GRANT YOU AND YOUR COMPANY ACCESS TO THIS OPEN SOURCE SOFTWARE CONNECTOR ONLY IF (A) YOU AND YOUR COMPANY REPRESENT AND WARRANT THAT YOU, ON BEHALF OF YOUR COMPANY, HAVE THE AUTHORITY TO LEGALLY BIND YOUR COMPANY AND (B) YOU, ON BEHALF OF YOUR COMPANY ACCEPT AND AGREE TO BE BOUND BY ALL OF THE OPEN SOURCE TERMS AND CONDITIONS APPLICABLE TO THIS OPEN SOURCE CONNECTOR AS SET FORTH BELOW (THIS “AGREEMENT”), WHICH SHALL BE DEFINITIVELY EVIDENCED BY ANY ONE OF THE FOLLOWING MEANS: YOU, ON BEHALF OF YOUR COMPANY, CLICKING THE “DOWNLOAD, “ACCEPTANCE” OR “CONTINUE” BUTTON, AS APPLICABLE OR COMPANY’S INSTALLATION, ACCESS OR USE OF THE OPEN SOURCE CONNECTOR AND SHALL BE EFFECTIVE ON THE EARLIER OF THE DATE ON WHICH THE DOWNLOAD, ACCESS, COPY OR INSTALL OF THE CONNECTOR OR USE ANY SERVICES (INCLUDING ANY UPDATES OR UPGRADES) PROVIDED BY SINGLESTORE.
BETA SOFTWARE CONNECTOR

Customer Understands and agrees that it is  being granted access to pre-release or “beta” versions of SingleStore’s open source software connector (“Beta Software Connector”) for the limited purposes of non-production testing and evaluation of such Beta Software Connector. Customer acknowledges that SingleStore shall have no obligation to release a generally available version of such Beta Software Connector or to provide support or warranty for such versions of the Beta Software Connector  for any production or non-evaluation use.

NOTWITHSTANDING ANYTHING TO THE CONTRARY IN ANY DOCUMENTATION,  AGREEMENT OR IN ANY ORDER DOCUMENT, SINGLESTORE WILL HAVE NO WARRANTY, INDEMNITY, SUPPORT, OR SERVICE LEVEL, OBLIGATIONS WITH
RESPECT TO THIS BETA SOFTWARE CONNECTOR (INCLUDING TOOLS AND UTILITIES).

APPLICABLE OPEN SOURCE LICENSE: Apache 2.0

IF YOU OR YOUR COMPANY DO NOT AGREE TO THESE TERMS AND CONDITIONS, DO NOT CHECK THE ACCEPTANCE BOX, AND DO NOT DOWNLOAD, ACCESS, COPY, INSTALL OR USE THE SOFTWARE OR THE SERVICES.

[reference table]: https://docs.singlestore.com/managed-service/en/create-a-database/physical-database-schema-design/concepts-of-physical-database-schema-design/other-schema-concepts.html#reference-tables-654455
[global temporary table]: https://docs.singlestore.com/managed-service/en/create-a-database/physical-database-schema-design/concepts-of-physical-database-schema-design/other-schema-concepts.html#global-temporary-tables
[columnstore]: https://docs.singlestore.com/managed-service/en/create-a-database/physical-database-schema-design/concepts-of-physical-database-schema-design/columnstore.html
[rowstore]: https://docs.singlestore.com/managed-service/en/create-a-database/physical-database-schema-design/concepts-of-physical-database-schema-design/rowstore.html
[sparse]: https://docs.singlestore.com/managed-service/en/reference/sql-reference/data-definition-language-ddl/create-table.html#compression---sparse-and-sparse-behavior
[shard key]: https://docs.singlestore.com/managed-service/en/developer-resources/porting-tables-to-singlestoredb-cloud/shard-keys.html
[sort key]: https://docs.singlestore.com/managed-service/en/create-a-database/physical-database-schema-design/procedures-for-physical-database-schema-design/creating-a-columnstore-table.html
[columnstore-tuning]: https://docs.singlestore.com/managed-service/en/create-a-database/physical-database-schema-design/procedures-for-physical-database-schema-design/configuring-the-columnstore-to-work-effectively.html
[`storedAs`]: https://laravel.com/docs/9.x/migrations#column-modifiers
[persisted computed column]: https://docs.singlestore.com/managed-service/en/create-a-database/physical-database-schema-design/procedures-for-physical-database-schema-design/using-persistent-computed-columns.html
[singlestore-pem]: https://portal.singlestore.com/static/ca/singlestore_bundle.pem
[Eloquent's attribute casting]: https://laravel.com/docs/9.x/eloquent-mutators#attribute-casting
[create table]: https://docs.singlestore.com/managed-service/en/reference/sql-reference/data-definition-language-ddl/create-table.html
[attr_persistent]: https://www.php.net/manual/en/features.persistent-connections.php