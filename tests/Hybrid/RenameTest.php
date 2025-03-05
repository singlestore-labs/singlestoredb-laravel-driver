<?php

namespace SingleStore\Laravel\Tests\Hybrid;

use Illuminate\Support\Facades\Schema;
use SingleStore\Laravel\Schema\Blueprint;
use SingleStore\Laravel\Schema\SingleStoreSchemaGrammar;
use SingleStore\Laravel\Tests\BaseTest;
use PHPUnit\Framework\Attributes\Test;

class RenameTest extends BaseTest
{
    use HybridTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();

        if ($this->runHybridIntegrations()) {
            $this->createTable(function (Blueprint $table) {
                $table->id();
            });
        }
    }

    protected function tearDown(): void
    {
        if ($this->runHybridIntegrations()) {
            Schema::dropIfExists('test_renamed');
        }

        parent::tearDown();
    }

    #[Test]
    public function rename_table()
    {
        if ($this->runHybridIntegrations()) {
            $cached = $this->mockDatabaseConnection;

            $this->mockDatabaseConnection = false;

            $this->assertFalse(Schema::hasTable('test_renamed'));
            Schema::rename('test', 'test_renamed');

            $this->assertTrue(Schema::hasTable('test_renamed'));

            $this->mockDatabaseConnection = $cached;
        }

        $connection = $this->getConnection('test');
        $grammar = new SingleStoreSchemaGrammar($connection);

        $connection->shouldReceive('getSchemaGrammar')->andReturn($grammar);
        $connection->shouldReceive('getDatabaseName')->andReturn('database');
        $connection->shouldReceive('getTablePrefix')->andReturn('');

        $blueprint = new Blueprint($connection, 'test');
        $blueprint->rename('test_renamed');

        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertEquals('alter table `test` rename to `test_renamed`', $statements[0]);
    }

    #[Test]
    public function rename_column()
    {
        if ($this->runHybridIntegrations()) {
            $cached = $this->mockDatabaseConnection;

            $this->mockDatabaseConnection = false;

            $this->createTable(function (Blueprint $table) {
                $table->id();
                $table->string('data');
            });

            Schema::table('test', function (Blueprint $table) {
                $table->renameColumn('data', 'data1');
            });

            $database = $this->getConnection()->getDatabaseName();
            $columnNames = Schema::getColumnListing("$database.test");
            $this->assertEquals(['id', 'data1'], $columnNames);

            $this->mockDatabaseConnection = $cached;
        }

        $connection = $this->getConnection('test');
        $grammar = new SingleStoreSchemaGrammar($connection);

        $connection->shouldReceive('getSchemaGrammar')->andReturn($grammar);
        $connection->shouldReceive('getDatabaseName')->andReturn('database');
        $connection->shouldReceive('getTablePrefix')->andReturn('');

        $blueprint = new Blueprint($connection, 'test');
        $blueprint->renameColumn('data', 'data1');

        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertEquals('alter table `test` change `data` `data1`', $statements[0]);
    }
}
