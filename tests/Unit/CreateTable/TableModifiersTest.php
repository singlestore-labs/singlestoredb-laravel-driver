<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace SingleStore\Laravel\Tests\Unit\CreateTable;

use SingleStore\Laravel\Schema\Blueprint;
use SingleStore\Laravel\Tests\BaseTest;

class TableModifiersTest extends BaseTest
{
    use AssertsTableCreation;

    /** @test */
    public function all_modifiers_together()
    {
        // This shouldn't actually be done, as it doesn't produce
        // a valid statement. This is just to test that the
        // string interpolation / concatenation works.
        $blueprint = $this->createTable(function (Blueprint $table) {
            $table->rowstore();
            $table->temporary();
            $table->global();
            $table->reference();

            $table->string('name');
        });

        $this->assertCreateStatement(
            $blueprint,
            "create rowstore reference global temporary table `test` (`name` varchar(255) not null)"
        );
    }

    /** @test */
    public function it_creates_a_standard_temp()
    {
        $blueprint = $this->createTable(function (Blueprint $table) {
            $table->temporary();

            $table->string('name');
        });

        $this->assertCreateStatement(
            $blueprint,
            "create temporary table `test` (`name` varchar(255) not null)"
        );
    }


    /** @test */
    public function it_creates_a_global_temp()
    {
        $blueprint = $this->createTable(function (Blueprint $table) {
            $table->temporary();
            $table->global();

            $table->string('name');
        });

        $this->assertCreateStatement(
            $blueprint,
            "create global temporary table `test` (`name` varchar(255) not null)"
        );
    }


    /** @test */
    public function it_creates_a_global_temp_chained()
    {
        $blueprint = $this->createTable(function (Blueprint $table) {
            $table->temporary()->global();

            $table->string('name');
        });

        $this->assertCreateStatement(
            $blueprint,
            "create global temporary table `test` (`name` varchar(255) not null)"
        );
    }


    /** @test */
    public function it_creates_a_global_temp_style_two()
    {
        $blueprint = $this->createTable(function (Blueprint $table) {
            $table->temporary($global = true);

            $table->string('name');
        });

        $this->assertCreateStatement(
            $blueprint,
            "create global temporary table `test` (`name` varchar(255) not null)"
        );
    }

    /** @test */
    public function it_creates_a_global_temp_rowstore()
    {
        $blueprint = $this->createTable(function (Blueprint $table) {
            $table->rowstore();
            $table->temporary();
            $table->global();

            $table->string('name');
        });

        $this->assertCreateStatement(
            $blueprint,
            "create rowstore global temporary table `test` (`name` varchar(255) not null)"
        );
    }

    /** @test */
    public function it_creates_a_reference()
    {
        $blueprint = $this->createTable(function (Blueprint $table) {
            $table->reference();

            $table->string('name');
        });

        $this->assertCreateStatement(
            $blueprint,
            "create reference table `test` (`name` varchar(255) not null)"
        );
    }

    /** @test */
    public function it_creates_a_rowstore_reference()
    {
        $blueprint = $this->createTable(function (Blueprint $table) {
            $table->reference();
            $table->rowstore();

            $table->string('name');
        });

        $this->assertCreateStatement(
            $blueprint,
            "create rowstore reference table `test` (`name` varchar(255) not null)"
        );
    }


    /** @test */
    public function it_creates_a_default()
    {
        $blueprint = $this->createTable(function (Blueprint $table) {
            // Just to ensure we haven't messed up the default case with all our modifier logic.
            $table->string('name');
        });

        $this->assertCreateStatement(
            $blueprint,
            "create table `test` (`name` varchar(255) not null)"
        );
    }
}