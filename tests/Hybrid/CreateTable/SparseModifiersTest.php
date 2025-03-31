<?php

namespace SingleStore\Laravel\Tests\Hybrid\CreateTable;

use PHPUnit\Framework\Attributes\Test;
use SingleStore\Laravel\Schema\Blueprint;
use SingleStore\Laravel\Schema\SingleStoreSchemaGrammar;
use SingleStore\Laravel\Tests\BaseTest;
use SingleStore\Laravel\Tests\Hybrid\HybridTestHelpers;

class SparseModifiersTest extends BaseTest
{
    use HybridTestHelpers;

    #[Test]
    public function sparse_column()
    {
        $blueprint = $this->createTable(function (Blueprint $table) {
            $table->rowstore();

            $table->string('name')->nullable()->sparse();
        });

        $this->assertCreateStatement(
            $blueprint,
            'create rowstore table `test` (`name` varchar(255) null sparse)'
        );
    }

    #[Test]
    public function sparse_table()
    {
        $blueprint = $this->createTable(function (Blueprint $table) {
            $table->rowstore();

            $table->string('name');

            $table->sparse();
        });

        $this->assertCreateStatement(
            $blueprint,
            'create rowstore table `test` (`name` varchar(255) not null) compression = sparse'
        );
    }

    #[Test]
    public function sparse_with_after()
    {
        // See https://github.com/singlestore-labs/singlestoredb-laravel-driver/issues/18
        $connection = $this->getConnection('test');
        $grammar = new SingleStoreSchemaGrammar($connection);

        $connection->shouldReceive('getSchemaGrammar')->andReturn($grammar);
        $connection->shouldReceive('getTablePrefix')->andReturn('');
        $connection->shouldReceive('getDatabaseName')->andReturn('database');

        $blueprint = new Blueprint($connection, 'test');

        $blueprint->string('two_factor_secret')
            ->after('password')
            ->nullable()
            ->sparse();

        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(
            'alter table `test` add `two_factor_secret` varchar(255) null sparse after `password`',
            $statements[0]
        );
    }
}
