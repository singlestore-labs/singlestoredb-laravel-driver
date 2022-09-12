<?php
/**
 * @author https://github.com/srdante
 */

namespace SingleStore\Laravel\Tests\Hybrid\CreateTable;

use SingleStore\Laravel\Schema\Blueprint;
use SingleStore\Laravel\Tests\BaseTest;
use SingleStore\Laravel\Tests\Hybrid\HybridTestHelpers;

class IncrementWithoutPrimaryKeyTest extends BaseTest
{
    use HybridTestHelpers;

    /** @test */
    public function it_adds_a_big_increments_without_primary_key()
    {
        $blueprint = $this->createTable(function (Blueprint $table) {
            $table->bigIncrements('id')->withoutPrimaryKey();
        });

        $this->assertCreateStatement(
            $blueprint,
            'create table `test` (`id` bigint unsigned not null auto_increment)'
        );
    }

    /** @test */
    public function it_adds_an_id_without_primary_key()
    {
        $blueprint = $this->createTable(function (Blueprint $table) {
            $table->id()->withoutPrimaryKey();
        });

        $this->assertCreateStatement(
            $blueprint,
            'create table `test` (`id` bigint unsigned not null auto_increment)'
        );
    }
}
