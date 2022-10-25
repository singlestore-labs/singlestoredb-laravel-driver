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
        if (version_compare(Application::VERSION, '8.0.0', '=')) {
            // fulltext not added until later on in laravel 8 releases
            $this->markTestSkipped('requires higher laravel version');
        }

        $blueprint = $this->createTable(function (Blueprint $table) {
            $table->string('primary')->primary('name1');
            $table->string('index')->index('name2');
            $table->string('fulltext')->fulltext('name5')->charset('utf8');
        });

        $this->assertCreateStatement(
            $blueprint,
            'create table `test` (`primary` varchar(255) not null, `index` varchar(255) not null, `fulltext` varchar(255) character set utf8 not null, primary key `name1`(`primary`), index `name2`(`index`), fulltext `name5`(`fulltext`))'
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
    public function fulltext_index()
    {
        if (version_compare(Application::VERSION, '8.0.0', '=')) {
            // fulltext not added until later on in laravel 8 releases
            $this->markTestSkipped('requires higher laravel version');
        }

        $blueprint = $this->createTable(function (Blueprint $table) {
            $table->string('name')->charset('utf8');
            $table->fullText('name', 'idx');
        });

        $this->assertCreateStatement(
            $blueprint,
            'create table `test` (`name` varchar(255) character set utf8 not null, fulltext `idx`(`name`))'
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
