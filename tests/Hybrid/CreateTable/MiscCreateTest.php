<?php

namespace SingleStore\Laravel\Tests\Hybrid\CreateTable;

use Illuminate\Foundation\Application;
use PHPUnit\Framework\Attributes\Test;
use SingleStore\Laravel\Schema\Blueprint;
use SingleStore\Laravel\Tests\BaseTest;
use SingleStore\Laravel\Tests\Hybrid\HybridTestHelpers;

class MiscCreateTest extends BaseTest
{
    use HybridTestHelpers;

    #[Test]
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
            'create table `test` (`primary` varchar(255) not null, `index` varchar(255) not null, `foo` varchar(255) not null, index `name3` using hash(`foo`), index `name2`(`index`), primary key (`primary`))'
        );
    }

    #[Test]
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
            'create rowstore table `test` (`primary` varchar(255) not null, `index` varchar(255) not null, `georegion` geography not null, index `name2`(`index`), index `name3`(`georegion`), primary key (`primary`))'
        );
    }

    #[Test]
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

    #[Test]
    public function discussion_53()
    {
        $blueprint = $this->createTable(function (Blueprint $table) {
            $table->bigIncrements('id')->withoutPrimaryKey()->index();
            $table->unsignedBigInteger('user_id')->shardKey();
            $table->string('template_id');
            $table->longText('data');
            $table->string('response_status_code');
            $table->longText('response_message');
            $table->timestamps();
        });

        $this->assertCreateStatement(
            $blueprint,
            'create table `test` (`id` bigint unsigned not null auto_increment, `user_id` bigint unsigned not null, `template_id` varchar(255) not null, `data` longtext not null, `response_status_code` varchar(255) not null, `response_message` longtext not null, `created_at` timestamp null, `updated_at` timestamp null, index `test_id_index`(`id`), shard key(`user_id`))'
        );
    }

    #[Test]
    public function json_column()
    {
        $blueprint = $this->createTable(function (Blueprint $table) {
            $table->id();
            $table->json('data');
        });

        $this->assertCreateStatement(
            $blueprint,
            'create table `test` (`id` bigint unsigned not null auto_increment primary key, `data` json not null)'
        );
    }
}
