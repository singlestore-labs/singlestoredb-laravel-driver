<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace SingleStore\Laravel\Tests\Hybrid\CreateTable;

use SingleStore\Laravel\Schema\Blueprint;
use SingleStore\Laravel\Tests\BaseTest;
use SingleStore\Laravel\Tests\Hybrid\HybridTestHelpers;

class MiscCreateTests extends BaseTest
{
    use HybridTestHelpers;

    /** @test */
    public function all_keys_are_added_in_create()
    {
        $blueprint = $this->createTable(function (Blueprint $table) {
            $table->string('primary')->primary('name1');
            $table->string('index')->index('name2');
            $table->geometry('spatialIndex')->spatialIndex('name3');
            $table->string('fulltext')->fulltext('name4');
        });

        $this->assertCreateStatement(
            $blueprint,
            'create table `test` (`primary` varchar(255) not null, `index` varchar(255) not null, `spatialIndex` geometry not null, `fulltext` varchar(255) not null, primary key `name1`(`primary`), index `name2`(`index`), index `name3`(`spatialIndex`), fulltext `name4`(`fulltext`))'
        );
    }

    /** @test */
    public function fulltext_index()
    {
        $blueprint = $this->createTable(function (Blueprint $table) {
            $table->string('name');

            $table->fullText('name', 'idx');
        });

        $this->assertCreateStatement(
            $blueprint,
            'create table `test` (`name` varchar(255) not null, fulltext `idx`(`name`))'
        );
    }

    /** @test */
    public function option_column()
    {
        $blueprint = $this->createTable(function (Blueprint $table) {
            $table->string('name')->option('this is an option');
        });

        $this->assertCreateStatement(
            $blueprint,
            "create table `test` (`name` varchar(255) not null option 'this is an option')"
        );
    }
}
