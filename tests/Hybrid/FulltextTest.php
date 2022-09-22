<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace SingleStore\Laravel\Tests\Hybrid\Json;

use Illuminate\Support\Facades\DB;
use SingleStore\Laravel\Schema\Blueprint;
use SingleStore\Laravel\Tests\BaseTest;
use SingleStore\Laravel\Tests\Hybrid\HybridTestHelpers;

class FulltextTest extends BaseTest
{
    use HybridTestHelpers;

    /** @test */
    public function fulltext()
    {
        $query = DB::table('test')->whereFullText('title', 'performance');

        $this->assertEquals(
            "select * from `test` where MATCH (`title`) AGAINST (?)",
            $query->toSql()
        );

        $this->assertSame('performance', $query->getBindings()[0]);

        if (! $this->runHybridIntegrations()) {
            return;
        }

        $this->createTable(function (Blueprint $table) {
            $table->id();
            $table->text('title');

            $table->charset = 'utf8';
            $table->collation = 'utf8_unicode_ci';
            $table->fullText(['title']);
        });

        DB::table('test')->insert([[
            'title' => 'Designing Data-Intensive Applications: The Big Ideas Behind Reliable, Scalable, and Maintainable Systems',
        ], [
            'title' => 'Data Pipelines Pocket Reference: Moving and Processing Data for Analytics',
        ], [
            'title' => 'Data Quality Fundamentals',
        ], [
            'title' => 'High Performance MySQL: Optimization, Backups, and Replication',
        ]]);

        // @TODO Assert query returns
    }

    /** @test */
    public function fulltext_multicolumn()
    {
        $query = DB::table('test')->whereFullText(['first_name', 'last_name'], 'aaron');

        $this->assertEquals(
            'select * from `test` where MATCH (`first_name`, `last_name`) AGAINST (?)',
            $query->toSql()
        );

        $this->assertSame('aaron', $query->getBindings()[0]);

        if (!$this->runHybridIntegrations()) {
            return;
        }

        $this->createTable(function (Blueprint $table) {
            $table->id();
            $table->text('first_name');
            $table->text('last_name');

            $table->charset = 'utf8';
            $table->collation = 'utf8_unicode_ci';
            $table->fullText(['first_name']);
        });

        DB::table('test')->insert([[
            'first_name' => 'aaron',
            'last_name' => 'francis',
        ], [
            'first_name' => 'franco',
            'last_name' => 'gilio',
        ]]);

        // @TODO Assert query returns
    }
}
