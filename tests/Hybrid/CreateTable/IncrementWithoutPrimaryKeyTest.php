<?php

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
            $table->uuid('uuid');

            $table->primary(['id', 'uuid']);
        });

        $this->assertCreateStatement(
            $blueprint,
            'create table `test` (`id` bigint unsigned not null auto_increment, `uuid` char(36) not null, primary key `test_id_uuid_primary`(`id`, `uuid`))'
        );
    }

    /** @test */
    public function it_adds_an_id_without_primary_key()
    {
        $blueprint = $this->createTable(function (Blueprint $table) {
            $table->id()->withoutPrimaryKey();
            $table->uuid('uuid');

            $table->primary(['id', 'uuid']);
        });

        $this->assertCreateStatement(
            $blueprint,
            'create table `test` (`id` bigint unsigned not null auto_increment, `uuid` char(36) not null, primary key `test_id_uuid_primary`(`id`, `uuid`))'
        );
    }
}
