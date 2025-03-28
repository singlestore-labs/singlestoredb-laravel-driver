<?php

namespace SingleStore\Laravel\Tests\Hybrid;

use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use SingleStore\Laravel\Schema\Blueprint;
use SingleStore\Laravel\Schema\SingleStoreSchemaBuilder;
use SingleStore\Laravel\Schema\SingleStoreSchemaGrammar;
use SingleStore\Laravel\Tests\BaseTest;

class ChangeColumnTest extends BaseTest
{
    use HybridTestHelpers;

    #[Test]
    public function change_column_on_rowstore_table()
    {
        if ($this->runHybridIntegrations()) {
            $cached = $this->mockDatabaseConnection;

            $this->mockDatabaseConnection = false;

            if (method_exists(SingleStoreSchemaBuilder::class, 'useNativeSchemaOperationsIfPossible')) {
                Schema::useNativeSchemaOperationsIfPossible();
            }

            $this->createTable(function (Blueprint $table) {
                $table->rowstore();
                $table->id();
                $table->string('data');
            });

            Schema::table('test', function (Blueprint $table) {
                $table->text('data')->nullable()->change();
            });

            $this->assertEquals(['id', 'data'], Schema::getColumnListing('test'));
            $this->assertEquals('text', Schema::getColumnType('test', 'data'));

            $this->mockDatabaseConnection = $cached;
        }

        $connection = $this->getConnection();
        $grammar = new SingleStoreSchemaGrammar($connection);

        $connection->shouldReceive('getSchemaGrammar')->andReturn($grammar);
        $connection->shouldReceive('getDatabaseName')->andReturn('database');
        $connection->shouldReceive('getTablePrefix')->andReturn('');
        $connection->shouldReceive('scalar')
            ->with("select exists (select 1 from information_schema.tables where table_schema = 'database' and table_name = 'test' and storage_type = 'COLUMNSTORE') as is_columnstore")
            ->andReturn(0);
        $connection->shouldReceive('usingNativeSchemaOperations')->andReturn(true);

        $blueprint = new Blueprint($connection, 'test');
        $blueprint->text('data')->nullable()->change();

        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertEquals('alter table `test` modify `data` text null', $statements[0]);
    }

    #[Test]
    public function change_column_of_columnstore_table()
    {
        if ($this->runHybridIntegrations()) {
            $cached = $this->mockDatabaseConnection;

            $this->mockDatabaseConnection = false;

            $this->createTable(function (Blueprint $table) {
                $table->id();
                $table->string('data');
            });

            Schema::table('test', function (Blueprint $table) {
                $table->text('data')->nullable()->change();
            });

            $this->assertEquals(['id', 'data'], Schema::getColumnListing('test'));
            $this->assertEquals('text', Schema::getColumnType('test', 'data'));

            $this->mockDatabaseConnection = $cached;
        }

        $connection = $this->getConnection('test');
        $grammar = new SingleStoreSchemaGrammar($connection);

        $connection->shouldReceive('getSchemaGrammar')->andReturn($grammar);
        $connection->shouldReceive('getDatabaseName')->andReturn('database');
        $connection->shouldReceive('getTablePrefix')->andReturn('');
        $connection->shouldReceive('scalar')
            ->with("select exists (select 1 from information_schema.tables where table_schema = 'database' and table_name = 'test' and storage_type = 'COLUMNSTORE') as is_columnstore")
            ->andReturn(1);

        $blueprint = new Blueprint($connection, 'test');
        $blueprint->text('data')->nullable()->change();

        $statements = $blueprint->toSql($connection, $this->getGrammar());

        $this->assertCount(4, $statements);
        $this->assertEquals([
            'alter table `test` add `__temp__data` text null after `data`',
            'update `test` set `__temp__data` = `data`',
            'alter table `test` drop `data`',
            'alter table `test` change `__temp__data` `data`',
        ], $statements);
    }
}
