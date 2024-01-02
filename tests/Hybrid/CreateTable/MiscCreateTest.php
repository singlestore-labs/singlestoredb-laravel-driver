<?php

namespace SingleStore\Laravel\Tests\Hybrid\CreateTable;

use Illuminate\Foundation\Application;
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

        if (version_compare(Application::VERSION, '10.38.0', '>=')) {
            $this->assertCreateStatement(
                $blueprint,
                'create table `test` (`primary` varchar(255) not null, `index` varchar(255) not null, `foo` varchar(255) not null, index `name3` using hash(`foo`), index `name2`(`index`), primary key (`primary`))'
            );
        } else {
            $this->assertCreateStatement(
                $blueprint,
                'create table `test` (`primary` varchar(255) not null, `index` varchar(255) not null, `foo` varchar(255) not null, index `name3` using hash(`foo`), primary key `name1`(`primary`), index `name2`(`index`))'
            );
        }
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

        if (version_compare(Application::VERSION, '10.38.0', '>=')) {
            $this->assertCreateStatement(
                $blueprint,
                'create rowstore table `test` (`primary` varchar(255) not null, `index` varchar(255) not null, `georegion` geography not null, index `name2`(`index`), index `name3`(`georegion`), primary key (`primary`))'
            );
        } else {
            $this->assertCreateStatement(
                $blueprint,
                'create rowstore table `test` (`primary` varchar(255) not null, `index` varchar(255) not null, `georegion` geography not null, primary key `name1`(`primary`), index `name2`(`index`), index `name3`(`georegion`))'
            );
        }
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

    /** @test */
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

        if (version_compare(Application::VERSION, '10.38.0', '>=')) {
            $this->assertCreateStatement(
                $blueprint,
                'create table `test` (`id` bigint unsigned not null auto_increment, `user_id` bigint unsigned not null, `template_id` varchar(255) not null, `data` longtext not null, `response_status_code` varchar(255) not null, `response_message` longtext not null, `created_at` timestamp null, `updated_at` timestamp null, index `test_id_index`(`id`), shard key(`user_id`))'
            );
        } else {
            $this->assertCreateStatement(
                $blueprint,
                'create table `test` (`id` bigint unsigned not null auto_increment, `user_id` bigint unsigned not null, `template_id` varchar(255) not null, `data` longtext not null, `response_status_code` varchar(255) not null, `response_message` longtext not null, `created_at` timestamp null, `updated_at` timestamp null, index `test_id_index`(`id`), shard key(`user_id`))'
            );
        }
    }

    /** @test */
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
