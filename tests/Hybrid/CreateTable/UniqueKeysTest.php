<?php

namespace SingleStore\Laravel\Tests\Hybrid\CreateTable;

use SingleStore\Laravel\Schema\Blueprint;
use SingleStore\Laravel\Tests\BaseTest;
use SingleStore\Laravel\Tests\Hybrid\HybridTestHelpers;

class UniqueKeysTest extends BaseTest
{
    use HybridTestHelpers;

    /** @test */
    public function it_adds_a_unique_key()
    {
        $blueprint = $this->createTable(function (Blueprint $table) {
            $table->string('key');
            $table->string('val');

            $table->shardKey('key');
            $table->unique(['key', 'val']);
        });

        $this->assertCreateStatement(
            $blueprint,
            'create table `test` (`key` varchar(255) not null, `val` varchar(255) not null, shard key(`key`), unique `test_key_val_unique`(`key`, `val`))'
        );
    }

    /** @test */
    public function it_adds_a_unique_key_reference_fluent()
    {
        $blueprint = $this->createTable(function (Blueprint $table) {
            $table->reference();
            $table->string('name')->unique();
        });

        $this->assertCreateStatement(
            $blueprint,
            'create reference table `test` (`name` varchar(255) not null, unique `test_name_unique`(`name`))'
        );
    }
}
