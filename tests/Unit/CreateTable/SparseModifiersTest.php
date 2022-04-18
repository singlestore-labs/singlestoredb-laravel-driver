<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace SingleStore\Laravel\Tests\Unit\CreateTable;

use SingleStore\Laravel\Schema\Blueprint;
use SingleStore\Laravel\Tests\BaseTest;

class SparseModifiersTest extends BaseTest
{
    use AssertsTableCreation;

    /** @test */
    public function sparse_column()
    {
        $blueprint = $this->createTable(function (Blueprint $table) {
            $table->rowstore();

            $table->string('name')->nullable()->sparse();
        });

        $this->assertCreateStatement(
            $blueprint,
            "create rowstore table `test` (`name` varchar(255) null sparse)"
        );
    }

    /** @test */
    public function sparse_table()
    {
        $blueprint = $this->createTable(function (Blueprint $table) {
            $table->rowstore();

            $table->string('name');

            $table->sparse();
        });

        $this->assertCreateStatement(
            $blueprint,
            "create rowstore table `test` (`name` varchar(255) not null) compression = sparse"
        );
    }
}