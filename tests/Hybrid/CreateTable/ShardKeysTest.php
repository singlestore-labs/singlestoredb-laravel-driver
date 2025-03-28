<?php

namespace SingleStore\Laravel\Tests\Hybrid\CreateTable;

use PHPUnit\Framework\Attributes\Test;
use SingleStore\Laravel\Schema\Blueprint;
use SingleStore\Laravel\Tests\BaseTest;
use SingleStore\Laravel\Tests\Hybrid\HybridTestHelpers;

class ShardKeysTest extends BaseTest
{
    use HybridTestHelpers;

    #[Test]
    public function it_adds_a_shard_key_standalone()
    {
        $blueprint = $this->createTable(function (Blueprint $table) {
            $table->string('name');

            $table->shardKey('name');
        });

        $this->assertCreateStatement(
            $blueprint,
            'create table `test` (`name` varchar(255) not null, shard key(`name`))'
        );
    }

    #[Test]
    public function it_adds_a_shard_key_fluent()
    {
        $blueprint = $this->createTable(function (Blueprint $table) {
            $table->string('name')->shardKey();
        });

        $this->assertCreateStatement(
            $blueprint,
            'create table `test` (`name` varchar(255) not null, shard key(`name`))'
        );
    }

    #[Test]
    public function it_adds_a_dual_shard_key()
    {
        $blueprint = $this->createTable(function (Blueprint $table) {
            $table->string('f_name');
            $table->string('l_name');

            $table->shardKey(['f_name', 'l_name']);
        });

        $this->assertCreateStatement(
            $blueprint,
            'create table `test` (`f_name` varchar(255) not null, `l_name` varchar(255) not null, shard key(`f_name`, `l_name`))'
        );
    }
}
