<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace SingleStore\Laravel\Tests\Unit\CreateTable;

use Exception;
use SingleStore\Laravel\Schema\Blueprint;
use SingleStore\Laravel\Tests\BaseTest;

class SeriesTimestampTest extends BaseTest
{
    use AssertsTableCreation;

    /** @test */
    public function series_timestamp()
    {
        $blueprint = $this->createTable(function (Blueprint $table) {
            $table->date('created_at')->seriesTimestamp();
        });

        $this->assertCreateStatement(
            $blueprint,
            "create table `test` (`created_at` date not null series timestamp)"
        );
    }


    /** @test */
    public function series_timestamp_sparse()
    {
        $blueprint = $this->createTable(function (Blueprint $table) {
            $table->rowstore();

            $table->date('created_at')->nullable()->seriesTimestamp()->sparse();
        });

        $this->assertCreateStatement(
            $blueprint,
            "create rowstore table `test` (`created_at` date null sparse series timestamp)"
        );
    }
}