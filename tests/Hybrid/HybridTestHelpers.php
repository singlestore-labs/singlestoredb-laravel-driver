<?php

namespace SingleStore\Laravel\Tests\Hybrid;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use SingleStore\Laravel\Connect\SingleStoreConnection;
use SingleStore\Laravel\Schema\Blueprint;
use SingleStore\Laravel\Schema\SingleStoreSchemaBuilder;
use SingleStore\Laravel\Schema\SingleStoreSchemaGrammar;

trait HybridTestHelpers
{
    use OverridesGetConnection;

    public bool $mockDatabaseConnection = true;

    protected static int $counter = 1;

    protected function getGrammar($connection = null, $table = null)
    {
        return new SingleStoreSchemaGrammar($this->getConnection($connection, $table));
    }

    protected function runHybridIntegrations()
    {
        return env('HYBRID_INTEGRATION') == 1;
    }

    protected function getDatabaseConnection($connection = null, $table = null)
    {
        return $this->mockDatabaseConnection
            ? $this->mockedConnection()
            : parent::getConnection($connection, $table);
    }

    protected function mockedConnection()
    {
        $connection = Mockery::mock(SingleStoreConnection::class);
        $grammar = new SingleStoreSchemaGrammar($connection);

        $connection->shouldReceive('getConfig')->atMost()->once()->with('charset')->andReturn(null);
        $connection->shouldReceive('getConfig')->atMost()->once()->with('collation')->andReturn(null);
        $connection->shouldReceive('getConfig')->atMost()->once()->with('engine')->andReturn(null);
        $connection->shouldReceive('getConfig')->atMost()->once()->with('prefix_indexes')->andReturn(null);
        $connection->shouldReceive('getSchemaGrammar')->andReturn($grammar);
        $connection->shouldReceive('getTablePrefix')->andReturn('');
        $connection->shouldReceive('getSchemaBuilder')->andReturn(new SingleStoreSchemaBuilder($connection));

        return $connection;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        Mockery::close();
    }

    protected function assertCreateStatement($blueprint, $sql)
    {
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertEquals($sql, $statements[0]);

        return $statements;
    }

    protected function createTable($fn)
    {
        if ($this->runHybridIntegrations()) {
            $cached = $this->mockDatabaseConnection;

            $this->mockDatabaseConnection = false;

            Schema::dropIfExists('test');
            $this->assertFalse(Schema::hasTable('test'));
            Schema::create('test', $fn);

            // Can't assert table exists, because some tables are temp. If the table
            // wasn't successfully created, this will throw an error, so we're
            // just asserting a blank set of results comes back.
            $this->assertTrue(DB::table('test')->select('*')->get() instanceof Collection);

            $this->mockDatabaseConnection = $cached;
        }

        $blueprint = new Blueprint($this->getConnection(), 'test');
        $blueprint->create();

        call_user_func($fn, $blueprint);

        return $blueprint;
    }

    protected function insertJsonData($records)
    {
        $this->createTable(function (Blueprint $table) {
            $table->id();
            $table->json('data');
        });

        $ids = [];

        foreach ($records as $record) {
            DB::table('test')->insert([
                'id' => static::$counter,
                'data' => json_encode($record),
            ]);

            $ids[] = static::$counter++;
        }

        return $ids;
    }
}
