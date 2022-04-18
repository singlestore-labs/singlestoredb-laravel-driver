<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace SingleStore\Laravel\Tests\Unit\CreateTable;

use Exception;
use SingleStore\Laravel\Schema\Blueprint;
use SingleStore\Laravel\Tests\BaseTest;

class SpatialTest extends BaseTest
{
    use AssertsTableCreation;

    /** @test */
    public function spatial_without_resolution_fluent()
    {
        $blueprint = $this->createTable(function (Blueprint $table) {
            $table->geometry('shape')->spatialIndex('idx');
        });

        $this->assertCreateStatement(
            $blueprint,
            'create table `test` (`shape` geometry not null, index `idx`(`shape`))'
        );
    }

    /** @test */
    public function spatial_with_resolution()
    {
        $blueprint = $this->createTable(function (Blueprint $table) {
            $table->geometry('shape');

            $table->spatialIndex('shape', 'idx')->resolution(8);
        });

        $this->assertCreateStatement(
            $blueprint,
            'create table `test` (`shape` geometry not null, index `idx`(`shape`) with (resolution = 8))'
        );
    }
}