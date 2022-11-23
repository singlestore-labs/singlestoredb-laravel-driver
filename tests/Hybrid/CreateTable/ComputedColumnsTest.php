<?php

namespace SingleStore\Laravel\Tests\Hybrid\CreateTable;

use Exception;
use SingleStore\Laravel\Schema\Blueprint;
use SingleStore\Laravel\Tests\BaseTest;
use SingleStore\Laravel\Tests\Hybrid\HybridTestHelpers;

class ComputedColumnsTest extends BaseTest
{
    use HybridTestHelpers;

    /** @test */
    public function computed_virtual_throws_an_exception()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('SingleStore does not support virtual computed columns. Use `storedAs` instead.');

        $blueprint = $this->createTable(function (Blueprint $table) {
            $table->string('name')->virtualAs('1');
        });

        $this->assertCreateStatement($blueprint, 'Argument is moot, exception will be thrown.');
    }

    /** @test */
    public function computed_stored()
    {
        $blueprint = $this->createTable(function (Blueprint $table) {
            $table->integer('a');
            $table->integer('b');
            $table->integer('c')->storedAs('a + b');
        });

        $this->assertCreateStatement(
            $blueprint,
            'create table `test` (`a` int not null, `b` int not null, `c` as (a + b) persisted int)'
        );
    }
}
