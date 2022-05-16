<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace SingleStore\Laravel\Tests\Hybrid\CreateTable;

use SingleStore\Laravel\Schema\Blueprint;
use SingleStore\Laravel\Tests\BaseTest;
use SingleStore\Laravel\Tests\Hybrid\HybridTestHelpers;

class SpatialTest extends BaseTest
{
    use HybridTestHelpers;

    /** @test */
    public function geography_without_resolution_fluent()
    {
        $blueprint = $this->createTable(function (Blueprint $table) {
            $table->rowstore();

            $table->geography('shape')->spatialIndex('idx');
        });

        $this->assertCreateStatement(
            $blueprint,
            'create rowstore table `test` (`shape` geography not null, index `idx`(`shape`))'
        );
    }

    /** @test */
    public function geography_with_resolution()
    {
        $blueprint = $this->createTable(function (Blueprint $table) {
            $table->rowstore();

            $table->geography('shape');

            $table->spatialIndex('shape', 'idx')->resolution(8);
        });

        $this->assertCreateStatement(
            $blueprint,
            'create rowstore table `test` (`shape` geography not null, index `idx`(`shape`) with (resolution = 8))'
        );
    }

    /** @test */
    public function geography_point()
    {
        $blueprint = $this->createTable(function (Blueprint $table) {
            $table->rowstore();

            $table->point('point1');
            $table->geographyPoint('point2');
        });

        $this->assertCreateStatement(
            $blueprint,
            'create rowstore table `test` (`point1` geographypoint not null, `point2` geographypoint not null)'
        );
    }
}