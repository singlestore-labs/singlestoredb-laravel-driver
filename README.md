# SingleStore Driver for Laravel

This repository contains a SingleStore Driver for Laravel.

This package is currently in a pre-release beta, please use with caution and open any issues that you run into.

## Install

You can install the package via composer:

```shell
composer require singlestore/singlestore-laravel
```

## Usage

To enable the driver, head to your `config/databases.php` file and create a new entry for SingleStore in your `connections`, and update your `default` to point to that new connection:

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

## Migrations

This driver provides many SingleStore specific methods for creating or modifying tables. They are listed below. For more information see the [Create Table](https://docs.singlestore.com/managed-service/en/reference/sql-reference/data-definition-language-ddl/create-table.html) docs on SingleStore.

### Rowstore Tables

To create a rowstore table, use the `rowstore` method.

```php
Schema::create('table', function (Blueprint $table) {
    $table->rowstore();

    // ...
});
```

### Reference Tables

To create a reference table, you may use the `reference` method.

```php
Schema::create('table', function (Blueprint $table) {
    $table->reference();

    // ...
});
```

### Global Temporary Tables

To create a [global temporary table](https://docs.singlestore.com/managed-service/en/reference/sql-reference/data-definition-language-ddl/create-table.html), you may use the `global` method on the table.

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

You can mark particular columns as [sparse](https://docs.singlestore.com/managed-service/en/reference/sql-reference/data-definition-language-ddl/create-table.html) fluently by appending `sparse` to the column's definition.

```php
Schema::create('table', function (Blueprint $table) {
    $table->rowstore();

    $table->string('name')->nullable()->sparse();
});
```

### Sparse Tables

You can mark particular entire tables as [sparse](https://docs.singlestore.com/managed-service/en/reference/sql-reference/data-definition-language-ddl/create-table.html) fluently by appending `sparse` to the column's definition.

```php
Schema::create('table', function (Blueprint $table) {
    $table->rowstore();

    $table->string('name');

    $table->sparse();
});
```

### Shard Keys

You can add a [shard key](https://docs.singlestore.com/managed-service/en/getting-started-with-managed-service/about-managed-service/sharding.html) to your tables using the standalone `shardKey` method, or fluently by appending `shardKey` to the column definition.

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

You can add a [sort key](https://docs.singlestore.com/managed-service/en/create-a-database/physical-database-schema-design/procedures-for-physical-database-schema-design/creating-a-columnstore-table.html) to your tables using the standalone `sortKey` method, or fluently by appending `sortKey` to the column definition.

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

SingleStore does not support virtual computed columns. You must use Laravel's [`storedAs`](https://laravel.com/docs/9.x/migrations#column-modifiers) method to create a [persisted computed column](https://docs.singlestore.com/managed-service/en/create-a-database/physical-database-schema-design/procedures-for-physical-database-schema-design/using-persistent-computed-columns.html).

```php
Schema::create('test', function (Blueprint $table) {
    $table->integer('a');
    $table->integer('b');
    $table->integer('c')->storedAs('a + b');
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

## User agreement

SINGLESTORE, INC. ("SINGLESTORE") AGREES TO GRANT YOU AND YOUR COMPANY ACCESS TO THIS OPEN SOURCE SOFTWARE CONNECTOR ONLY IF (A) YOU AND YOUR COMPANY REPRESENT AND WARRANT THAT YOU, ON BEHALF OF YOUR COMPANY, HAVE THE AUTHORITY TO LEGALLY BIND YOUR COMPANY AND (B) YOU, ON BEHALF OF YOUR COMPANY ACCEPT AND AGREE TO BE BOUND BY ALL OF THE OPEN SOURCE TERMS AND CONDITIONS APPLICABLE TO THIS OPEN SOURCE CONNECTOR AS SET FORTH BELOW (THIS “AGREEMENT”), WHICH SHALL BE DEFINITIVELY EVIDENCED BY ANY ONE OF THE FOLLOWING MEANS: YOU, ON BEHALF OF YOUR COMPANY, CLICKING THE “DOWNLOAD, “ACCEPTANCE” OR “CONTINUE” BUTTON, AS APPLICABLE OR COMPANY’S INSTALLATION, ACCESS OR USE OF THE OPEN SOURCE CONNECTOR AND SHALL BE EFFECTIVE ON THE EARLIER OF THE DATE ON WHICH THE DOWNLOAD, ACCESS, COPY OR INSTALL OF THE CONNECTOR OR USE ANY SERVICES (INCLUDING ANY UPDATES OR UPGRADES) PROVIDED BY SINGLESTORE.
BETA SOFTWARE CONNECTOR

Customer Understands and agrees that it is  being granted access to pre-release or “beta” versions of SingleStore’s open source software connector (“Beta Software Connector”) for the limited purposes of non-production testing and evaluation of such Beta Software Connector. Customer acknowledges that SingleStore shall have no obligation to release a generally available version of such Beta Software Connector or to provide support or warranty for such versions of the Beta Software Connector  for any production or non-evaluation use.

NOTWITHSTANDING ANYTHING TO THE CONTRARY IN ANY DOCUMENTATION,  AGREEMENT OR IN ANY ORDER DOCUMENT, SINGLESTORE WILL HAVE NO WARRANTY, INDEMNITY, SUPPORT, OR SERVICE LEVEL, OBLIGATIONS WITH
RESPECT TO THIS BETA SOFTWARE CONNECTOR (INCLUDING TOOLS AND UTILITIES).

APPLICABLE OPEN SOURCE LICENSE: Apache 2.0

IF YOU OR YOUR COMPANY DO NOT AGREE TO THESE TERMS AND CONDITIONS, DO NOT CHECK THE ACCEPTANCE BOX, AND DO NOT DOWNLOAD, ACCESS, COPY, INSTALL OR USE THE SOFTWARE OR THE SERVICES.
