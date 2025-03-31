<?php

namespace SingleStore\Laravel\Tests\Hybrid\CreateTable;

use PHPUnit\Framework\Attributes\Test;
use SingleStore\Laravel\Schema\Blueprint;
use SingleStore\Laravel\Tests\BaseTest;
use SingleStore\Laravel\Tests\Hybrid\HybridTestHelpers;

class SeriesTimestampTest extends BaseTest
{
    use HybridTestHelpers;

    #[Test]
    public function series_timestamp()
    {
        $blueprint = $this->createTable(function (Blueprint $table) {
            $table->date('created_at')->seriesTimestamp();
        });

        $this->assertCreateStatement(
            $blueprint,
            'create table `test` (`created_at` date not null series timestamp)'
        );
    }

    #[Test]
    public function series_timestamp_sparse()
    {
        $blueprint = $this->createTable(function (Blueprint $table) {
            $table->rowstore();

            $table->date('created_at')->nullable()->seriesTimestamp()->sparse();
        });

        $this->assertCreateStatement(
            $blueprint,
            'create rowstore table `test` (`created_at` date null sparse series timestamp)'
        );
    }
}
