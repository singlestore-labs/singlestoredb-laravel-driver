<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace SingleStore\Laravel\Tests\Unit\CreateTable;

use Mockery;
use SingleStore\Laravel\Connect\Connection;
use SingleStore\Laravel\Schema\Blueprint;
use SingleStore\Laravel\Schema\Grammar;

trait AssertsTableCreation
{
    protected function getGrammar()
    {
        return new Grammar;
    }

    protected function getConnection($mockConfig = true)
    {
        $connection = Mockery::mock(Connection::class);

        if ($mockConfig) {
            $connection->shouldReceive('getConfig')->atMost()->once()->with('charset')->andReturn(null);
            $connection->shouldReceive('getConfig')->atMost()->once()->with('collation')->andReturn(null);
            $connection->shouldReceive('getConfig')->atMost()->once()->with('engine')->andReturn(null);
        }

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
        $blueprint = new Blueprint('test');
        $blueprint->create();

        call_user_func($fn, $blueprint);

        return $blueprint;
    }
}