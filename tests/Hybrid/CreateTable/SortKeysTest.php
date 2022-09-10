<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace SingleStore\Laravel\Tests\Hybrid\CreateTable;

use SingleStore\Laravel\Schema\Blueprint;
use SingleStore\Laravel\Tests\BaseTest;
use SingleStore\Laravel\Tests\Hybrid\HybridTestHelpers;

class SortKeysTest extends BaseTest
{
    use HybridTestHelpers;

    /** @test */
    public function it_adds_a_sort_key_standalone()
    {
        $blueprint = $this->createTable(function (Blueprint $table) {
            $table->string('name');
            $table->sortKey('name');
        });

        $this->assertCreateStatement(
            $blueprint,
            'create table `test` (`name` varchar(255) not null, sort key(`name` asc))'
        );
    }

    /** @test */
    public function it_adds_a_sort_key_with_desc_direction_standalone()
    {
        $blueprint = $this->createTable(function (Blueprint $table) {
            $table->string('name');
            $table->sortKey('name', 'desc');
        });

        $this->assertCreateStatement(
            $blueprint,
            'create table `test` (`name` varchar(255) not null, sort key(`name` desc))'
        );
    }

    /** @test */
    public function it_adds_a_sort_key_fluent()
    {
        $blueprint = $this->createTable(function (Blueprint $table) {
            $table->string('name')->sortKey();
        });

        $this->assertCreateStatement(
            $blueprint,
            'create table `test` (`name` varchar(255) not null, sort key(`name` asc))'
        );
    }

    /** @test */
    public function it_adds_a_sort_key_with_desc_direction_fluent()
    {
        $blueprint = $this->createTable(function (Blueprint $table) {
            $table->string('name')->sortKey('desc');
        });

        $this->assertCreateStatement(
            $blueprint,
            'create table `test` (`name` varchar(255) not null, sort key(`name` desc))'
        );
    }

    /** @test */
    public function it_adds_a_dual_sort_key()
    {
        $blueprint = $this->createTable(function (Blueprint $table) {
            $table->string('f_name');
            $table->string('l_name');
            $table->sortKey(['f_name', 'l_name']);
        });

        $this->assertCreateStatement(
            $blueprint,
            'create table `test` (`f_name` varchar(255) not null, `l_name` varchar(255) not null, sort key(`f_name`, `l_name` asc))'
        );
    }

    /** @test */
    public function shard_and_sort_keys()
    {
        $blueprint = $this->createTable(function (Blueprint $table) {
            $table->string('name')->sortKey()->shardKey();
        });

        $this->assertCreateStatement(
            $blueprint,
            'create table `test` (`name` varchar(255) not null, shard key(`name`), sort key(`name` asc))'
        );
    }
}
