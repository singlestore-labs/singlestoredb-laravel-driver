<?php

namespace SingleStore\Laravel\Tests\Hybrid\CreateTable;

use SingleStore\Laravel\Schema\Blueprint;
use SingleStore\Laravel\Tests\BaseTest;
use SingleStore\Laravel\Tests\Hybrid\HybridTestHelpers;

class MiscCreateTest extends BaseTest
{
    use HybridTestHelpers;

    /** @test */
    public function all_keys_are_added_in_create_columnstore()
    {
        $blueprint = $this->createTable(function (Blueprint $table) {
            $table->string('primary')->primary('name1');
            $table->string('index')->index('name2');
            $table->string('foo');
            $table->index('foo', 'name3', 'hash');
        });

        $this->assertCreateStatement(
            $blueprint,
            'create table `test` (`primary` varchar(255) not null, `index` varchar(255) not null, `foo` varchar(255) not null, index `name3` using hash(`foo`), primary key `name1`(`primary`), index `name2`(`index`))'
        );
    }

    /** @test */
    public function all_keys_are_added_in_create_rowstore()
    {
        $blueprint = $this->createTable(function (Blueprint $table) {
            $table->rowstore();
            $table->string('primary')->primary('name1');
            $table->string('index')->index('name2');
            $table->geography('georegion')->spatialIndex('name3');
        });

        $this->assertCreateStatement(
            $blueprint,
            'create rowstore table `test` (`primary` varchar(255) not null, `index` varchar(255) not null, `georegion` geography not null, primary key `name1`(`primary`), index `name2`(`index`), index `name3`(`georegion`))'
        );
    }

    /** @test */
    public function medium_integer_id()
    {
        $blueprint = $this->createTable(function (Blueprint $table) {
            $table->mediumInteger('id', true, true);
        });

        $this->assertCreateStatement(
            $blueprint,
            'create table `test` (`id` mediumint unsigned not null auto_increment primary key)'
        );
    }
}
