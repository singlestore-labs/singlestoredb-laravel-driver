<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace SingleStore\Laravel\Tests\Hybrid;

use Illuminate\Foundation\Application;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use SingleStore\Laravel\Connect\Connection;
use SingleStore\Laravel\Schema\Blueprint;
use SingleStore\Laravel\Schema\Grammar;

trait HybridTestHelpers
{
    use OverridesGetConnection;

    public $mockDatabaseConnection = true;

    protected static $counter = 1;

    protected function getGrammar()
    {
        return new Grammar;
    }

    protected function runHybridIntegrations()
    {
        return env('HYBRID_INTEGRATION') == 1;
    }

    protected function getDatabaseConnection($connection = null, $table = null)
    {
        return $this->mockDatabaseConnection
            ? $this->mockedConnection()
            : $this->realConnection($connection, $table);
    }

    protected function mockedConnection()
    {
        $connection = Mockery::mock(Connection::class);

        $connection->shouldReceive('getConfig')->atMost()->once()->with('charset')->andReturn(null);
        $connection->shouldReceive('getConfig')->atMost()->once()->with('collation')->andReturn(null);
        $connection->shouldReceive('getConfig')->atMost()->once()->with('engine')->andReturn(null);

        return $connection;
    }

    protected function realConnection($connection, $table)
    {
        if (version_compare(Application::VERSION, '9.0.0', '>=')) {
            // Laravel 9
            return parent::getConnection($connection, $table);
        }

        // Laravel 8
        return parent::getConnection($connection);
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
            $this->mockDatabaseConnection = false;

            Schema::dropIfExists('test');
            $this->assertFalse(Schema::hasTable('test'));
            Schema::create('test', $fn);

            // Can't assert table exists, because some tables are temp. If the table
            // wasn't successfully created, this will throw an error, so we're
            // just asserting a blank set of results comes back.
            $this->assertTrue(DB::table('test')->select('*')->get() instanceof Collection);

            $this->mockDatabaseConnection = true;
        }

        $blueprint = new Blueprint('test');
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
                'data' => json_encode($record)
            ]);

            $ids[] = static::$counter++;
        }

        return $ids;
    }
}